#!/usr/bin/env python3
"""
One-off: set Sku=B2B-REDDRAGON on QBO item 767 "9008 RED DRAGONFRUIT 红龙珠果 pc".

The WC product "RED DRAGONFRUIT 白龙珠果 (pc)" (SKU B2B-REDDRAGON) has no QBO
match because item 767 carries no Sku, so the sync hard-blocks its orders.
Michelle confirmed 9008 is the right target (2026-07-26).

Same safe shape as qbo-set-item-skus.py: sparse update of just Sku, fresh
SyncToken, rollback JSON written before the write. Reversible (clear Sku).
Usage:  python3 qbo-set-reddragon-sku.py            # dry-run (default)
        python3 qbo-set-reddragon-sku.py --execute
"""
import os, json, argparse, importlib.util
from datetime import datetime, timezone

HERE = os.path.dirname(os.path.abspath(__file__))
ITEM_ID = '767'
NEW_SKU = 'B2B-REDDRAGON'

spec = importlib.util.spec_from_file_location('tc', os.path.join(HERE, 'qbo-set-item-taxcode.py'))
tc = importlib.util.module_from_spec(spec); spec.loader.exec_module(tc)


def main():
    ap = argparse.ArgumentParser(); ap.add_argument('--execute', action='store_true')
    args = ap.parse_args()

    env = tc.load_env(); q = tc.QBO(env)
    cur = q.query(f"select Id,Name,Sku,SyncToken from Item where Id='{ITEM_ID}'").get('Item', [])
    if not cur:
        raise SystemExit(f"[!] item {ITEM_ID} not found")
    it = cur[0]
    existing = (it.get('Sku') or '').strip()
    print(f"[i] {'EXECUTE' if args.execute else 'DRY-RUN'}")
    print(f"    item {ITEM_ID}  \"{it.get('Name')}\"  current Sku={existing or '(none)'} -> {NEW_SKU}")

    if existing and existing != NEW_SKU:
        raise SystemExit(f"[!] item already has Sku={existing!r} — refusing to overwrite")

    # Guard: NEW_SKU must not already be on a different item, or the sync would
    # resolve ambiguously.
    clash = q.query(f"select Id,Name from Item where Sku='{NEW_SKU}'").get('Item', [])
    clash = [c for c in clash if c['Id'] != ITEM_ID]
    if clash:
        raise SystemExit(f"[!] Sku {NEW_SKU} already on item(s) {[c['Id'] for c in clash]} — aborting")

    if not args.execute:
        print("    (dry-run — nothing written)")
        return

    ts = datetime.now(timezone.utc).strftime('%Y-%m-%dT%H-%M-%SZ')
    roll = os.path.join(HERE, f'qbo-reddragon-sku-{ts}.applied.json')
    json.dump({'item_id': ITEM_ID, 'name': it.get('Name'),
               'previous_sku': existing, 'new_sku': NEW_SKU},
              open(roll, 'w'), indent=2, ensure_ascii=False)

    url = f"{tc.QBO_BASE}/{q.realm}/item?minorversion=70"
    st, d = tc._req(url, 'POST', q._h(), json.dumps(
        {'Id': ITEM_ID, 'SyncToken': it['SyncToken'], 'sparse': True, 'Sku': NEW_SKU}))
    if st == 200:
        print(f"  ✓ set  (rollback: {os.path.basename(roll)})")
    else:
        raise SystemExit(f"  ✗ FAIL {st} {str(d)[:300]}")


if __name__ == '__main__':
    main()
