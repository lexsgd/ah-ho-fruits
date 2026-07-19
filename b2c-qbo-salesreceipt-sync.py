#!/usr/bin/env python3
"""
Ah Ho Fruit — B2C WooCommerce -> QuickBooks Sales Receipt sync.

Reflect each PAID residential (B2C) website order in QuickBooks as a Sales
Receipt (customer + fruit line items + price + GST), so Ah Ho can see
"which fruits sold, at what price, to which customer" and reconcile against
the Stripe payouts. B2B (wholesale, status processing-b2b) is handled
separately by the CRM Perks plugin as invoices — this EXCLUDES those.

Uses the dedicated Intuit app "Ah Ho WooCommerce B2C Sync" (production), so it
never touches the B2B plugin's QuickBooks connection.

  DRY-RUN by default. Only writes to QuickBooks with --execute.
  Idempotent: skips orders whose SalesReceipt (DocNumber woo-<id>) already exists.
  Atomic token persistence (a refresh bug can never blank .env).
"""
import os, sys, json, time, base64, argparse, csv, tempfile, urllib.parse, urllib.request, urllib.error
from decimal import Decimal, ROUND_HALF_UP

HERE = os.path.dirname(os.path.abspath(__file__))
ENV_PATH = os.path.join(HERE, ".env")
# WC-SKU -> QBO-item-code crosswalk. Ah Ho's QBO catalogue is deliberately coarser
# than WooCommerce (many WC SKUs -> one QBO item, e.g. 125g + 200g blueberry), so a
# QBO item's Sku field can only hold ONE code. A direct WC-SKU==QBO-Sku lookup misses
# ~75% of B2C order lines; this file translates WC SKU -> the QBO item's code first.
CROSSWALK_PATH = os.environ.get(
    "AHHO_CROSSWALK", os.path.join(HERE, "QBO-WC-Crosswalk-FINAL-2026-06-16.csv"))

GST_RATE = Decimal("0.09")
SR_TAXCODE_ID = "45"               # QBO TaxCode "SR 9%" (rate SR-09; id 32 "9% SR"/SR-9 errors on calc)
DEPOSIT_ACCOUNT_ID = "31"          # UOB (Bank) — per Michelle: Stripe payouts go to UOB; she reconciles manually
B2C_STATUSES = {"processing", "completed"}   # residential
B2B_STATUSES = {"processing-b2b"}            # NEVER touched here
QBO_BASE = "https://quickbooks.api.intuit.com/v3/company"
TOKEN_URL = "https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer"


# ---------- env ----------
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
    """Atomically update one key in .env (never truncate-before-read)."""
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


def load_crosswalk(path=CROSSWALK_PATH):
    """Return {WC SKU: QBO code}. QBO code is the value stored in the QBO item's
    Sku field. Rows with a blank WC SKU or blank QBO code are skipped (a blank
    mapping would be worse than none — it must fall through to a direct match /
    hard-stop). Missing file -> empty map -> behaves exactly like before (direct
    match only), so this is safe to ship even if the CSV isn't present."""
    m = {}
    if not os.path.exists(path):
        return m
    with open(path, newline="", encoding="utf-8-sig") as f:
        for row in csv.DictReader(f):
            wc = (row.get("WC SKU") or "").strip()
            code = (row.get("QBO code") or "").strip()
            if wc and code:
                m[wc] = code
    return m


def money(x):
    return Decimal(str(x)).quantize(Decimal("0.01"), rounding=ROUND_HALF_UP)


# ---------- HTTP ----------
def _req(url, method="GET", headers=None, data=None):
    r = urllib.request.Request(url, method=method, headers=headers or {},
                               data=data.encode() if isinstance(data, str) else data)
    try:
        with urllib.request.urlopen(r, timeout=40) as resp:
            return resp.status, json.loads(resp.read().decode() or "{}")
    except urllib.error.HTTPError as e:
        body = e.read().decode()
        try: body = json.loads(body)
        except Exception: pass
        return e.code, body


