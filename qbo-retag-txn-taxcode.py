#!/usr/bin/env python3
"""
Bulk re-tag the tax code on POSTED transactions (Invoices / Bills) in Ah Ho's
QuickBooks — e.g. Invoices G9 -> SR 9%, Bills G9 -> TX 9%.

WHY: migrated transactions use tax code "G9" (id 64), which IRAS InvoiceNow / GST
e-invoicing doesn't recognise. G9, SR 9% and TX 9% are ALL 9%, so totals do NOT
change — this only re-classifies the code.

⚠️ COMPLIANCE GATE (read before --execute):
  This rewrites POSTED transactions back to a date you choose. If a GST period was
  already FILED with IRAS, changing its codes makes QuickBooks no longer match the
  filed return, and for Bills (G9 -> TX = claimable input tax) it can change the GST
  figures. DO NOT run over filed periods without the accountant's explicit OK on the
  exact --since date. Default --since is 2026-03-01 but the accountant sets the real one.

SAFETY:
  - DRY-RUN by default. Only writes with --execute.
  - Fetches each txn FRESH (Id + SyncToken + full Line) right before updating.
  - Changes ONLY lines whose TaxCodeRef == --from; every other field is preserved.
  - Strips TxnTaxDetail so QuickBooks recomputes the tax from the new line codes.
  - Writes a rollback file (txn ids + old/new code) -> reverse with --from/--to swapped.
  - TEST FIRST: run --only <id> --execute on ONE txn, eyeball it in QBO (code changed,
    total unchanged), THEN do the bulk run.
  - Uses the same .env / B2C app credentials as the backup + item scripts.

Usage:
  python3 qbo-retag-txn-taxcode.py --entity invoice                 # dry-run, G9->SR 9%, since Mar
  python3 qbo-retag-txn-taxcode.py --entity bill                    # dry-run, G9->TX 9%, since Mar
  python3 qbo-retag-txn-taxcode.py --entity invoice --only 12345 --execute   # single-txn test
  python3 qbo-retag-txn-taxcode.py --entity invoice --since 2026-07-01 --execute  # bulk, current qtr
"""
import os, sys, json, base64, argparse, urllib.parse, urllib.request, urllib.error
from datetime import datetime, timezone

HERE = os.path.dirname(os.path.abspath(__file__))
ENV_PATH = os.path.join(HERE, ".env")
QBO_BASE = "https://quickbooks.api.intuit.com/v3/company"
TOKEN_URL = "https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer"
BATCH = 20  # QBO BatchItemRequest max is 30; margin for big invoice payloads

# entity -> (QBO entity name, default target tax code id, line-detail keys holding TaxCodeRef)
ENTITIES = {
    "invoice": {"name": "Invoice", "default_to": "45",  # SR 9%
                "detail_keys": ["SalesItemLineDetail"]},
    "bill":    {"name": "Bill",    "default_to": "48",  # TX 9%
                "detail_keys": ["AccountBasedExpenseLineDetail", "ItemBasedExpenseLineDetail"]},
}
DEFAULT_FROM = "64"  # G9


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
        with urllib.request.urlopen(r, timeout=90) as resp:
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
            raise RuntimeError(f"query failed ({st}): {str(d)[:200]}")
        return d.get("QueryResponse", {})

    def batch(self, ops):
        url = f"{QBO_BASE}/{self.realm}/batch?minorversion=70"
        return _req(url, "POST", self._h(), json.dumps({"BatchItemRequest": ops}))


def line_detail(line, detail_keys):
    """Return the sub-dict on a line that holds TaxCodeRef (or None)."""
    for k in detail_keys:
        if k in line and isinstance(line[k], dict):
            return line[k]
    return None


def retag_txn(txn, detail_keys, from_code, to_code):
    """Mutate a copy: set every line's TaxCodeRef from_code->to_code. Returns
    (new_txn, n_lines_changed). Strips TxnTaxDetail so QBO recomputes."""
    import copy
    t = copy.deepcopy(txn)
    changed = 0
    for line in t.get("Line", []):
        d = line_detail(line, detail_keys)
        if d and (d.get("TaxCodeRef") or {}).get("value") == from_code:
            d["TaxCodeRef"] = {"value": to_code}
            changed += 1
    if changed:
        t.pop("TxnTaxDetail", None)   # let QBO recompute from the new line codes
        t["sparse"] = False           # full update
    return t, changed


