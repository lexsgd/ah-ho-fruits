#!/usr/bin/env python3
"""
Full read-only backup of AH HO FRUIT TRADING's QuickBooks Online company.

- Refreshes the OAuth access token (and persists the rotated refresh token back to .env).
- Dumps every queryable QBO entity to timestamped JSON with full pagination.
- Writes a manifest.json with per-entity counts + SHA-256 checksums for integrity.

Reusable + idempotent + READ ONLY. Re-run before any risky write operation.

Usage:  python3 qbo-backup.py
Output: qbo-backups/<UTC-timestamp>/<Entity>.json  +  manifest.json
"""
import os, sys, json, time, base64, hashlib, urllib.request, urllib.parse, urllib.error
from datetime import datetime, timezone

PROJ_DIR = os.path.dirname(os.path.abspath(__file__))
HOME_ENV = os.path.expanduser("~/.env")
PROJ_ENV = os.path.join(PROJ_DIR, ".env")
TOKEN_URL = "https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer"
API_BASE = "https://quickbooks.api.intuit.com/v3/company"

# All standard QBO queryable entities. Singletons (CompanyInfo, Preferences) handled too.
ENTITIES = [
    "Account", "Customer", "Vendor", "Employee", "Item",
    "Invoice", "Bill", "BillPayment", "Payment", "CreditMemo",
    "SalesReceipt", "RefundReceipt", "Estimate", "PurchaseOrder", "Purchase",
    "Deposit", "Transfer", "JournalEntry", "VendorCredit", "CreditCardPayment",
    "TaxCode", "TaxRate", "TaxAgency", "PaymentMethod", "Term",
    "Class", "Department", "Attachable", "Budget", "TimeActivity",
    "ReimburseCharge", "JournalCode", "CompanyCurrency",
    "CompanyInfo", "Preferences",
    # NOTE: ExchangeRate excluded — QBO auto-generates ~532k FX-rate rows (not Ah Ho data); bloated backup to 285MB.
]


def load_env(path):
    env = {}
    if os.path.exists(path):
        with open(path) as f:
            for line in f:
                line = line.strip()
                if not line or line.startswith("#") or "=" not in line:
                    continue
                k, v = line.split("=", 1)
                env[k.strip()] = v.strip()
    return env


def http_post_form(url, fields, headers):
    data = urllib.parse.urlencode(fields).encode()
    req = urllib.request.Request(url, data=data, headers=headers, method="POST")
    with urllib.request.urlopen(req, timeout=60) as r:
        return json.loads(r.read().decode())


def http_get(url, token):
    req = urllib.request.Request(url, headers={
        "Authorization": f"Bearer {token}",
        "Accept": "application/json",
    })
    with urllib.request.urlopen(req, timeout=120) as r:
        return json.loads(r.read().decode())


def refresh_token(client_id, client_secret, refresh):
    basic = base64.b64encode(f"{client_id}:{client_secret}".encode()).decode()
    return http_post_form(TOKEN_URL,
        {"grant_type": "refresh_token", "refresh_token": refresh},
        {"Authorization": f"Basic {basic}",
         "Content-Type": "application/x-www-form-urlencoded",
         "Accept": "application/json"})


def persist_refresh(new_refresh):
    """Refresh tokens rotate — write the new one back so the next run still works."""
    lines = []
    found = False
    with open(PROJ_ENV) as f:
        for line in f:
            if line.startswith("QBO_AHHO_REFRESH_TOKEN="):
                lines.append(f"QBO_AHHO_REFRESH_TOKEN={new_refresh}\n")
                found = True
            else:
                lines.append(line)
    if found:
        with open(PROJ_ENV, "w") as f:
            f.writelines(lines)


def query_all(realm, token, entity):
    """Paginated SELECT * for an entity. Returns list of records."""
    out = []
    start = 1
    page = 1000
    while True:
        q = f"SELECT * FROM {entity} STARTPOSITION {start} MAXRESULTS {page}"
        url = f"{API_BASE}/{realm}/query?query={urllib.parse.quote(q)}&minorversion=65"
        resp = http_get(url, token)
        qr = resp.get("QueryResponse", {})
        rows = qr.get(entity, [])
        if not rows:
            break
        out.extend(rows)
        if len(rows) < page:
            break
        start += page
        time.sleep(0.2)  # gentle on rate limits
    return out


def get_singleton(realm, token, entity):
    if entity == "CompanyInfo":
        url = f"{API_BASE}/{realm}/companyinfo/{realm}?minorversion=65"
        return [http_get(url, token).get("CompanyInfo", {})]
    if entity == "Preferences":
        url = f"{API_BASE}/{realm}/preferences?minorversion=65"
        return [http_get(url, token).get("Preferences", {})]
    return query_all(realm, token, entity)


def main():
    home = load_env(HOME_ENV)
    proj = load_env(PROJ_ENV)
    client_id = home.get("QBO_CLIENT_ID")
    client_secret = home.get("QBO_CLIENT_SECRET")
    realm = proj.get("QBO_AHHO_REALM_ID")
    refresh = proj.get("QBO_AHHO_REFRESH_TOKEN")
    if not all([client_id, client_secret, realm, refresh]):
        print("ERROR: missing QBO_CLIENT_ID/SECRET (~/.env) or QBO_AHHO_REALM_ID/REFRESH_TOKEN (project .env)")
        sys.exit(1)

    print("Refreshing access token...")
    tok = refresh_token(client_id, client_secret, refresh)
    access = tok["access_token"]
    new_refresh = tok.get("refresh_token")
    if new_refresh and new_refresh != refresh:
        persist_refresh(new_refresh)
        print("  rotated refresh token persisted to .env")

    ts = datetime.now(timezone.utc).strftime("%Y-%m-%dT%H-%M-%SZ")
    outdir = os.path.join(PROJ_DIR, "qbo-backups", ts)
    os.makedirs(outdir, exist_ok=True)
    print(f"Backing up realm {realm} -> {outdir}\n")

    manifest = {"realm_id": realm, "timestamp_utc": ts, "entities": {}}
    total = 0
    for ent in ENTITIES:
        try:
            rows = get_singleton(realm, token=access, entity=ent)
            blob = json.dumps(rows, indent=2, sort_keys=True).encode()
            sha = hashlib.sha256(blob).hexdigest()
            with open(os.path.join(outdir, f"{ent}.json"), "wb") as f:
                f.write(blob)
            manifest["entities"][ent] = {"count": len(rows), "sha256": sha}
            total += len(rows)
            print(f"  {ent:18s} {len(rows):>7d}")
        except urllib.error.HTTPError as e:
            body = e.read().decode()[:200]
            manifest["entities"][ent] = {"error": f"HTTP {e.code}: {body}"}
            print(f"  {ent:18s}  SKIP ({e.code})")
        except Exception as e:
            manifest["entities"][ent] = {"error": str(e)}
            print(f"  {ent:18s}  SKIP ({e})")

    manifest["total_records"] = total
    with open(os.path.join(outdir, "manifest.json"), "w") as f:
        json.dump(manifest, f, indent=2)

    print(f"\nDone. {total} records across {len([e for e in manifest['entities'].values() if 'count' in e])} entities.")
    print(f"Manifest: {os.path.join(outdir, 'manifest.json')}")


if __name__ == "__main__":
    main()
