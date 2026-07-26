#!/usr/bin/env python3
"""One-off: normalize punctuation in item 5021.1 (AHF-PINEAPPLE-7) Name to prove the fix."""
import os, sys, re, json, base64, urllib.parse, urllib.request, urllib.error

HERE = "/Users/lexnaweiming/ah-ho-fruits"
ENV = os.path.join(HERE, ".env")
QBO_BASE = "https://quickbooks.api.intuit.com/v3/company"
TOKEN_URL = "https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer"

env = {}
for line in open(ENV):
    s = line.strip()
    if s and not s.startswith("#") and "=" in s:
        k, v = s.split("=", 1); env[k.strip()] = v.strip().strip('"').strip("'")

def req(url, method="GET", headers=None, data=None):
    r = urllib.request.Request(url, method=method, headers=headers or {}, data=data.encode() if isinstance(data, str) else data)
    try:
        with urllib.request.urlopen(r, timeout=60) as resp:
            return resp.status, json.loads(resp.read().decode() or "{}")
    except urllib.error.HTTPError as e:
        b = e.read().decode()
        try: b = json.loads(b)
        except Exception: pass
        return e.code, b

auth = base64.b64encode(f"{env['QBO_B2C_CLIENT_ID']}:{env['QBO_B2C_CLIENT_SECRET']}".encode()).decode()
st, tok = req(TOKEN_URL, "POST", {"Authorization": f"Basic {auth}", "Accept": "application/json",
              "Content-Type": "application/x-www-form-urlencoded"},
              urllib.parse.urlencode({"grant_type": "refresh_token", "refresh_token": env["QBO_B2C_REFRESH_TOKEN"]}))
access = tok["access_token"]
realm = env["QBO_B2C_REALM_ID"]
H = {"Authorization": f"Bearer {access}", "Accept": "application/json", "Content-Type": "application/json"}

q = "select * from Item where Sku = 'AHF-PINEAPPLE-7'"
st, d = req(f"{QBO_BASE}/{realm}/query?minorversion=70&query=" + urllib.parse.quote(q), "GET", H)
items = d.get("QueryResponse", {}).get("Item", [])
if not items:
    print("not found"); sys.exit(1)
it = items[0]
print("current Name:", repr(it.get("Name")))
new = re.sub(r'\s+', ' ', re.sub(r'[".,()\[\]/]', ' ', it["Name"])).strip()[:100]
print("new Name    :", repr(new))
payload = dict(it); payload["Name"] = new
st, d = req(f"{QBO_BASE}/{realm}/item?minorversion=70", "POST", H, json.dumps(payload))
print("status:", st, "->", repr(d.get("Item", {}).get("Name")) if st == 200 else str(d)[:200])
