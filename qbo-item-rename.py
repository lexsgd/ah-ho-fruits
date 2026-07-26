#!/usr/bin/env python3
"""
Ah Ho Fruit — restore descriptive item NAMES in QuickBooks.

The WooCommerce<->QBO B2B plugin wrote product *numbers* into each item's Name
field and pushed the real fruit description into the Description field. QBO's
invoice "Product/service" typeahead searches Name only, so staff can no longer
type a description to find an item. This restores Name = Description so typing
works again. Every original Name is saved to a rollback file first.

Reuses the dedicated B2C Intuit app credentials (same company/realm).

  DRY-RUN by default. Writes only with --execute.
  --limit N     cap number of items (default 10 for the test batch)
  --all         operate on every numeric-named item with a description
"""
import os, sys, json, time, re, base64, argparse, tempfile, urllib.parse, urllib.request, urllib.error
from datetime import datetime, timezone

HERE = os.path.dirname(os.path.abspath(__file__))
ENV_PATH = os.path.join(HERE, ".env")
QBO_BASE = "https://quickbooks.api.intuit.com/v3/company"
TOKEN_URL = "https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer"

# The 10-item test batch: numeric-named items with clean, readable descriptions.
TEST_NAMES = ["1006", "1007", "1008", "1006.1", "0004.1",
              "0007.4", "1020.1", "3019.1", "3034.1", "1015.8"]


def load_env(path=ENV_PATH):
    env = {}
    if os.path.exists(path):
        for line in open(path):
            s = line.strip()
            if s and not s.startswith("#") and "=" in s:
                k, v = s.split("=", 1)
                env[k.strip()] = v.strip().strip('"').strip("'")
    return env


def set_env_value(key, value, path=ENV_PATH):
    lines = open(path).read().splitlines() if os.path.exists(path) else []
    found = False
    for i, l in enumerate(lines):
        if l.startswith(key + "="):
            lines[i] = f"{key}={value}"; found = True; break
    if not found:
        lines.append(f"{key}={value}")
    fd, tmp = tempfile.mkstemp(dir=os.path.dirname(path))
    os.write(fd, ("\n".join(lines) + "\n").encode()); os.close(fd)
    os.replace(tmp, path)


def _req(url, method="GET", headers=None, data=None):
    r = urllib.request.Request(url, method=method, headers=headers or {},
                               data=data.encode() if isinstance(data, str) else data)
    try:
        with urllib.request.urlopen(r, timeout=60) as resp:
            return resp.status, json.loads(resp.read().decode() or "{}")
    except urllib.error.HTTPError as e:
        body = e.read().decode()
        try: body = json.loads(body)
        except Exception: pass
        return e.code, body
    except Exception as e:                 # socket read timeouts etc — never crash the run
        return 0, {"_exc": str(e)}


class QBO:
    def __init__(self, env):
        self.realm = env["QBO_B2C_REALM_ID"]
        self.cid = env["QBO_B2C_CLIENT_ID"]
        self.csec = env["QBO_B2C_CLIENT_SECRET"]
        self.refresh = env["QBO_B2C_REFRESH_TOKEN"]
        self.access = None

    def _refresh_token(self):
        auth = base64.b64encode(f"{self.cid}:{self.csec}".encode()).decode()
        body = urllib.parse.urlencode({"grant_type": "refresh_token",
                                       "refresh_token": self.refresh})
        st, d = _req(TOKEN_URL, "POST",
                     {"Authorization": f"Basic {auth}", "Accept": "application/json",
                      "Content-Type": "application/x-www-form-urlencoded"}, body)
        if st != 200 or "access_token" not in d:
            sys.exit(f"[!] token refresh failed ({st}): {str(d)[:200]}")
        self.access = d["access_token"]
        new_rt = d.get("refresh_token")
        if new_rt and new_rt != self.refresh:
            self.refresh = new_rt
            set_env_value("QBO_B2C_REFRESH_TOKEN", new_rt)

    def _h(self):
        if not self.access:
            self._refresh_token()
        return {"Authorization": f"Bearer {self.access}", "Accept": "application/json",
                "Content-Type": "application/json"}

    def query(self, q):
        url = f"{QBO_BASE}/{self.realm}/query?minorversion=70&query=" + urllib.parse.quote(q)
        st, d = _req(url, "GET", self._h())
        return d.get("QueryResponse", {}) if st == 200 else {"_error": d}

    def update_item(self, item):
        url = f"{QBO_BASE}/{self.realm}/item?minorversion=70"
        return _req(url, "POST", self._h(), json.dumps(item))


def clean_name(desc):
    """QBO item Name: no ':' allowed, max 100 chars, trimmed/collapsed."""
    n = re.sub(r"\s+", " ", (desc or "").replace(":", "-")).strip()
    return n[:100]


def get_item_by_name(qbo, name):
    esc = name.replace("'", "\\'")
    qr = qbo.query(f"select * from Item where Name = '{esc}'")
    items = qr.get("Item", [])
    return items[0] if items else None


def name_taken(qbo, new_name, self_id):
    esc = new_name.replace("'", "\\'")
    qr = qbo.query(f"select Id from Item where Name = '{esc}'")
    for it in qr.get("Item", []):
        if it["Id"] != self_id:
            return True
    return False


def fetch_all_items(qbo):
    """Paginate every item (full objects)."""
    out, start, page = [], 1, 1000
    while True:
        qr = qbo.query(f"select * from Item startposition {start} maxresults {page}")
        rows = qr.get("Item", [])
        if not rows:
            break
        out.extend(rows)
        if len(rows) < page:
            break
        start += page
        time.sleep(0.2)
    return out