class QBO:
    def __init__(self, env, crosswalk=None):
        self.env = env
        self.realm = env["QBO_B2C_REALM_ID"]
        self.cid = env["QBO_B2C_CLIENT_ID"]
        self.csec = env["QBO_B2C_CLIENT_SECRET"]
        self.refresh = env["QBO_B2C_REFRESH_TOKEN"]
        self.access = None
        self._item_cache = {}
        self.crosswalk = crosswalk or {}

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
        if new_rt and new_rt != self.refresh:          # rotated -> persist atomically
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

    def post(self, entity, payload):
        url = f"{QBO_BASE}/{self.realm}/{entity}?minorversion=70"
        return _req(url, "POST", self._h(), json.dumps(payload))

    # --- lookups ---
    def income_account_id(self):
        qr = self.query("select Id,Name from Account where AccountType = 'Income' maxresults 5")
        accts = qr.get("Account", [])
        for a in accts:
            if "sale" in a.get("Name", "").lower():
                return a["Id"]
        return accts[0]["Id"] if accts else None

    def _item_id_by_qbo_sku(self, code):
        code_esc = code.replace("'", "\\'")
        qr = self.query(f"select Id,Name from Item where Sku = '{code_esc}'")
        items = qr.get("Item", [])
        return items[0]["Id"] if items else None

    def item_by_sku(self, sku):
        # DIRECT-FIRST, crosswalk-FALLBACK. Try the raw WC SKU against QBO's Sku
        # field first (phase-1 one-to-one items were written this way). Only if
        # that misses do we translate WC SKU -> QBO code via the crosswalk and try
        # again. Direct-first guarantees this never breaks a match that works today;
        # the crosswalk leg only ADDS matches once the coarse many-to-one QBO items
        # actually carry their code in a queryable field (they do NOT yet — see
        # AhHo-QBO-Mapping-Confirm doc; that is the remaining go-live blocker).
        if sku in self._item_cache:
            return self._item_cache[sku]
        rid = self._item_id_by_qbo_sku(sku)
        if not rid:
            code = self.crosswalk.get(sku)
            if code and code != sku:
                rid = self._item_id_by_qbo_sku(code)
        self._item_cache[sku] = rid
        return rid

    def service_item_id(self, name):
        key = "svc:" + name
        if key in self._item_cache:
            return self._item_cache[key]
        qr = self.query(f"select Id from Item where Name = '{name}'")
        items = qr.get("Item", [])
        if items:
            self._item_cache[key] = items[0]["Id"]; return items[0]["Id"]
        inc = self.income_account_id()
        st, d = self.post("item", {"Name": name, "Type": "Service",
                                   "IncomeAccountRef": {"value": inc}})
        rid = d["Item"]["Id"] if st == 200 else None
        self._item_cache[key] = rid
        return rid

    def create_item(self, sku, name):
        inc = self.income_account_id()
        payload = {"Name": (name or sku)[:100], "Sku": sku, "Type": "NonInventory",
                   "IncomeAccountRef": {"value": inc}}
        st, d = self.post("item", payload)
        if st == 200:
            rid = d["Item"]["Id"]; self._item_cache[sku] = rid; return rid, None
        return None, f"create item failed ({st}): {str(d)[:160]}"

    def customer_by_email(self, email):
        if not email:
            return None
        e = email.replace("'", "\\'")
        qr = self.query(f"select Id,DisplayName from Customer where PrimaryEmailAddr = '{e}'")
        c = qr.get("Customer", [])
        return c[0]["Id"] if c else None

    def create_customer(self, name, email):
        display = (name or email or "WC Customer")[:100]
        payload = {"DisplayName": display}
        if email:
            payload["PrimaryEmailAddr"] = {"Address": email}
        st, d = self.post("customer", payload)
        if st == 200:
            return d["Customer"]["Id"], None
        # duplicate DisplayName -> append email to disambiguate
        if "Duplicate" in str(d) and email:
            payload["DisplayName"] = f"{display} ({email})"[:100]
            st, d = self.post("customer", payload)
            if st == 200:
                return d["Customer"]["Id"], None
        return None, f"create customer failed ({st}): {str(d)[:160]}"

    def txn_exists(self, entity, docnumber):
        """entity: 'Invoice' or 'SalesReceipt'. Idempotency check by DocNumber."""
        qr = self.query(f"select Id from {entity} where DocNumber = '{docnumber}'")
        return bool(qr.get(entity))


# ---------- WooCommerce ----------
def wc_orders(env, status, limit):
    base = env["WC_BASE_URL"].rstrip("/")
    qs = urllib.parse.urlencode({"per_page": limit, "status": status,
                                 "orderby": "date", "order": "desc"})
    auth = base64.b64encode(f"{env['WC_CONSUMER_KEY']}:{env['WC_CONSUMER_SECRET']}".encode()).decode()
    st, d = _req(f"{base}/wp-json/wc/v3/orders?{qs}", "GET",
                 {"Authorization": f"Basic {auth}", "User-Agent": "ahho-qbo-sync/1.0"})
    if st != 200:
        sys.exit(f"[!] WC fetch failed ({st}): {str(d)[:160]}")
    return d


