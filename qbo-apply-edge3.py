#!/usr/bin/env python3
"""Apply the 3 already-correct edge-case mappings (SKU strings were misleading; the
crosswalk product-NAME confirmed them correct). SET two no-Sku items; crosswalk the
locsweet sibling. Reversible (rollback json + CSV backup). --execute to apply."""
import os, json, argparse, importlib.util
from datetime import datetime, timezone
HERE = os.path.dirname(os.path.abspath(__file__))
spec = importlib.util.spec_from_file_location('m11', os.path.join(HERE, 'qbo-apply-michelle-11.py'))
m11 = importlib.util.module_from_spec(spec); spec.loader.exec_module(m11); tc = m11.tc

SETS = [
    ('1225', 'AHF-CHERRYTOMATOVINE-1',      '2027.23 11 AUS CUSTARD APPLE 釋迦果'),
    ('166',  'AHF-PLUM-AMBER-10KG-2-2-1-1', '1045 16 x 315g SA SUGAR PRUNE 非洲蜜枣'),
]
SIBLING = {'AHF-SUNKIST-56-1': 'AHF-LOCSWEET'}   # item 921 already keyed AHF-LOCSWEET

ap = argparse.ArgumentParser(); ap.add_argument('--execute', action='store_true'); a = ap.parse_args()
env = tc.load_env(); q = tc.QBO(env)
print(f"[i] {'EXECUTE' if a.execute else 'DRY-RUN'}  sets={len(SETS)} sibling={len(SIBLING)}")
for iid, wc, nm in SETS:
    other = [h for h in q.query(f"select Id,Name from Item where Sku='{wc}'").get('Item', []) if h['Id'] != iid]
    print(f"  SET   item {iid} Sku={wc}  ({nm[:34]}) {'⚠COLLISION '+str(other) if other else 'free ✓'}")
for wc, tgt in SIBLING.items():
    ex = q.query(f"select Id,Name from Item where Sku='{tgt}'").get('Item', [])
    print(f"  XWALK {wc} -> {tgt}  {'(target ok '+ex[0]['Id']+')' if ex else '⚠ target missing'}")
if not a.execute:
    print("\n[i] dry-run only."); raise SystemExit

ts = datetime.now(timezone.utc).strftime('%Y-%m-%dT%H-%M-%SZ')
rb = {'sets': [], 'xwalk': []}
ok = err = 0
for iid, wc, nm in SETS:
    cur = q.query(f"select Id,Sku,SyncToken from Item where Id='{iid}'").get('Item', [])
    if not cur: print(f"  ✗ {iid} not found"); err += 1; continue
    url = f"{tc.QBO_BASE}/{q.realm}/item?minorversion=70"
    st, d = tc._req(url, 'POST', q._h(), json.dumps({'Id': iid, 'SyncToken': cur[0]['SyncToken'], 'sparse': True, 'Sku': wc}))
    if st == 200:
        ok += 1; rb['sets'].append({'id': iid, 'prior_sku': cur[0].get('Sku'), 'new_sku': wc}); print(f"  ✓ SET item {iid} Sku={wc}")
    else:
        err += 1; print(f"  ✗ SET item {iid} FAIL {str(d)[:100]}")
bak = m11.XWALK_CSV + f'.bak-{ts}'
open(bak, 'wb').write(open(m11.XWALK_CSV, 'rb').read()); print(f"  = crosswalk backed up -> {os.path.basename(bak)}")
for ch in m11.apply_xwalk({wc: (tgt, '', '') for wc, tgt in SIBLING.items()}, execute=True):
    rb['xwalk'].append(ch); print(f"  ✓ XWALK {ch['action']} {ch['wc']} -> {ch['new']}")
json.dump(rb, open(os.path.join(HERE, f'qbo-apply-edge3-{ts}.applied.json'), 'w'), indent=2, ensure_ascii=False)
print(f"[i] done: {ok} sets, {err} failed, {len(SIBLING)} crosswalk rows.")