def looks_like_code(name):
    """True if Name is a product-number / short code rather than a description.
    Codes: start with a digit, or short all-caps/alnum tokens with no lowercase words."""
    n = (name or "").strip()
    if not n:
        return False
    if n[0].isdigit():
        return True
    # short codes with no lowercase letters and <= 12 chars (e.g. BT.5, SC.2, PC, 48AP, DUMP1, ADDON)
    if len(n) <= 12 and not any(c.islower() for c in n):
        return True
    return False


def run_full_pass(qbo, execute):
    items = fetch_all_items(qbo)
    print(f"Fetched {len(items)} items.\n")
    taken = {(i.get("Name") or "").lower() for i in items}

    plan = []
    for it in items:
        name = it.get("Name") or ""
        desc = (it.get("Description") or "").strip()
        if not desc:
            continue
        if not looks_like_code(name):
            continue                     # already has a readable name — leave it
        base = clean_name(desc)
        if not base or base.lower() == name.lower():
            continue
        new_name = base
        if new_name.lower() in taken and new_name.lower() != name.lower():
            new_name = clean_name(f"{desc} [{name}]")
        # extremely rare secondary collision
        n2 = new_name; k = 2
        while new_name.lower() in taken and new_name.lower() != name.lower():
            new_name = clean_name(f"{n2} ({k})"); k += 1
        taken.discard(name.lower())
        taken.add(new_name.lower())
        plan.append((it, new_name))

    print(f"{len(plan)} items will be renamed "
          f"({'EXECUTE' if execute else 'DRY-RUN'}).\n")
    for it, nn in plan[:15]:
        print(f"  {it.get('Name'):12s} -> {nn!r}")
    if len(plan) > 15:
        print(f"  ... and {len(plan)-15} more")

    if not execute:
        return [], 0

    rollback, done, fails = [], 0, 0
    for i, (it, nn) in enumerate(plan, 1):
        rollback.append({"Id": it["Id"], "old_name": it.get("Name"),
                         "new_name": nn, "sku": it.get("Sku")})
        payload = dict(it)
        payload["Name"] = nn
        st, d = qbo.update_item(payload)
        tries = 0
        while st != 200 and tries < 3:   # retry timeouts (0) / throttle (429) / transient 5xx
            time.sleep(2 + tries * 2)
            st, d = qbo.update_item(payload)
            tries += 1
        if st == 200:
            done += 1
        else:
            fails += 1
            print(f"  FAIL {it.get('Name')} {st}: {str(d)[:150]}")
        if i % 100 == 0:
            print(f"  ...{i}/{len(plan)} ({done} ok, {fails} fail)")
        time.sleep(0.13)
    return rollback, fails


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--execute", action="store_true", help="actually write (default dry-run)")
    ap.add_argument("--limit", type=int, default=10)
    ap.add_argument("--all", action="store_true", help="full pass over every item")
    args = ap.parse_args()

    env = load_env()
    qbo = QBO(env)
    mode = "EXECUTE" if args.execute else "DRY-RUN"
    print(f"=== QBO item rename ({mode}) — realm {qbo.realm} ===\n")

    if args.all:
        rollback, fails = run_full_pass(qbo, args.execute)
        if args.execute and rollback:
            ts = datetime.now(timezone.utc).strftime("%Y-%m-%dT%H-%M-%SZ")
            rbpath = os.path.join(HERE, f".qbo-item-rename-rollback-{ts}.json")
            json.dump(rollback, open(rbpath, "w"), indent=2, ensure_ascii=False)
            print(f"\nRollback saved: {rbpath}")
            print(f"Done: {len(rollback)-fails} renamed, {fails} failed.")
        return

    targets = TEST_NAMES[:args.limit]
    rollback = []
    done = 0
    for name in targets:
        item = get_item_by_name(qbo, name)
        if not item:
            print(f"  SKIP  {name:10s}  (not found — maybe already renamed)")
            continue
        desc = item.get("Description") or ""
        if not desc.strip():
            print(f"  SKIP  {name:10s}  (no description to use)")
            continue
        new_name = clean_name(desc)
        if new_name == item.get("Name"):
            print(f"  SKIP  {name:10s}  (already descriptive)")
            continue
        if name_taken(qbo, new_name, item["Id"]):
            new_name = clean_name(f"{desc} [{name}]")
            print(f"  NOTE  {name:10s}  name collision -> appending code")

        print(f"  {name:10s} -> {new_name!r}")
        rollback.append({"Id": item["Id"], "old_name": item.get("Name"),
                         "new_name": new_name, "sku": item.get("Sku")})

        if args.execute:
            payload = dict(item)
            payload["Name"] = new_name
            st, d = qbo.update_item(payload)
            if st == 200:
                done += 1
                print(f"           OK (SyncToken {d['Item']['SyncToken']})")
            else:
                err = d.get("Fault", d) if isinstance(d, dict) else d
                print(f"           FAIL {st}: {str(err)[:200]}")
            time.sleep(0.3)

    if args.execute and rollback:
        ts = datetime.now(timezone.utc).strftime("%Y-%m-%dT%H-%M-%SZ")
        rbpath = os.path.join(HERE, f".qbo-item-rename-rollback-{ts}.json")
        json.dump(rollback, open(rbpath, "w"), indent=2, ensure_ascii=False)
        print(f"\nRollback saved: {rbpath}")

    print(f"\n{mode}: {done if args.execute else len(rollback)} item(s) "
          f"{'renamed' if args.execute else 'would be renamed'}.")


if __name__ == "__main__":
    main()
