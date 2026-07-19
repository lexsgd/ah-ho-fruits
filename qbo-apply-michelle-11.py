#!/usr/bin/env python3
"""
Apply Michelle's 11-product mappings (2026-07-19 WhatsApp reply). 8 resolve to a
single existing QBO item; 3 plums are held for re-confirm (no clean single target).

Two mechanisms, matching the sync's DIRECT-FIRST then CROSSWALK-FALLBACK resolution:
  SET    -> QBO item has no Sku: set item.Sku = the WC SKU (direct match).
  XWALK  -> QBO item already has a Sku (code): append a crosswalk row
            WC SKU -> that code (many WC products -> one coarse QBO item).

Safe: sparse Sku update w/ fresh SyncToken; crosswalk append is idempotent.
Reversible: writes qbo-item-sku-set-<ts>.michelle11.json (prior Sku values +
appended crosswalk rows).
Usage:  python3 qbo-apply-michelle-11.py            # dry-run
        python3 qbo-apply-michelle-11.py --execute
"""
import os, sys, json, csv, argparse, importlib.util
from datetime import datetime, timezone

HERE = os.path.dirname(os.path.abspath(__file__))
spec = importlib.util.spec_from_file_location('tc', os.path.join(HERE, 'qbo-set-item-taxcode.py'))
tc = importlib.util.module_from_spec(spec); spec.loader.exec_module(tc)
XWALK_CSV = os.path.join(HERE, 'QBO-WC-Crosswalk-FINAL-2026-06-16.csv')

# (mode, wc_sku, qbo_item_id, qbo_code_or_None, qbo_name, wc_product)
PLAN = [
    ('SET',   'AHF-COCONUT-12-1',   '595',  None,                  '5006 30 COCONUT 椰子',            '30" THAI COCONUT'),
    ('SET',   'AHF-ORANGE-EGY-1',   '845',  None,                  '9067 ORANGE pcs 橙子',            'SA. VALENCIA ORANGE (pc)'),
    ('SET',   'AHF-COCONUT-1',      '832',  None,                  '9059 COCONUT 椰子 pcs',           'YOUNG COCONUT'),
    ('SET',   'AHF-LONGAN-ORG-1KG', '1156', None,                  'W-T08 LONGAN 龙眼 kg',            'ORGANIC LONGAN (1kg)'),
    ('SET',   'B2C-MANGO-THAI',     '793',  None,                  '9028 MANGO pcs 芒果',             'THAI HONEY MANGO (pc)'),
    ('XWALK', 'AHF-FRAGRANT-XXL-1', '548',  'AHF-FRAGRANT-XXL',    '4007 KS FRAGRANT PEAR XXL 7kg',   'FRAGRANT PEAR XXL [KS REDBOX]'),
    ('XWALK', 'AHF-KIWI-GOLD-J-1',  '30',   'AHF-KIWI-GOLD-JUMBO', '0007.2 31 ZESPRI JUMBO GOLD KIWI','GOLD KIWI JUMBO'),
    ('XWALK', 'B2C-TWMG',           '793',  'B2C-MANGO-THAI',      '9028 MANGO pcs 芒果',             'TAIWAN AIWEN MANGO (pc)'),
]
# SET #10 (793 -> B2C-MANGO-THAI) MUST run before XWALK #11 (B2C-TWMG -> B2C-MANGO-THAI).


FIELDS = ['WC SKU', 'Website product name', 'QBO code', 'QBO description']


def load_xwalk_rows():
    rows = []
    if os.path.exists(XWALK_CSV):
        with open(XWALK_CSV, newline='', encoding='utf-8-sig') as f:
            r = csv.DictReader(f)
            rows = list(r)
    return rows


