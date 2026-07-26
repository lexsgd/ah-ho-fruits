#!/usr/bin/env python3
"""
Sync QBO customers -> WooCommerce, on top of existing WC customers.

Default = DRY RUN (no writes). Use --execute to apply.

Steps:
  1. Back up ALL existing WC customers to wc-backups/<ts>/customers.json
  2. Map every QBO customer -> WC fields (placeholder email = enquiry+<slug(DisplayName)>@ahhofruit.com)
  3. Dedupe vs existing WC (by _qbo_customer_id meta, then by email)
  4. Classify create / update / skip; write proposed diff to wc-backups/<ts>/dryrun-plan.json
  5. --execute: batch create/update (100/call), tag every record with _qbo_customer_id

Idempotent + re-runnable. Read-only unless --execute.
"""
import os, sys, json, re, time, base64, hashlib, urllib.request, urllib.parse, urllib.error
from datetime import datetime, timezone

PROJ_DIR = os.path.dirname(os.path.abspath(__file__))
ENVP = os.path.join(PROJ_DIR, ".env")
BACKUP_GLOB = os.path.join(PROJ_DIR, "qbo-backups")
EXECUTE = "--execute" in sys.argv


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
WC_CK = ENV["WC_CONSUMER_KEY"]
WC_CS = ENV["WC_CONSUMER_SECRET"]
WC_BASE = ENV.get("WC_BASE_URL", "https://ahhofruit.com").rstrip("/")
WC_API = f"{WC_BASE}/wp-json/wc/v3"
AUTH = base64.b64encode(f"{WC_CK}:{WC_CS}".encode()).decode()


def wc(method, path, body=None, params=None):
    url = f"{WC_API}/{path}"
    if params:
        url += "?" + urllib.parse.urlencode(params)
    data = json.dumps(body).encode() if body is not None else None
    req = urllib.request.Request(url, data=data, method=method, headers={
        "Authorization": f"Basic {AUTH}",
        "Content-Type": "application/json",
        "Accept": "application/json",
        "User-Agent": "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36",
    })
    with urllib.request.urlopen(req, timeout=120) as r:
        return json.loads(r.read().decode()), dict(r.headers)


def latest_qbo_backup():
    dirs = sorted(d for d in os.listdir(BACKUP_GLOB) if os.path.isdir(os.path.join(BACKUP_GLOB, d)))
    return os.path.join(BACKUP_GLOB, dirs[-1], "Customer.json")


def slug(name):
    s = re.sub(r"[^a-z0-9]+", "-", name.strip().lower())
    return re.sub(r"-+", "-", s).strip("-")


def build_emails(qbo):
    """Final unique email per customer: real email if present else placeholder
    enquiry+<slug>@ahhofruit.com; -<id> on slug collisions; and a GLOBAL uniqueness
    guard (incl. shared real emails like the THAI BAANG group) via plus-addressing."""
    from collections import Counter
    slugs = {c["Id"]: slug(c.get("DisplayName", "") or f"customer-{c['Id']}") for c in qbo}
    scount = Counter(slugs.values())
    seen, out = set(), {}
    for c in qbo:
        cid = c["Id"]
        real = (c.get("PrimaryEmailAddr") or {}).get("Address", "").strip().lower()
        if real:
            email = real
        else:
            s = slugs[cid]
            if scount[s] > 1:
                s = f"{s}-{cid}"
            email = f"enquiry+{s}@ahhofruit.com"
        if email in seen:  # shared/duplicate address -> plus-address with QBO id
            local, _, domain = email.partition("@")
            local = f"{local}-qbo{cid}" if "+" in local else f"{local}+qbo{cid}"
            email = f"{local}@{domain}"
        seen.add(email)
        out[cid] = email
    return out


PC_RE = re.compile(r'(?<!\d)(\d{6})(?!\d)')  # SG postcode = 6 digits, not part of a longer run


def clean_phone(raw):
    """Extract the phone number, dropping notes like 'mr quek' / 'Tel:'."""
    if not raw:
        return ""
    m = re.search(r'\+?\d[\d\s\-]{5,}\d', raw)
    return (m.group(0) if m else raw).strip()


def clean_sg_address(addr):
    """Parse a free-text QBO address into structured WC fields.
    Extracts the SG postcode, sets city=Singapore, splits street (address_1)
    from unit/building (address_2). Faithful: nothing dropped except the
    postcode token (moved to its own field)."""
    empty = {"address_1": "", "address_2": "", "city": "", "state": "", "postcode": "", "country": "SG"}
    if not addr:
        return empty
    ls = [addr.get(k, "").strip() for k in ("Line1", "Line2", "Line3", "Line4", "Line5") if addr.get(k, "").strip()]
    structured_pc = (addr.get("PostalCode") or "").strip()
    parsed = PC_RE.findall(" ".join(ls))            # 6-digit postcodes living inside the text lines
    postcode = structured_pc or (parsed[-1] if parsed else "")
    if not ls and not postcode:
        return empty

    def strip_pc(s):
        for pc in parsed:  # only strip tokens actually present in the free-text lines
            s = re.sub(r'\(?\s*s(?:ingapore)?\s*\)?[\s,]*' + pc + r'\b', '', s, flags=re.I)
            s = re.sub(r'(?<!\d)' + pc + r'(?!\d)', '', s)
        return s.strip(" ,|")

    cleaned = [x for x in (strip_pc(s) for s in ls) if x]
    city = (addr.get("City") or "").strip() or ("Singapore" if (cleaned or postcode) else "")
    return {
        "address_1": cleaned[0] if cleaned else "",
        "address_2": ", ".join(cleaned[1:]) if len(cleaned) > 1 else "",
        "city": city,
        "state": (addr.get("CountrySubDivisionCode") or "").strip(),
        "postcode": postcode,
        "country": "SG",
    }


