#!/usr/bin/env python3
"""
Phase 1 of the WC<->QBO catalog migration: write each WooCommerce SKU into the
matching QuickBooks item's empty Sku field, so the Intuit WooCommerce Connector
matches by SKU. ONLY the clean 1-to-1 mappings (built by the dry-run) are written.

Safety:
  - Reuses qbo-backup.py's OAuth refresh (rotated refresh token persisted to .env).
  - DRY-RUN by default. Writes only with --execute.
  - Re-reads each item's LIVE SyncToken right before patching (no stale writes).
  - Skips any item whose Sku is already set (never overwrites).
  - Sparse update of the Sku field ONLY — no other field, no invoices touched.
  - Records a rollback file (every item written had Sku="" before -> revert = clear).

Usage:
  python3 qbo-write-skus.py                 # dry-run, all
  python3 qbo-write-skus.py --limit 1 --execute   # write ONE (test)
  python3 qbo-write-skus.py --execute             # write all remaining
"""
import os, sys, json, time, base64, urllib.request, urllib.parse, urllib.error
from datetime import datetime, timezone

PROJ_DIR = os.path.dirname(os.path.abspath(__file__))
HOME_ENV = os.path.expanduser("~/.env")
PROJ_ENV = os.path.join(PROJ_DIR, ".env")
PLAN = "/Users/lexnaweiming/Test/phase1-write-plan.json"
RESULTS = "/Users/lexnaweiming/Test/phase1-write-results.json"
ROLLBACK = "/Users/lexnaweiming/Test/phase1-rollback.json"
TOKEN_URL = "https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer"
API_BASE = "https://quickbooks.api.intuit.com/v3/company"

def load_env(path):
    env = {}
    if os.path.exists(path):
        for line in open(path):
            line = line.strip()
            if line and not line.startswith("#") and "=" in line:
                k, v = line.split("=", 1)
                env[k.strip()] = v.strip()
    return env

def refresh_token(cid, secret, refresh):
    basic = base64.b64encode(f"{cid}:{secret}".encode()).decode()
    data = urllib.parse.urlencode({"grant_type": "refresh_token", "refresh_token": refresh}).encode()
    req = urllib.request.Request(TOKEN_URL, data=data, method="POST", headers={
        "Authorization": f"Basic {basic}",
        "Content-Type": "application/x-www-form-urlencoded", "Accept": "application/json"})
    with urllib.request.urlopen(req, timeout=60) as r:
        return json.loads(r.read().decode())

def persist_refresh(new_refresh):
    lines = []
    for line in open(PROJ_ENV):
        lines.append(f"QBO_AHHO_REFRESH_TOKEN={new_refresh}\n"
                     if line.startswith("QBO_AHHO_REFRESH_TOKEN=") else line)
    open(PROJ_ENV, "w").writelines(lines)

def api_get(realm, token, path):
    url = f"{API_BASE}/{realm}/{path}"
    req = urllib.request.Request(url, headers={"Authorization": f"Bearer {token}", "Accept": "application/json"})
    with urllib.request.urlopen(req, timeout=60) as r:
        return json.loads(r.read().decode())

def api_post(realm, token, path, body):
    url = f"{API_BASE}/{realm}/{path}"
    data = json.dumps(body).encode()
    req = urllib.request.Request(url, data=data, method="POST", headers={
        "Authorization": f"Bearer {token}", "Content-Type": "application/json", "Accept": "application/json"})
    with urllib.request.urlopen(req, timeout=60) as r:
        return json.loads(r.read().decode())

def main():
    execute = "--execute" in sys.argv
    limit = None
    if "--limit" in sys.argv:
        limit = int(sys.argv[sys.argv.index("--limit") + 1])

    home, proj = load_env(HOME_ENV), load_env(PROJ_ENV)
    cid, secret = home.get("QBO_CLIENT_ID"), home.get("QBO_CLIENT_SECRET")
    realm, refresh = proj.get("QBO_AHHO_REALM_ID"), proj.get("QBO_AHHO_REFRESH_TOKEN")
    if not all([cid, secret, realm, refresh]):
        print("ERROR: missing QBO creds"); sys.exit(1)

    plan = json.load(open(PLAN))
    if limit:
        plan = plan[:limit]
    mode = "EXECUTE" if execute else "DRY-RUN"
    print(f"[{mode}] Phase-1 SKU write: {len(plan)} item(s) on realm {realm}\n")

    print("Refreshing access token...")
    tok = refresh_token(cid, secret, refresh)
    access = tok["access_token"]
    if tok.get("refresh_token") and tok["refresh_token"] != refresh:
        persist_refresh(tok["refresh_token"]); print("  rotated refresh token persisted")

    results, rollback = [], []
    ok = skip = fail = 0
    for n, p in enumerate(plan, 1):
        iid, sku, code = p["id"], p["sku"], p["code"]
        try:
            cur = api_get(realm, access, f"item/{iid}?minorversion=65").get("Item", {})
        except Exception as e:
            print(f"  [{n}] item {iid} ({code}) READ FAILED: {e}"); fail += 1
            results.append({**p, "status": "read_failed", "error": str(e)}); continue
        live_sku = (cur.get("Sku") or "").strip()
        if live_sku:
            print(f"  [{n}] item {iid} ({code}) already has SKU '{live_sku}' -> skip")
            skip += 1; results.append({**p, "status": "skip_existing", "live_sku": live_sku}); continue
        if not execute:
            print(f"  [{n}] WOULD set item {iid} ({code}) Sku = {sku}")
            results.append({**p, "status": "dry_run"}); continue
        body = {"Id": iid, "SyncToken": cur.get("SyncToken"), "sparse": True, "Sku": sku}
        try:
            resp = api_post(realm, access, "item?minorversion=65", body)
            new = resp.get("Item", {})
            print(f"  [{n}] item {iid} ({code}) Sku set -> {new.get('Sku')}")
            ok += 1
            results.append({**p, "status": "written", "new_sku": new.get("Sku"), "new_synctoken": new.get("SyncToken")})
            rollback.append({"id": iid, "code": code, "prior_sku": "", "set_sku": sku})
        except urllib.error.HTTPError as e:
            detail = e.read().decode()[:300]
            print(f"  [{n}] item {iid} ({code}) WRITE FAILED {e.code}: {detail}")
            fail += 1; results.append({**p, "status": "write_failed", "http": e.code, "error": detail})
        time.sleep(0.15)

    json.dump(results, open(RESULTS, "w"), indent=2)
    if execute and rollback:
        json.dump(rollback, open(ROLLBACK, "w"), indent=2)
    print(f"\n{mode} done. written={ok} skipped={skip} failed={fail}")
    print(f"results -> {RESULTS}" + (f"\nrollback -> {ROLLBACK}" if execute and rollback else ""))

if __name__ == "__main__":
    main()
