#!/usr/bin/env python3
"""
Ah Ho Fruit — make QBO items findable by fruit name WITHOUT losing the product number.

QBO's invoice item search matches the item NAME + SKU only (never the Sales
Description). Items are named by product numbers, so typing a fruit name finds
nothing. Fix: set  Name = "<number> <description>"  so BOTH the number and the
fruit name are searchable. SKU is left untouched (B2B/WooCommerce match on it).

  0007  -> 0007 44" ZESPRI GOLD KIWI 金奇异果
  1006  -> 1006 5kg 8.5R" USA. CHERRY 美国车厘子

  DRY-RUN by default. Writes only with --execute.
  Idempotent: skips items whose Name already contains the description.
  Per-run rollback JSON written on --execute.
"""
import os, sys, json, time, re, base64, argparse, tempfile, urllib.parse, urllib.request, urllib.error
from datetime import datetime, timezone

HERE = os.path.dirname(os.path.abspath(__file__))
ENV_PATH = os.path.join(HERE, ".env")
QBO_BASE = "https://quickbooks.api.intuit.com/v3/company"
TOKEN_URL = "https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer"
NAME_MAX = 100


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
    except Exception as e:
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


def fetch_all_items(qbo):
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


def clean_desc(desc):
    return re.sub(r"\s+", " ", (desc or "").replace(":", "-")).strip()


def make_name(number, desc):
    d = clean_desc(desc)
    if not d:
        return None
    combined = f"{number} {d}"
    return combined[:NAME_MAX]


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--execute", action="store_true")
    args = ap.parse_args()

    env = load_env()
    qbo = QBO(env)
    mode = "EXECUTE" if args.execute else "DRY-RUN"
    print(f"=== QBO item name prefix ({mode}) — realm {qbo.realm} ===\n")

    items = fetch_all_items(qbo)
    print(f"Fetched {len(items)} items.\n")

    plan = []
    for it in items:
        name = (it.get("Name") or "").strip()
        d = clean_desc(it.get("Description"))
        if not d:
            continue                              # nothing searchable to add
        if d in name:
            continue                              # already has the description (idempotent)
        new_name = make_name(name, it.get("Description"))
        if not new_name or new_name == name:
            continue
        plan.append((it, new_name))

    print(f"{len(plan)} items will get 'number + description' names "
          f"({mode}).\n")
    for it, nn in plan[:12]:
        print(f"  {it.get('Name'):10s} -> {nn!r}")
    if len(plan) > 12:
        print(f"  ... and {len(plan)-12} more")

    if not args.execute:
        return

    rollback, done, fails = [], 0, 0
    for i, (it, nn) in enumerate(plan, 1):
        rollback.append({"Id": it["Id"], "old_name": it.get("Name"), "new_name": nn})
        payload = dict(it)
        payload["Name"] = nn
        st, d = qbo.update_item(payload)
        tries = 0
        while st != 200 and tries < 3:
            time.sleep(2 + tries * 2)
            st, d = qbo.update_item(payload)
            tries += 1
        if st == 200:
            done += 1
        else:
            fails += 1
            print(f"  FAIL {it.get('Name')} {st}: {str(d)[:150]}")
        if i % 100 == 0:
            print(f"  ...{i}/{len(plan)} ({done} ok, {fails} fail)", flush=True)
        time.sleep(0.13)

    ts = datetime.now(timezone.utc).strftime("%Y-%m-%dT%H-%M-%SZ")
    rbpath = os.path.join(HERE, f".qbo-name-prefix-rollback-{ts}.json")
    json.dump(rollback, open(rbpath, "w"), indent=2, ensure_ascii=False)
    print(f"\nRollback saved: {rbpath}")
    print(f"Done: {done} renamed, {fails} failed.")


if __name__ == "__main__":
    main()