# ---------- build + sync ----------
def net_of_gst(gross):
    """WC prices are GST-inclusive; this QBO company taxes line amounts EXCLUSIVELY
    (GlobalTaxCalculation=TaxInclusive is ignored by its tax engine — verified live
    2026-07-12: receipt woo-5134 posted $87.20 instead of $80). So we send the NET;
    QBO adds 9% SR back and the receipt total == the gross the customer paid."""
    return money(gross / (Decimal("1") + GST_RATE))


def build_lines(order, qbo, execute):
    """Return (lines, warnings, unmapped, built_net).

    Line Amount = NET (GST-excluded). The SalesReceipt posts with
    GlobalTaxCalculation=TaxExcluded, so QBO adds 9% SR back and TotalAmt == the
    GST-inclusive gross the customer paid.

    `unmapped` lists any order line whose product could not be matched to a QBO
    item. Unmapped products are NEVER silently auto-created; the caller blocks the
    whole order so we never post an under-stated receipt.
    `built_net` is the Decimal sum of the net line amounts (QBO's taxable base)."""
    lines, warn, unmapped = [], [], []
    built_net = Decimal("0")

    def add(item_id, desc, gross, qty=None):
        nonlocal built_net
        net = net_of_gst(gross)
        built_net += net
        lines.append({"DetailType": "SalesItemLineDetail", "Amount": float(net),
                      "Description": desc,
                      "SalesItemLineDetail": {"ItemRef": {"value": item_id},
                                              **({"Qty": qty} if qty else {}),
                                              "TaxCodeRef": {"value": SR_TAXCODE_ID}}})

    for li in order.get("line_items", []):
        sku = (li.get("sku") or "").strip()
        gross = money(li.get("total", "0"))
        if gross <= 0:
            continue
        item_id = qbo.item_by_sku(sku) if sku else None
        if not item_id:
            unmapped.append(f"{sku or '(no SKU)'} ({li.get('name')})")
            continue
        add(item_id, li.get("name"), gross, li.get("quantity"))

    # shipping + fees so the receipt total == what the customer paid
    ship = money(order.get("shipping_total", "0"))
    if ship > 0:
        sid = qbo.service_item_id("Delivery") if execute else "shipping"
        if sid:
            add(sid, "Delivery / Shipping", ship)
        else:
            warn.append(f"shipping ${ship} not added (no Delivery item)")
    for fee in order.get("fee_lines", []):
        amt = money(fee.get("total", "0"))
        if amt > 0:
            fid = qbo.service_item_id("Fee") if execute else "fee"
            if fid:
                add(fid, fee.get("name") or "Fee", amt)
            else:
                warn.append(f"fee ${amt} not added")
    return lines, warn, unmapped, built_net


