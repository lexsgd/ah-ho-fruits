#!/usr/bin/env python3
"""
One-off: set the QBO item Sku on the "no-code" QBO items so unmapped WooCommerce
products will resolve for the invoice sync. Matches WC product -> QBO item by name
similarity; ONLY applies high-confidence, non-conflicting matches (each QBO item
targeted by exactly one WC product, confidence >= THRESH). Everything else is
deferred and printed for manual confirmation.

Safe: sparse update of just the Sku field, fresh SyncToken. Reversible (clear Sku).
Writes qbo-item-sku-set-<ts>.applied.json for rollback.
Usage:  python3 qbo-set-item-skus.py            # dry-run (default)
        python3 qbo-set-item-skus.py --execute
"""
import os, sys, json, re, argparse, importlib.util
from difflib import SequenceMatcher
from datetime import datetime, timezone

HERE = os.path.dirname(os.path.abspath(__file__))
THRESH = 0.75

spec = importlib.util.spec_from_file_location('tc', os.path.join(HERE, 'qbo-set-item-taxcode.py'))
tc = importlib.util.module_from_spec(spec); spec.loader.exec_module(tc)
xspec = importlib.util.spec_from_file_location('sync', os.path.join(HERE, 'b2c-qbo-salesreceipt-sync.py'))
sync = importlib.util.module_from_spec(xspec); xspec.loader.exec_module(sync)

WC = os.path.join(HERE, 'wc-backups/2026-07-19T00-31-06Z/full-store/products.json')
QBO_ITEMS = os.path.join(HERE, 'qbo-backups/2026-07-19T10-17-36Z/Item.json')


def norm(s):
    return re.sub(r'[^0-9a-zA-Z一-鿿]+', ' ', (s or '').lower()).strip()


def build():
    wc = json.load(open(WC)); wc = wc if isinstance(wc, list) else wc.get('products', wc)
    it = json.load(open(QBO_ITEMS)); it = it if isinstance(it, list) else it.get('Item', it)
    it = [i for i in it if i.get('Active')]
    qbo_skus = {(i.get('Sku') or '').strip() for i in it if (i.get('Sku') or '').strip()}
    xwalk = sync.load_crosswalk()
    qn = [(norm(i.get('Name', '')), i.get('Name', ''), (i.get('Sku') or '').strip(), i.get('Id')) for i in it]
    cand = []
    for p in wc:
        sku = (p.get('sku') or '').strip(); name = p.get('name', '')
        code = xwalk.get(sku)
        if (sku in qbo_skus) or (code and code in qbo_skus):
            continue
        nn = norm(name)
        best = max(qn, key=lambda q: SequenceMatcher(None, nn, q[0]).ratio())
        conf = round(SequenceMatcher(None, nn, best[0]).ratio(), 2)
        if best[2]:            # QBO item already has a code -> not our bucket (WC-side fix)
            continue
        cand.append({'conf': conf, 'qbo_id': best[3], 'qbo_name': best[1], 'wc_sku': sku, 'wc_name': name})
    # keep best match per QBO item; apply only unique + high-confidence
    bybest = {}
    for c in sorted(cand, key=lambda c: -c['conf']):
        bybest.setdefault(c['qbo_id'], c)
    apply = sorted([c for c in bybest.values() if c['conf'] >= THRESH], key=lambda c: -c['conf'])
    defer = [c for c in cand if c not in apply]
    return apply, defer


def post_item(q, payload):
    url = f"{tc.QBO_BASE}/{q.realm}/item?minorversion=70"
    return tc._req(url, 'POST', q._h(), json.dumps(payload))


def main():
    ap = argparse.ArgumentParser(); ap.add_argument('--execute', action='store_true'); args = ap.parse_args()
    apply, defer = build()
    print(f"[i] {'EXECUTE' if args.execute else 'DRY-RUN'}  apply={len(apply)} (conf>={THRESH}, unique)  defer={len(defer)}")
    if not args.execute:
        for c in apply:
            print(f"  would set  item {c['qbo_id']} \"{c['qbo_name'][:32]}\"  Sku={c['wc_sku']}  (conf {c['conf']})")
        print("  --- deferred (manual confirm): ---")
        for c in sorted(defer, key=lambda c: -c['conf']):
            print(f"  conf {c['conf']}  item {c['qbo_id']} \"{c['qbo_name'][:30]}\"  ?<- {c['wc_sku']}  ({c['wc_name'][:26]})")
        return
    env = tc.load_env(); q = tc.QBO(env)
    ts = datetime.now(timezone.utc).strftime('%Y-%m-%dT%H-%M-%SZ')
    json.dump({'applied': apply, 'deferred': defer},
              open(os.path.join(HERE, f'qbo-item-sku-set-{ts}.applied.json'), 'w'), indent=2, ensure_ascii=False)
    ok = err = 0
    for c in apply:
        cur = q.query(f"select Id,SyncToken from Item where Id='{c['qbo_id']}'").get('Item', [])
        if not cur:
            print(f"  [skip] {c['qbo_id']} not found"); err += 1; continue
        st, d = post_item(q, {'Id': c['qbo_id'], 'SyncToken': cur[0]['SyncToken'], 'sparse': True, 'Sku': c['wc_sku']})
        if st == 200:
            ok += 1; print(f"  ✓ {c['qbo_id']} \"{c['qbo_name'][:30]}\" Sku={c['wc_sku']}")
        else:
            err += 1; print(f"  ✗ {c['qbo_id']} FAIL {str(d)[:100]}")
    print(f"[i] done: {ok} set, {err} failed. Deferred {len(defer)} for manual confirm.")


if __name__ == '__main__':
    main()