def qbo_to_wc(c, email):
    real = bool((c.get("PrimaryEmailAddr") or {}).get("Address", "").strip())
    display = c.get("DisplayName", "") or f"Customer {c['Id']}"
    phone = clean_phone((c.get("PrimaryPhone") or {}).get("FreeFormNumber") or (c.get("Mobile") or {}).get("FreeFormNumber") or "")
    bill = clean_sg_address(c.get("BillAddr"))
    ship = clean_sg_address(c.get("ShipAddr")) if c.get("ShipAddr") else dict(bill)
    bill.update({"first_name": "", "last_name": display, "company": display, "phone": phone, "email": email})
    ship.update({"first_name": "", "last_name": display, "company": display})
    return {
        "email": email,
        "first_name": "",
        "last_name": display,
        "username": f"qbo-{c['Id']}",
        "billing": bill,
        "shipping": ship,
        "meta_data": [
            # NOTE: no leading underscore — WC REST hides _-prefixed meta, breaking readback/rollback
            {"key": "qbo_customer_id", "value": str(c["Id"])},
            {"key": "qbo_placeholder_email", "value": "false" if (real and email == (c.get('PrimaryEmailAddr') or {}).get('Address','').strip().lower()) else "true"},
            {"key": "qbo_synced_at", "value": datetime.now(timezone.utc).isoformat()},
        ],
    }


def fetch_all_wc_customers():
    out, page = [], 1
    while True:
        rows, _ = wc("GET", "customers", params={"per_page": 100, "page": page, "role": "all"})
        if not rows:
            break
        out.extend(rows)
        if len(rows) < 100:
            break
        page += 1
    return out


def main():
    ts = datetime.now(timezone.utc).strftime("%Y-%m-%dT%H-%M-%SZ")
    outdir = os.path.join(PROJ_DIR, "wc-backups", ts)
    os.makedirs(outdir, exist_ok=True)
    mode = "EXECUTE" if EXECUTE else "DRY RUN"
    print(f"=== QBO -> WC customer sync [{mode}] ===\n")

    qbo = json.load(open(latest_qbo_backup()))
    print(f"QBO customers: {len(qbo)}")

    # Step 1: backup existing WC customers
    existing = fetch_all_wc_customers()
    json.dump(existing, open(os.path.join(outdir, "customers.json"), "w"), indent=2)
    print(f"Existing WC customers backed up: {len(existing)} -> {outdir}/customers.json")

    by_email = {c.get("email", "").lower(): c for c in existing if c.get("email")}
    by_qboid = {}
    for c in existing:
        for m in c.get("meta_data", []):
            if m.get("key") == "qbo_customer_id":
                by_qboid[str(m.get("value"))] = c

    emails = build_emails(qbo)
    creates, updates, plan = [], [], []
    for c in qbo:
        payload = qbo_to_wc(c, emails[c["Id"]])
        match = by_qboid.get(str(c["Id"])) or by_email.get(payload["email"])
        if match:
            upd = {"id": match["id"], **{k: payload[k] for k in ("billing", "shipping", "meta_data")}}
            updates.append(upd)
            plan.append({"action": "update", "wc_id": match["id"], "qbo_id": c["Id"], "name": payload["last_name"], "email": payload["email"]})
        else:
            creates.append(payload)
            plan.append({"action": "create", "qbo_id": c["Id"], "name": payload["last_name"], "email": payload["email"], "placeholder": payload["meta_data"][1]["value"]})

    json.dump(plan, open(os.path.join(outdir, "dryrun-plan.json"), "w"), indent=2)
    ph = sum(1 for p in plan if p.get("placeholder") == "true")
    print(f"\nPLAN: create={len(creates)}  update={len(updates)}  (placeholder emails={ph}, real emails={len(qbo)-ph})")
    print(f"Full proposed diff: {outdir}/dryrun-plan.json")
    print("\n--- first 10 proposed actions ---")
    for p in plan[:10]:
        print(f"  {p['action']:6} | {p.get('name','')[:34]:34} | {p['email']}")

    if not EXECUTE:
        print("\nDRY RUN — nothing written. Re-run with --execute to apply.")
        return

    # EXECUTE: batch create then update, 100 per call
    print("\nExecuting batches...")
    def batches(lst, n=100):
        for i in range(0, len(lst), n):
            yield lst[i:i+n]
    created = updated = 0
    for b in batches(creates):
        resp, _ = wc("POST", "customers/batch", body={"create": b})
        created += sum(1 for x in resp.get("create", []) if x.get("id"))
        errs = [x for x in resp.get("create", []) if x.get("error")]
        if errs:
            print(f"  create errors: {len(errs)} e.g. {errs[0].get('error')}")
        time.sleep(0.5)
    for b in batches(updates):
        resp, _ = wc("POST", "customers/batch", body={"update": b})
        updated += sum(1 for x in resp.get("update", []) if x.get("id"))
        time.sleep(0.5)
    print(f"Done. created={created} updated={updated}")


if __name__ == "__main__":
    main()