def apply_xwalk(desired, execute):
    """desired: {wc_sku: (code, prod, name)}. Update an existing row's QBO code if
    it differs (records old code for rollback), else append. Returns list of changes."""
    rows = load_xwalk_rows()
    by_wc = {}
    for row in rows:
        by_wc[(row.get('WC SKU') or '').strip()] = row
    changes = []
    for wc, (code, prod, name) in desired.items():
        cur = by_wc.get(wc)
        if cur is None:
            changes.append({'wc': wc, 'action': 'append', 'old': None, 'new': code})
            rows.append({'WC SKU': wc, 'Website product name': prod, 'QBO code': code, 'QBO description': name})
        else:
            old = (cur.get('QBO code') or '').strip()
            if old == code:
                changes.append({'wc': wc, 'action': 'ok', 'old': old, 'new': code})
            else:
                changes.append({'wc': wc, 'action': 'update', 'old': old, 'new': code})
                cur['QBO code'] = code
                cur['QBO description'] = name
    if execute:
        with open(XWALK_CSV, 'w', newline='', encoding='utf-8') as f:
            w = csv.DictWriter(f, fieldnames=FIELDS)
            w.writeheader()
            for row in rows:
                w.writerow({k: row.get(k, '') for k in FIELDS})
    return changes


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--execute', action='store_true')
    args = ap.parse_args()
    sets = [p for p in PLAN if p[0] == 'SET']
    xwalks = [p for p in PLAN if p[0] == 'XWALK']
    desired = {wc: (code, prod, name) for mode, wc, iid, code, name, prod in xwalks}

    print(f"[i] {'EXECUTE' if args.execute else 'DRY-RUN'}  sets={len(sets)}  xwalk_rows={len(xwalks)}")
    for mode, wc, iid, code, name, prod in PLAN:
        if mode == 'SET':
            print(f"  SET    item {iid:>4} \"{name[:34]}\"  Sku={wc}   ({prod})")
    # preview crosswalk actions (no write in dry-run)
    for ch in apply_xwalk(desired, execute=False):
        act = {'append': 'APPEND', 'update': f"UPDATE (was {ch['old']!r})", 'ok': 'already correct'}[ch['action']]
        print(f"  XWALK  {ch['wc']} -> {ch['new']}   [{act}]")
    print("  HOLD   #4 OCT SUN PLUM (B2B-OCTSUNPLUM), #7 AUS RED PLUM (AHF-PLUM-RED-1), "
          "#9 AMBER JEWEL PLUM (AHF-PLUM-AMBER-1) — re-confirm target")

    if not args.execute:
        print("\n[i] dry-run only. Re-run with --execute to apply.")
        return

    env = tc.load_env(); q = tc.QBO(env)
    ts = datetime.now(timezone.utc).strftime('%Y-%m-%dT%H-%M-%SZ')
    rollback = {'sets': [], 'xwalk_changes': []}
    ok = err = 0

    # SET item Skus first (#10 sets 793's Sku before #11's crosswalk references it)
    for mode, wc, iid, code, name, prod in sets:
        cur = q.query(f"select Id,Name,Sku,SyncToken from Item where Id='{iid}'").get('Item', [])
        if not cur:
            print(f"  ✗ SET {iid} not found"); err += 1; continue
        prior = cur[0].get('Sku')
        url = f"{tc.QBO_BASE}/{q.realm}/item?minorversion=70"
        st, d = tc._req(url, 'POST', q._h(),
                        json.dumps({'Id': iid, 'SyncToken': cur[0]['SyncToken'], 'sparse': True, 'Sku': wc}))
        if st == 200:
            ok += 1; rollback['sets'].append({'id': iid, 'prior_sku': prior, 'new_sku': wc})
            print(f"  ✓ SET item {iid} \"{name[:30]}\" Sku={wc}")
        else:
            err += 1; print(f"  ✗ SET item {iid} FAIL {str(d)[:120]}")

    # back up crosswalk before rewrite, then update-or-append
    if os.path.exists(XWALK_CSV):
        bak = XWALK_CSV + f'.bak-{ts}'
        with open(XWALK_CSV, 'rb') as src, open(bak, 'wb') as dst:
            dst.write(src.read())
        print(f"  = crosswalk backed up -> {os.path.basename(bak)}")
    for ch in apply_xwalk(desired, execute=True):
        rollback['xwalk_changes'].append(ch)
        if ch['action'] != 'ok':
            print(f"  ✓ XWALK {ch['action']} {ch['wc']} -> {ch['new']}"
                  + (f" (was {ch['old']!r})" if ch['action'] == 'update' else ""))

    rbf = os.path.join(HERE, f'qbo-item-sku-set-{ts}.michelle11.json')
    json.dump(rollback, open(rbf, 'w'), indent=2, ensure_ascii=False)
    print(f"\n[i] done: {ok} sets ok, {err} failed. crosswalk changes: "
          + ", ".join(f"{c['action']}" for c in rollback['xwalk_changes']))
    print(f"[i] rollback -> {rbf}")
    print("[i] HELD (re-confirm): #4 OCT SUN PLUM, #7 AUS RED PLUM, #9 AMBER JEWEL PLUM")


if __name__ == '__main__':
    main()
