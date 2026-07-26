#!/usr/bin/env python3
"""
Restore QBO item NAMES to the pre-rename state from a backup snapshot.

Source of truth: qbo-backups/<snapshot>/Item.json (original numeric Names,
captured before any rename). For every item whose current Name differs from
the backup, set it back. Read-only against the backup; writes only to QBO.

  DRY-RUN by default. Writes only with --execute.
  --snapshot <dir>  backup folder under qbo-backups/ (default: latest)
"""
import os, sys, json, time, base64, argparse, tempfile, urllib.parse, urllib.request, urllib.error

HERE = os.path.dirname(os.path.abspath(__file__))
ENV_PATH = os.path.join(HERE, ".env")
QBO_BASE = "https://quickbooks.api.intuit.com/v3/company"
TOKEN_URL = "https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer"
BACKUP_DIR = os.path.join(HERE, "qbo-backups")


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


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--execute", action="store_true")
    ap.add_argument("--snapshot", default=None)
    args = ap.parse_args()

    snap = args.snapshot
    if not snap:
        snaps = sorted(d for d in os.listdir(BACKUP_DIR)
                       if os.path.isdir(os.path.join(BACKUP_DIR, d)))
        snap = snaps[-1]
    snap_path = os.path.join(BACKUP_DIR, snap, "Item.json")
    orig = {i["Id"]: i.get("Name") for i in json.load(open(snap_path))}
    print(f"Backup snapshot: {snap}  ({len(orig)} original names)\n")

    env = load_env()
    qbo = QBO(env)
    mode = "EXECUTE" if args.execute else "DRY-RUN"
    print(f"=== restore item names ({mode}) — realm {qbo.realm} ===\n")

    live = fetch_all_items(qbo)
    print(f"Fetched {len(live)} live items.\n")

    changed = [it for it in live
               if it["Id"] in orig and orig[it["Id"]] is not None
               and it.get("Name") != orig[it["Id"]]]
    print(f"{len(changed)} items differ from backup and will be restored.\n")
    for it in changed[:12]:
        print(f"  {it.get('Name'):40s} -> {orig[it['Id']]!r}")
    if len(changed) > 12:
        print(f"  ... and {len(changed)-12} more")

    if not args.execute:
        return

    done, fails = 0, 0
    for i, it in enumerate(changed, 1):
        payload = dict(it)
        payload["Name"] = orig[it["Id"]]
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
            print(f"  ...{i}/{len(changed)} ({done} ok, {fails} fail)")
        time.sleep(0.13)

    print(f"\nDone: {done} restored, {fails} failed.")


if __name__ == "__main__":
    main()
