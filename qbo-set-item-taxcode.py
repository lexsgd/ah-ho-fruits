#!/usr/bin/env python3
"""
Bulk-change the SALES tax code on Ah Ho's QuickBooks items (e.g. G9 -> SR 9%).

Why: the migrated items use tax code "G9" (id 64), which IRAS's InvoiceNow / GST
e-invoicing system does not recognise. Michelle needs them on "SR 9%" (id 45).
Both are 9% — this changes only the code/classification, not the rate, and only
affects NEW transactions (existing invoices keep their posted tax).

Safety:
  - DRY-RUN by default. Only writes with --execute.
  - Fetches FRESH SyncTokens right before updating (a stale token -> QBO rejects
    that one item, never a wrong write).
  - Sparse update: touches ONLY SalesTaxCodeRef, nothing else on the item.
  - Writes a rollback file (every changed item id + its OLD code) so the change
    can be reverted with:  --from 45 --to 64 --execute
  - Uses the same .env / B2C app credentials as the backup + sync scripts.

Usage:
  python3 qbo-set-item-taxcode.py                      # dry-run, G9(64) -> SR 9%(45)
  python3 qbo-set-item-taxcode.py --execute            # do it
  python3 qbo-set-item-taxcode.py --from 45 --to 64 --execute   # rollback
"""
import os, sys, json, base64, argparse, urllib.parse, urllib.request, urllib.error
from datetime import datetime, timezone

HERE = os.path.dirname(os.path.abspath(__file__))
ENV_PATH = os.path.join(HERE, ".env")
QBO_BASE = "https://quickbooks.api.intuit.com/v3/company"
TOKEN_URL = "https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer"
BATCH = 25  # QBO BatchItemRequest max is 30; keep margin


def load_env(path=ENV_PATH):
    env = {}
    for line in open(path):
        s = line.strip()
        if s and not s.startswith("#") and "=" in s:
            k, v = s.split("=", 1)
            env[k.strip()] = v.strip().strip('"').strip("'")
    return env


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


class QBO:
    def __init__(self, env):
        self.realm = env["QBO_B2C_REALM_ID"]
        self.cid = env["QBO_B2C_CLIENT_ID"]
        self.csec = env["QBO_B2C_CLIENT_SECRET"]
        self.refresh = env["QBO_B2C_REFRESH_TOKEN"]
        self.access = None

    def _token(self):
        auth = base64.b64encode(f"{self.cid}:{self.csec}".encode()).decode()
        body = urllib.parse.urlencode({"grant_type": "refresh_token", "refresh_token": self.refresh})
        st, d = _req(TOKEN_URL, "POST",
                     {"Authorization": f"Basic {auth}", "Accept": "application/json",
                      "Content-Type": "application/x-www-form-urlencoded"}, body)
        if st != 200 or "access_token" not in d:
            sys.exit(f"[!] token refresh failed ({st}): {str(d)[:200]}")
        self.access = d["access_token"]

    def _h(self):
        if not self.access:
            self._token()
        return {"Authorization": f"Bearer {self.access}", "Accept": "application/json",
                "Content-Type": "application/json"}

    def query(self, q):
        url = f"{QBO_BASE}/{self.realm}/query?minorversion=70&query=" + urllib.parse.quote(q)
        st, d = _req(url, "GET", self._h())
        if st != 200:
            sys.exit(f"[!] query failed ({st}): {str(d)[:200]}")
        return d.get("QueryResponse", {})

    def batch(self, ops):
        url = f"{QBO_BASE}/{self.realm}/batch?minorversion=70"
        return _req(url, "POST", self._h(), json.dumps({"BatchItemRequest": ops}))


def fetch_items_with_sales_code(qbo, code):
    """All items whose SalesTaxCodeRef == code, with FRESH Id+SyncToken."""
    out, start = [], 1
    while True:
        page = qbo.query(f"select Id,Name,SyncToken,Type,SalesTaxCodeRef "
                         f"from Item startposition {start} maxresults 1000")
        rows = page.get("Item", [])
        if not rows:
            break
        for it in rows:
            if (it.get("SalesTaxCodeRef") or {}).get("value") == code:
                out.append(it)
        if len(rows) < 1000:
            break
        start += 1000
    return out


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--from", dest="from_code", default="64", help="current sales tax code id (default 64 = G9)")
    ap.add_argument("--to", dest="to_code", default="45", help="target sales tax code id (default 45 = SR 9%%)")
    ap.add_argument("--execute", action="store_true", help="actually write (default dry-run)")
    args = ap.parse_args()

    env = load_env()
    qbo = QBO(env)
    ts = datetime.now(timezone.utc).strftime("%Y-%m-%dT%H-%M-%SZ")
    print(f"[i] mode: {'EXECUTE (live)' if args.execute else 'DRY-RUN'}  "
          f"SalesTaxCode {args.from_code} -> {args.to_code}  realm {qbo.realm}")

    items = fetch_items_with_sales_code(qbo, args.from_code)
    print(f"[i] {len(items)} item(s) currently on sales tax code {args.from_code}")
    for it in items[:10]:
        print(f"    {it['Id']:>5}  {it.get('Name','')[:40]}")
    if len(items) > 10:
        print(f"    ... and {len(items)-10} more")
    if not items:
        print("[i] nothing to change."); return

    # rollback record — written in BOTH modes so a dry-run also documents the plan
    rb_path = os.path.join(HERE, f"taxcode-change-{ts}.{'applied' if args.execute else 'planned'}.json")
    json.dump({"from": args.from_code, "to": args.to_code, "realm": qbo.realm,
               "item_ids": [it["Id"] for it in items]}, open(rb_path, "w"), indent=2)
    print(f"[i] {'rollback' if args.execute else 'plan'} file: {rb_path}")

    if not args.execute:
        print(f"[dry-run] would update {len(items)} items. Re-run with --execute to apply. "
              f"Reverse later with: --from {args.to_code} --to {args.from_code} --execute")
        return

    ok = err = 0
    for i in range(0, len(items), BATCH):
        chunk = items[i:i+BATCH]
        ops = [{"bId": it["Id"], "operation": "update",
                "Item": {"Id": it["Id"], "SyncToken": it["SyncToken"], "sparse": True,
                         "SalesTaxCodeRef": {"value": args.to_code}}} for it in chunk]
        st, d = qbo.batch(ops)
        if st != 200:
            print(f"    [batch error {st}] {str(d)[:160]}"); err += len(chunk); continue
        for r in d.get("BatchItemResponse", []):
            if "Item" in r:
                ok += 1
            else:
                err += 1
                print(f"    [item {r.get('bId')} failed] {str(r.get('Fault'))[:140]}")
        print(f"    progress: {ok} ok, {err} failed / {len(items)}")
    print(f"[i] done: {ok} updated, {err} failed. Rollback file: {rb_path}")


if __name__ == "__main__":
    main()
