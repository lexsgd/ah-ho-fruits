#!/usr/bin/env python3
"""
Robust standalone re-pull of QBO Invoices (the entity that 500s on bulk SELECT *).
Small page size + per-page retries with backoff. Writes Invoice.json into the
given backup dir and updates manifest.json with count + sha256.

Usage: python3 qbo-backup-invoices.py <backup-dir>
"""
import os, sys, json, time, base64, hashlib, urllib.request, urllib.parse, urllib.error

PROJ_DIR = os.path.dirname(os.path.abspath(__file__))
HOME_ENV = os.path.expanduser("~/.env")
PROJ_ENV = os.path.join(PROJ_DIR, ".env")
TOKEN_URL = "https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer"
API_BASE = "https://quickbooks.api.intuit.com/v3/company"
ENTITY = "Invoice"
PAGE = 100  # small to avoid 500s on heavy line-item payloads


def load_env(path):
    env = {}
    if os.path.exists(path):
        for line in open(path):
            line = line.strip()
            if line and not line.startswith("#") and "=" in line:
                k, v = line.split("=", 1)
                env[k.strip()] = v.strip()
    return env


def refresh_access(client_id, client_secret, refresh):
    basic = base64.b64encode(f"{client_id}:{client_secret}".encode()).decode()
    data = urllib.parse.urlencode({"grant_type": "refresh_token", "refresh_token": refresh}).encode()
    req = urllib.request.Request(TOKEN_URL, data=data, headers={
        "Authorization": f"Basic {basic}",
        "Content-Type": "application/x-www-form-urlencoded",
        "Accept": "application/json"}, method="POST")
    with urllib.request.urlopen(req, timeout=60) as r:
        tok = json.loads(r.read().decode())
    # persist rotated refresh token
    new_r = tok.get("refresh_token")
    if new_r and new_r != refresh:
        lines = []
        for line in open(PROJ_ENV):
            lines.append(f"QBO_AHHO_REFRESH_TOKEN={new_r}\n" if line.startswith("QBO_AHHO_REFRESH_TOKEN=") else line)
        open(PROJ_ENV, "w").writelines(lines)
    return tok["access_token"]


def fetch_page(realm, token, start, page):
    q = f"SELECT * FROM {ENTITY} ORDERBY Id STARTPOSITION {start} MAXRESULTS {page}"
    url = f"{API_BASE}/{realm}/query?query={urllib.parse.quote(q)}&minorversion=65"
    req = urllib.request.Request(url, headers={"Authorization": f"Bearer {token}", "Accept": "application/json"})
    with urllib.request.urlopen(req, timeout=180) as r:
        return json.loads(r.read().decode()).get("QueryResponse", {}).get(ENTITY, [])


def main():
    if len(sys.argv) < 2:
        print("usage: qbo-backup-invoices.py <backup-dir>"); sys.exit(1)
    outdir = sys.argv[1]
    home, proj = load_env(HOME_ENV), load_env(PROJ_ENV)
    token = refresh_access(home["QBO_CLIENT_ID"], home["QBO_CLIENT_SECRET"], proj["QBO_AHHO_REFRESH_TOKEN"])
    realm = proj["QBO_AHHO_REALM_ID"]

    rows, start = [], 1
    while True:
        page = PAGE
        ok = False
        for attempt in range(5):
            try:
                batch = fetch_page(realm, token, start, page)
                ok = True
                break
            except urllib.error.HTTPError as e:
                wait = 2 * (attempt + 1)
                if e.code in (500, 503, 429):
                    page = max(25, page // 2)  # shrink page on server error
                    print(f"  start={start} HTTP {e.code}, retry {attempt+1} (page->{page}) in {wait}s")
                    time.sleep(wait)
                elif e.code == 401:
                    print("  token expired mid-run; refreshing")
                    token = refresh_access(home["QBO_CLIENT_ID"], home["QBO_CLIENT_SECRET"], load_env(PROJ_ENV)["QBO_AHHO_REFRESH_TOKEN"])
                else:
                    print(f"  start={start} HTTP {e.code}: {e.read().decode()[:150]}"); time.sleep(wait)
            except Exception as ex:
                print(f"  start={start} {ex}, retry {attempt+1}"); time.sleep(2 * (attempt + 1))
        if not ok:
            print(f"  ABORT: could not fetch page at start={start} after retries"); sys.exit(2)
        if not batch:
            break
        rows.extend(batch)
        print(f"  fetched {len(rows)} (last page {len(batch)})")
        if len(batch) < page:
            break
        start += len(batch)
        time.sleep(0.2)

    blob = json.dumps(rows, indent=2, sort_keys=True).encode()
    sha = hashlib.sha256(blob).hexdigest()
    open(os.path.join(outdir, f"{ENTITY}.json"), "wb").write(blob)

    mpath = os.path.join(outdir, "manifest.json")
    m = json.load(open(mpath))
    m["entities"][ENTITY] = {"count": len(rows), "sha256": sha}
    m["total_records"] = sum(v.get("count", 0) for v in m["entities"].values())
    json.dump(m, open(mpath, "w"), indent=2)
    print(f"\nInvoice backup complete: {len(rows)} records -> {ENTITY}.json (sha {sha[:12]}...)")


if __name__ == "__main__":
    main()
