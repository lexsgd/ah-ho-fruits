#!/usr/bin/env python3
"""
Full read-only backup of the WooCommerce store (products, orders, customers, + key lists).
Mirrors qbo-backup.py discipline: timestamped JSON per entity + manifest with counts + sha256.
Read-only. Run before any change that touches the store or QBO product SKUs.

Usage:  python3 wc-backup.py
Output: wc-backups/<UTC-ts>/<entity>.json  +  manifest.json
"""
import os, sys, json, time, base64, hashlib, urllib.request, urllib.parse, urllib.error
from datetime import datetime, timezone

PROJ_DIR = os.path.dirname(os.path.abspath(__file__))
ENVP = os.path.join(PROJ_DIR, ".env")
UA = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36"

# entity -> endpoint (all under /wp-json/wc/v3/)
ENTITIES = {
    "products": "products",
    "product_variations_note": None,  # handled separately per variable product
    "orders": "orders",
    "customers": "customers",
    "coupons": "coupons",
    "product_categories": "products/categories",
    "tax_rates": "taxes",
    "tax_classes": "taxes/classes",
    "shipping_zones": "shipping/zones",
    "payment_gateways": "payment_gateways",
}


def load_env(path):
    env = {}
    if os.path.exists(path):
        for line in open(path):
            line = line.strip()
            if line and not line.startswith("#") and "=" in line:
                k, v = line.split("=", 1)
                env[k.strip()] = v.strip()
    return env


ENV = load_env(ENVP)
CK, CS = ENV["WC_CONSUMER_KEY"], ENV["WC_CONSUMER_SECRET"]
BASE = ENV.get("WC_BASE_URL", "https://ahhofruit.com").rstrip("/")
API = f"{BASE}/wp-json/wc/v3"
AUTH = base64.b64encode(f"{CK}:{CS}".encode()).decode()


def get(path, params=None):
    url = f"{API}/{path}"
    if params:
        url += "?" + urllib.parse.urlencode(params)
    req = urllib.request.Request(url, headers={
        "Authorization": f"Basic {AUTH}", "User-Agent": UA, "Accept": "application/json"})
    with urllib.request.urlopen(req, timeout=120) as r:
        return json.loads(r.read().decode()), dict(r.headers)


def get_all(path, extra=None):
    out, page = [], 1
    while True:
        params = {"per_page": 100, "page": page}
        if path == "orders":
            params["status"] = "any"
        if path == "customers":
            params["role"] = "all"
        if extra:
            params.update(extra)
        try:
            rows, _ = get(path, params)
        except urllib.error.HTTPError as e:
            if e.code == 400 and page > 1:
                break  # past last page
            raise
        if not isinstance(rows, list) or not rows:
            break
        out.extend(rows)
        if len(rows) < 100:
            break
        page += 1
        time.sleep(0.2)
    return out


def main():
    ts = datetime.now(timezone.utc).strftime("%Y-%m-%dT%H-%M-%SZ")
    outdir = os.path.join(PROJ_DIR, "wc-backups", ts, "full-store")
    os.makedirs(outdir, exist_ok=True)
    print(f"WooCommerce backup -> {outdir}\n")
    manifest = {"base": BASE, "timestamp_utc": ts, "entities": {}}

    for name, path in ENTITIES.items():
        if path is None:
            continue
        try:
            rows = get_all(path)
            blob = json.dumps(rows, indent=2, sort_keys=True).encode()
            sha = hashlib.sha256(blob).hexdigest()
            with open(os.path.join(outdir, f"{name}.json"), "wb") as f:
                f.write(blob)
            manifest["entities"][name] = {"count": len(rows), "sha256": sha}
            print(f"  {name:22s} {len(rows):>6d}")
        except Exception as e:
            manifest["entities"][name] = {"error": str(e)[:200]}
            print(f"  {name:22s}  SKIP ({str(e)[:80]})")

    # variations for variable products
    try:
        products = json.load(open(os.path.join(outdir, "products.json")))
        variable = [p for p in products if p.get("type") == "variable"]
        all_vars = {}
        for p in variable:
            vrows = get_all(f"products/{p['id']}/variations")
            if vrows:
                all_vars[str(p["id"])] = vrows
        if all_vars:
            blob = json.dumps(all_vars, indent=2, sort_keys=True).encode()
            open(os.path.join(outdir, "product_variations.json"), "wb").write(blob)
            manifest["entities"]["product_variations"] = {
                "variable_products": len(all_vars),
                "total_variations": sum(len(v) for v in all_vars.values()),
                "sha256": hashlib.sha256(blob).hexdigest()}
            print(f"  product_variations     {sum(len(v) for v in all_vars.values()):>6d} (across {len(all_vars)} products)")
    except Exception as e:
        print(f"  product_variations      SKIP ({str(e)[:80]})")

    with open(os.path.join(outdir, "manifest.json"), "w") as f:
        json.dump(manifest, f, indent=2)
    total = sum(v.get("count", 0) for v in manifest["entities"].values())
    print(f"\nDone. ~{total} records. Manifest: {outdir}/manifest.json")


if __name__ == "__main__":
    main()