def sync_order(order, qbo, execute, doctype="invoice"):
    ENTITY = "Invoice" if doctype == "invoice" else "SalesReceipt"
    num = order.get("number"); oid = order.get("id")
    doc = f"woo-{oid}"
    billing = order.get("billing") or {}
    name = (billing.get("first_name", "") + " " + billing.get("last_name", "")).strip()
    email = billing.get("email", "")
    total = money(order.get("total", "0"))

    print(f"--- Order #{num}  {name} <{email}>  WC ${total}  ({doc}) [{ENTITY}] ---")
    if execute and qbo.txn_exists(ENTITY, doc):
        print("    already in QBO (DocNumber exists) — skip"); return "skip"

    lines, warn, unmapped, built_net = build_lines(order, qbo, execute)
    for ln in lines:
        d = ln["SalesItemLineDetail"]
        print(f"    {ln['Description'][:42]:42} item={d['ItemRef']['value']} qty={d.get('Qty')} ${ln['Amount']:.2f} SR")
    for w in warn:
        print(f"    [warn] {w}")

    # F1 — never post an under-stated receipt: any unmapped product blocks the whole order.
    if unmapped:
        for u in unmapped:
            print(f"    [BLOCKED] unmapped product: {u}")
        print("    → order held (add the WC SKU to the crosswalk, or set the QBO item's Sku, then re-run). NOT posted.")
        return "unmapped"
    if not lines:
        print("    no usable lines — skip"); return "skip"

    # F2 — reconcile the receipt QBO will build (net + 9% SR) against what the customer paid.
    # Tolerance $0.05 absorbs GST rounding on multi-line orders; larger gaps = a real
    # discount/fee not modelled, so we hold rather than post a non-reconciling receipt.
    expected = built_net + money(GST_RATE * built_net)
    delta = total - expected
    if abs(delta) > Decimal("0.05"):
        print(f"    [MISMATCH] would post ${expected} vs WC paid ${total} (Δ ${delta}) — likely a discount/fee not modelled")
        if execute:
            print("    → held to protect reconciliation. NOT posted."); return "mismatch"

    cust_id = None
    if execute:
        cust_id = qbo.customer_by_email(email)
        if not cust_id:
            cust_id, err = qbo.create_customer(name, email)
            if err:
                print(f"    [error] {err}"); return "error"
        print(f"    customer QBO id {cust_id}")

    payload = {
        "DocNumber": doc,
        "CustomerRef": {"value": cust_id} if cust_id else {"value": "__DRYRUN__"},
        "GlobalTaxCalculation": "TaxExcluded",   # line Amounts are NET; QBO adds 9% SR back to the gross the customer paid
        "TxnDate": (order.get("date_paid") or order.get("date_created") or "")[:10] or None,
        "PrivateNote": f"WooCommerce B2C order #{num} ({order.get('payment_method_title')})",
        "Line": lines,
    }
    if doctype == "salesreceipt":
        # A paid receipt deposits the money into an account. Invoices stay UNPAID — Michelle
        # receives payment against them and deducts the Stripe fee when she reconciles the
        # monthly payout, so per-order gross doesn't get force-matched to the bank.
        payload["DepositToAccountRef"] = {"value": DEPOSIT_ACCOUNT_ID}
    payload = {k: v for k, v in payload.items() if v is not None}

    if not execute:
        kind = "paid receipt" if doctype == "salesreceipt" else "UNPAID invoice"
        print(f"    [dry-run] would POST {ENTITY} ({kind}) total=${expected} (net ${built_net} + 9% SR; WC paid ${total})"); return "dryrun"

    st, d = qbo.post(doctype, payload)
    if st == 200:
        obj = d[ENTITY]
        tail = f"  Balance=${obj.get('Balance')} (unpaid)" if doctype == "invoice" else "  (paid)"
        print(f"    ✅ {ENTITY} #{obj['Id']}  Total=${obj.get('TotalAmt')}{tail}")
        return "ok"
    print(f"    [error] {ENTITY} POST failed ({st}): {str(d)[:220]}")
    return "error"


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--execute", action="store_true", help="actually POST to QBO (default dry-run)")
    ap.add_argument("--limit", type=int, default=5)
    ap.add_argument("--status", default="processing")
    ap.add_argument("--only", help="only this WC order id")
    ap.add_argument("--since", help="only orders paid/created on/after YYYY-MM-DD (today-onward go-live guard)")
    ap.add_argument("--doctype", choices=["invoice", "salesreceipt"], default="invoice",
                    help="QBO document to create (default: invoice — UNPAID, Michelle closes w/ Stripe fee)")
    args = ap.parse_args()

    if args.status in B2B_STATUSES:
        sys.exit(f"[!] {args.status} is B2B — handled by the plugin, not here.")

    env = load_env()
    crosswalk = load_crosswalk()
    qbo = QBO(env, crosswalk)
    xwalk_note = f"{len(crosswalk)} WC-SKU→QBO-code mappings" if crosswalk \
        else f"MISSING ({CROSSWALK_PATH}) — direct SKU match only"
    print(f"[i] mode: {'EXECUTE (live)' if args.execute else 'DRY-RUN'}  status={args.status}  doctype={args.doctype}")
    print(f"[i] crosswalk: {xwalk_note}")
    orders = wc_orders(env, args.status, args.limit)
    if args.only:
        orders = [o for o in orders if str(o.get("id")) == str(args.only)]
    elif args.since:
        before = len(orders)
        orders = [o for o in orders if (o.get("date_paid") or o.get("date_created") or "")[:10] >= args.since]
        print(f"[i] --since {args.since}: {len(orders)} of {before} fetched order(s) in window")
    print(f"[i] {len(orders)} order(s)\n")

    tally = {}
    for o in orders:
        r = sync_order(o, qbo, args.execute, args.doctype)
        tally[r] = tally.get(r, 0) + 1
        print()
    print("[i] summary:", tally)


if __name__ == "__main__":
    main()