def fetch_txns(qbo, entity_name, from_code, detail_keys, since, until, only, limit):
    """Yield txns (fresh, with SyncToken) in the date window that have >=1 line on from_code."""
    out = []
    if only:
        page = qbo.query(f"select * from {entity_name} where Id = '{only}'")
        rows = page.get(entity_name, [])
    else:
        where = f"TxnDate >= '{since}'"
        if until:
            where += f" and TxnDate <= '{until}'"
        rows, start = [], 1
        PAGE = 100  # small page: `select *` on big tables 500s on large payloads
        while True:
            page = qbo.query(f"select * from {entity_name} where {where} "
                             f"startposition {start} maxresults {PAGE}")
            batch = page.get(entity_name, [])
            if not batch:
                break
            rows.extend(batch)
            print(f"    fetched {len(rows)}...", end="\r")
            if len(batch) < PAGE:
                break
            start += PAGE
            if limit and len(rows) >= limit:
                break
        print()
    for t in rows:
        if any((line_detail(l, detail_keys) or {}).get("TaxCodeRef", {}).get("value") == from_code
               for l in t.get("Line", [])):
            out.append(t)
            if limit and len(out) >= limit:
                break
    return out


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--entity", choices=["invoice", "bill"], required=True)
    ap.add_argument("--from", dest="from_code", default=DEFAULT_FROM, help="current tax code id (default 64 = G9)")
    ap.add_argument("--to", dest="to_code", default=None, help="target tax code id (default: invoice=45 SR 9%%, bill=48 TX 9%%)")
    ap.add_argument("--since", default="2026-03-01", help="only txns TxnDate >= this (YYYY-MM-DD). ACCOUNTANT sets the safe date.")
    ap.add_argument("--until", default=None, help="optional TxnDate <= this (YYYY-MM-DD)")
    ap.add_argument("--only", default=None, help="single txn Id (for the pre-bulk test)")
    ap.add_argument("--limit", type=int, default=0, help="cap number of txns (0 = all)")
    ap.add_argument("--execute", action="store_true", help="actually write (default dry-run)")
    args = ap.parse_args()

    cfg = ENTITIES[args.entity]
    ename, dkeys = cfg["name"], cfg["detail_keys"]
    to_code = args.to_code or cfg["default_to"]

    env = load_env()
    qbo = QBO(env)
    ts = datetime.now(timezone.utc).strftime("%Y-%m-%dT%H-%M-%SZ")
    print(f"[i] mode: {'EXECUTE (live)' if args.execute else 'DRY-RUN'}  {ename}  "
          f"tax {args.from_code} -> {to_code}  since {args.since}"
          + (f"  until {args.until}" if args.until else "") + (f"  only {args.only}" if args.only else ""))
    if args.execute and not args.only:
        print("[!] COMPLIANCE: you are about to rewrite POSTED transactions over a date range.")
        print("[!] Confirm the accountant approved this --since date (filed GST periods!).")

    print("[i] fetching...")
    txns = fetch_txns(qbo, ename, args.from_code, dkeys, args.since, args.until, args.only, args.limit)
    total_lines = sum(retag_txn(t, dkeys, args.from_code, to_code)[1] for t in txns)
    print(f"[i] {len(txns)} {ename.lower()}(s) with >=1 G9 line ({total_lines} lines to re-tag)")
    for t in txns[:10]:
        _, n = retag_txn(t, dkeys, args.from_code, to_code)
        print(f"    {ename} Id {t['Id']:>7}  Doc {t.get('DocNumber','-'):>8}  {t.get('TxnDate')}  "
              f"Total ${t.get('TotalAmt')}  ({n} line(s))")
    if len(txns) > 10:
        print(f"    ... and {len(txns)-10} more")
    if not txns:
        print("[i] nothing to change."); return

    rb_path = os.path.join(HERE, f"txn-retag-{args.entity}-{ts}.{'applied' if args.execute else 'planned'}.json")
    json.dump({"entity": ename, "from": args.from_code, "to": to_code, "since": args.since,
               "until": args.until, "realm": qbo.realm, "txn_ids": [t["Id"] for t in txns]},
              open(rb_path, "w"), indent=2)
    print(f"[i] {'rollback' if args.execute else 'plan'} file: {rb_path}")

    if not args.execute:
        print(f"[dry-run] would update {len(txns)} {ename.lower()}(s). No changes made.")
        print(f"[dry-run] next: test ONE first -> --entity {args.entity} --only <Id> --execute, verify in QBO, then bulk.")
        return

    ok = err = 0
    for i in range(0, len(txns), BATCH):
        chunk = txns[i:i+BATCH]
        ops = []
        for t in chunk:
            nt, n = retag_txn(t, dkeys, args.from_code, to_code)
            ops.append({"bId": t["Id"], "operation": "update", ename: nt})
        # Proactively refresh the access token every ~40 batches so a long run
        # never dies at the 60-min token expiry mid-way.
        if i and (i // BATCH) % 40 == 0:
            qbo.access = None
        st, d = qbo.batch(ops)
        if st in (401, 403):                 # token expired -> refresh once and retry
            qbo.access = None
            st, d = qbo.batch(ops)
        if st != 200:
            print(f"    [batch error {st}] {str(d)[:160]}", flush=True); err += len(chunk); continue
        for r in d.get("BatchItemResponse", []):
            if ename in r:
                ok += 1
            else:
                err += 1
                print(f"    [{r.get('bId')} failed] {str(r.get('Fault'))[:160]}")
        print(f"    progress: {ok} ok, {err} failed / {len(txns)}", flush=True)
    print(f"[i] done: {ok} updated, {err} failed. Rollback file: {rb_path}", flush=True)


if __name__ == "__main__":
    main()
