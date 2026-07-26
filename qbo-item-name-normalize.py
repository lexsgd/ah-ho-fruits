#!/usr/bin/env python3
"""
Ah Ho Fruit — final item-name form for searchability:  Name = "<number> <normalized description>".

QBO's invoice search does CONTIGUOUS substring matching on Name+SKU. Staff type
partial words with spaces and no punctuation ("7 ind pine"), which fails against
'7" IND. PINEAPPLE' because the " and . break contiguity. Fix: keep the product
NUMBER exactly (with its decimal, so typing the number still works) and append the
description with all punctuation flattened to single spaces, so word-by-word typing
matches as a contiguous substring.

  5021.1  ->  5021.1 7 IND PINEAPPLE 印尼黄梨   (typing "7 ind pine" now matches)

Number source: the pre-everything backup Item.json (pure numeric Names).
DRY-RUN by default; --execute writes. Idempotent. Rollback JSON on --execute.
"""
import os, sys, re, json, time, base64, argparse, tempfile, urllib.parse, urllib.request, urllib.error
from datetime import datetime, timezone

HERE = os.path.dirname(os.path.abspath(__file__))
ENV_PATH = os.path.join(HERE, ".env")
QBO_BASE = "https://quickbooks.api.intuit.com/v3/company"
TOKEN_URL = "https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer"
BACKUP = os.path.join(HERE, "qbo-backups", "2026-06-30T13-23-34Z", "Item.json")
NAME_MAX = 100


def load_env(path=ENV_PATH):
    env = {}
    for line in open(path):
        s = line.strip()
        if s and not s.startswith("#") and "=" in s:
            k, v = s.split("=", 1); env[k.strip()] = v.strip().strip('"').strip("'")
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
        b = e.read().decode()
        try: b = json.loads(b)
        except Exception: pass
        return e.code, b
    except Exception as e:
        return 0, {"_exc": str(e)}


class QBO:
    def __init__(self, env):
        self.realm = env["QBO_B2C_REALM_ID"]; self.cid = env["QBO_B2C_CLIENT_ID"]
        self.csec = env["QBO_B2C_CLIENT_SECRET"]; self.refresh = env["QBO_B2C_REFRESH_TOKEN"]
        self.access = None

    def _refresh_token(self):
        auth = base64.b64encode(f"{self.cid}:{self.csec}".encode()).decode()
        st, d = _req(TOKEN_URL, "POST",
                     {"Authorization": f"Basic {auth}", "Accept": "application/json",
                      "Content-Type": "application/x-www-form-urlencoded"},
                     urllib.parse.urlencode({"grant_type": "refresh_token", "refresh_token": self.refresh}))
        if st != 200 or "access_token" not in d:
            sys.exit(f"[!] token refresh failed ({st}): {str(d)[:200]}")
        self.access = d["access_token"]
        nt = d.get("refresh_token")
        if nt and nt != self.refresh:
            self.refresh = nt; set_env_value("QBO_B2C_REFRESH_TOKEN", nt)

    def _h(self):
        if not self.access: self._refresh_token()
        return {"Authorization": f"Bearer {self.access}", "Accept": "application/json",
                "Content-Type": "application/json"}

    def query(self, q):
        st, d = _req(f"{QBO_BASE}/{self.realm}/query?minorversion=70&query=" + urllib.parse.quote(q), "GET", self._h())
        return d.get("QueryResponse", {}) if st == 200 else {"_error": d}

    def update_item(self, item):
        return _req(f"{QBO_BASE}/{self.realm}/item?minorversion=70", "POST", self._h(), json.dumps(item))


def fetch_all_items(qbo):
    out, start, page = [], 1, 1000
    while True:
        rows = qbo.query(f"select * from Item startposition {start} maxresults {page}").get("Item", [])
        if not rows: break
        out.extend(rows)
        if len(rows) < page: break
        start += page; time.sleep(0.2)
    return out


def norm_desc(desc):
    # flatten every run of non-word chars (", . , - ( ) / spaces ...) to one space; keep letters/digits/CJK
    return re.sub(r"[^\w]+", " ", (desc or ""), flags=re.UNICODE).strip()


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--execute", action="store_true")
    args = ap.parse_args()

    orig = {i["Id"]: {"num": i.get("Name"), "desc": i.get("Description")}
            for i in json.load(open(BACKUP))}
    env = load_env(); qbo = QBO(env)
    mode = "EXECUTE" if args.execute else "DRY-RUN"
    print(f"=== normalize item names ({mode}) — realm {qbo.realm} ===\n")

    live = fetch_all_items(qbo)
    print(f"Fetched {len(live)} live items.\n")

    plan = []
    for it in live:
        o = orig.get(it["Id"])
        if o and o["num"]:
            number = o["num"]                       # pure number from backup (keeps its decimal)
            desc = o["desc"]
        else:
            number = (it.get("Name") or "").split(" ", 1)[0]   # edge items: first token
            desc = it.get("Description")
        nd = norm_desc(desc)
        if not nd:
            continue
        new_name = f"{number} {nd}"[:NAME_MAX]
        if new_name == (it.get("Name") or ""):
            continue                                # idempotent
        plan.append((it, new_name))

    print(f"{len(plan)} items will be set to '<number> <normalized desc>' ({mode}).\n")
    for it, nn in plan[:12]:
        print(f"  {it.get('Name'):32.32s} -> {nn!r}")
    if len(plan) > 12:
        print(f"  ... and {len(plan)-12} more")

    if not args.execute:
        return

    rollback, done, fails = [], 0, 0
    for i, (it, nn) in enumerate(plan, 1):
        rollback.append({"Id": it["Id"], "old_name": it.get("Name"), "new_name": nn})
        payload = dict(it); payload["Name"] = nn
        st, d = qbo.update_item(payload)
        t = 0
        while st != 200 and t < 3:
            time.sleep(2 + t * 2); st, d = qbo.update_item(payload); t += 1
        if st == 200: done += 1
        else: fails += 1; print(f"  FAIL {it.get('Name')} {st}: {str(d)[:120]}")
        if i % 100 == 0: print(f"  ...{i}/{len(plan)} ({done} ok, {fails} fail)", flush=True)
        time.sleep(0.13)

    ts = datetime.now(timezone.utc).strftime("%Y-%m-%dT%H-%M-%SZ")
    rb = os.path.join(HERE, f".qbo-name-normalize-rollback-{ts}.json")
    json.dump(rollback, open(rb, "w"), indent=2, ensure_ascii=False)
    print(f"\nRollback saved: {rb}\nDone: {done} renamed, {fails} failed.")


if __name__ == "__main__":
    main()
