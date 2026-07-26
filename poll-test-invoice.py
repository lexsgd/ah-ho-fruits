#!/usr/bin/env python3
"""Poll QBO until the connector creates the invoice/sales-receipt for WC test order #5003.
Refreshes the access token each cycle (rotated refresh token persisted) so it survives >1h."""
import os, json, base64, time, urllib.request, urllib.parse, urllib.error

PROJ = os.path.dirname(os.path.abspath(__file__))
ENVP = os.path.join(PROJ, ".env")

def le(p):
    e = {}
    for l in open(p):
        if l.strip() and not l.startswith("#") and "=" in l:
            k, v = l.split("=", 1); e[k.strip()] = v.strip()
    return e

H = le(os.path.expanduser("~/.env"))
BASIC = base64.b64encode((H["QBO_CLIENT_ID"] + ":" + H["QBO_CLIENT_SECRET"]).encode()).decode()

def refresh():
    pr = le(ENVP)
    d = urllib.parse.urlencode({"grant_type": "refresh_token", "refresh_token": pr["QBO_AHHO_REFRESH_TOKEN"]}).encode()
    tok = json.loads(urllib.request.urlopen(urllib.request.Request(
        "https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer", data=d, method="POST",
        headers={"Authorization": "Basic " + BASIC, "Content-Type": "application/x-www-form-urlencoded", "Accept": "application/json"}), timeout=60).read())
    nr = tok.get("refresh_token")
    if nr and nr != pr["QBO_AHHO_REFRESH_TOKEN"]:
        with open(ENVP) as f:                      # read FULLY first (avoid truncate-before-read wipe)
            lines = f.readlines()
        if not any(l.startswith("QBO_AHHO_REFRESH_TOKEN=") for l in lines):
            raise SystemExit("ABORT: QBO_AHHO_REFRESH_TOKEN missing from .env — refusing to rewrite")
        new_lines = [("QBO_AHHO_REFRESH_TOKEN=" + nr + "\n") if l.startswith("QBO_AHHO_REFRESH_TOKEN=") else l for l in lines]
        tmp = ENVP + ".tmp"
        with open(tmp, "w") as f:                   # atomic: write temp then replace
            f.writelines(new_lines)
        os.replace(tmp, ENVP)
    return tok["access_token"], pr["QBO_AHHO_REALM_ID"]

def q(a, realm, query):
    url = f"https://quickbooks.api.intuit.com/v3/company/{realm}/query?query={urllib.parse.quote(query)}&minorversion=65"
    return json.loads(urllib.request.urlopen(urllib.request.Request(url, headers={"Authorization": "Bearer " + a, "Accept": "application/json"}), timeout=120).read()).get("QueryResponse", {})

SINCE = "2026-06-17T19:35:00+08:00"
deadline = time.time() + 180 * 60
attempt = 0
while time.time() < deadline:
    attempt += 1
    try:
        a, realm = refresh()
        hits = []
        for ent in ["Invoice", "SalesReceipt"]:
            for r in q(a, realm, f"SELECT * FROM {ent} WHERE Metadata.CreateTime >= '{SINCE}' ORDER BY Metadata.CreateTime DESC").get(ent, []):
                cust = str((r.get("CustomerRef") or {}).get("name", "")).lower()
                if "zztest" in cust or "qbo connector" in cust or float(r.get("TotalAmt", -1)) == 0.50:
                    hits.append((ent, r))
        if hits:
            print("FOUND after %d polls:" % attempt)
            for ent, r in hits:
                print(json.dumps({"entity": ent, "Id": r.get("Id"), "DocNumber": r.get("DocNumber"),
                                  "Customer": (r.get("CustomerRef") or {}).get("name"), "TotalAmt": r.get("TotalAmt"),
                                  "TxnDate": r.get("TxnDate"), "Balance": r.get("Balance"), "PrivateNote": r.get("PrivateNote"),
                                  "Lines": [{"desc": li.get("Description"),
                                              "item": (li.get("SalesItemLineDetail") or {}).get("ItemRef", {}).get("name"),
                                              "amt": li.get("Amount"),
                                              "taxcode": (li.get("SalesItemLineDetail") or {}).get("TaxCodeRef", {}).get("value")}
                                             for li in r.get("Line", []) if li.get("DetailType") == "SalesItemLineDetail"]}, indent=2))
            json.dump([{"entity": e, "id": r.get("Id")} for e, r in hits], open("/Users/lexnaweiming/Test/test-invoice-found.json", "w"))
            raise SystemExit(0)
    except urllib.error.HTTPError as e:
        print("poll %d transient error %s; retrying next cycle" % (attempt, e.code))
    time.sleep(180)
print("TIMEOUT: no QBO invoice for order #5003 after 90 min — connector sync schedule is longer; next cycle or manual sync needed.")
raise SystemExit(1)
