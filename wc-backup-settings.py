#!/usr/bin/env python3
"""
Supplement the WooCommerce backup with full SETTINGS + STATE:
- every settings group and its option values (general, products, tax, shipping, checkout, account, email, advanced, integration...)
- system_status (active plugins + versions, theme, WP/PHP/DB, environment, pages, security)
- webhooks, shipping_methods, data (countries/currencies/continents)

Writes into an existing wc-backups/<ts>/full-store dir (pass as arg, else latest).
Read-only. Sends a Chrome UA (Vodien WAF 403s non-browser agents).
"""
import os, sys, json, base64, hashlib, urllib.parse, urllib.request, urllib.error

HERE = os.path.dirname(os.path.abspath(__file__))
UA = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0 Safari/537.36"


def load_env(path):
    env = {}
    if os.path.exists(path):
        for line in open(path):
            s = line.strip()
            if s and not s.startswith("#") and "=" in s:
                k, v = s.split("=", 1); env[k.strip()] = v.strip().strip('"').strip("'")
    return env


def main():
    env = load_env(os.path.join(HERE, ".env"))
    base = env["WC_BASE_URL"].rstrip("/")
    api = f"{base}/wp-json/wc/v3"
    auth = base64.b64encode(f"{env['WC_CONSUMER_KEY']}:{env['WC_CONSUMER_SECRET']}".encode()).decode()

    if len(sys.argv) > 1:
        outdir = sys.argv[1]
    else:
        wcb = os.path.join(HERE, "wc-backups")
        latest = sorted(os.listdir(wcb))[-1]
        outdir = os.path.join(wcb, latest, "full-store")
    os.makedirs(outdir, exist_ok=True)
    print(f"Supplementing: {outdir}\n")

    def get(path, params=None):
        url = f"{api}/{path}"
        if params:
            url += "?" + urllib.parse.urlencode(params)
        req = urllib.request.Request(url, headers={
            "Authorization": f"Basic {auth}", "Accept": "application/json", "User-Agent": UA})
        with urllib.request.urlopen(req, timeout=90) as r:
            return json.loads(r.read().decode())

    def save(name, obj):
        blob = json.dumps(obj, indent=2, sort_keys=True, ensure_ascii=False).encode()
        open(os.path.join(outdir, f"{name}.json"), "wb").write(blob)
        n = len(obj) if isinstance(obj, list) else 1
        print(f"  {name:26s} {n:>5d}  (sha {hashlib.sha256(blob).hexdigest()[:12]})")

    # 1) all settings groups + their option values
    try:
        groups = get("settings")
        save("settings_groups", groups)
        all_settings = {}
        for g in groups:
            gid = g.get("id")
            if not gid:
                continue
            try:
                all_settings[gid] = get(f"settings/{gid}")
            except Exception as e:
                all_settings[gid] = {"_error": str(e)}
        save("settings_values", all_settings)
    except Exception as e:
        print(f"  settings FAILED: {e}")

    # 2) system status = plugins, versions, db, environment, theme, pages, security
    for ep in ["system_status", "webhooks", "shipping_methods",
               "data/countries", "data/currencies", "data/continents"]:
        try:
            save(ep.replace("/", "_"), get(ep, {"per_page": 100} if ep == "webhooks" else None))
        except urllib.error.HTTPError as e:
            print(f"  {ep:26s} SKIP HTTP {e.code}")
        except Exception as e:
            print(f"  {ep:26s} SKIP {e}")

    print("\nDone. Settings + state supplement written.")


if __name__ == "__main__":
    main()
