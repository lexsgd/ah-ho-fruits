#!/usr/bin/env python3
"""Apply Michelle's final-7 answers (2026-07-19 WhatsApp):
  A) plums -> her NEW items 1289 (OCT SUN PLUM kg) / 1290 (RED PLUM kg); Amber Jewel -> Red Plum.
  B) 2 oranges (USA Navel L/M) -> item 771 SUNKIST NAVEL pcs.
  C) SA pear (L + pc) -> item 804 PEAR pcs.
SET = item has no Sku -> set item.Sku = WC SKU (direct). XWALK = sibling WC SKU -> that Sku.
Reversible (rollback json), crosswalk backed up. Dry-run default; --execute to apply."""
import os, json, argparse, importlib.util
from datetime import datetime, timezone
HERE = os.path.dirname(os.path.abspath(__file__))
spec = importlib.util.spec_from_file_location('m11', os.path.join(HERE, 'qbo-apply-michelle-11.py'))
m11 = importlib.util.module_from_spec(spec); spec.loader.exec_module(m11)
tc = m11.tc

# (mode, wc_sku, qbo_item_id, target_code|None, qbo_name, label)
PLAN = [
    ('SET',   'B2B-OCTSUNPLUM', '1289', None,             '9060 OCT SUN PLUM 李子 kg',       'A October Sun Plum'),
    ('SET',   'AHF-PLUM-RED-1', '1290', None,             '9060 RED PLUM 李子 kg',           'A Australia Red Plum'),
    ('XWALK', 'AHF-PLUM-AMBER-1','1290','AHF-PLUM-RED-1', '9060 RED PLUM 李子 kg',           'A Amber Jewel Plum -> Red Plum'),
    ('SET',   'AHF-SUNKIST-L',  '771',  None,             '9012 SUNKIST NAVEL 新奇士脐橙 pcs','B USA Navel Orange (L)'),
    ('XWALK', 'AHF-SUNKIST-S-1','771',  'AHF-SUNKIST-L',  '9012 SUNKIST NAVEL 新奇士脐橙 pcs','B USA Navel Orange (M)'),
    ('SET',   'AHF-SA-PEAR-1',  '804',  None,             '9036 PEAR pcs 耙 梨',             'C South Africa Pear (L)'),
    ('XWALK', 'AHF-SA-PEAR-1-1','804',  'AHF-SA-PEAR-1',  '9036 PEAR pcs 耙 梨',             'C South Africa Pear (pc)'),
]

ap = argparse.ArgumentParser(); ap.add_argument('--execute', action='store_true'); a = ap.parse_args()
sets = [p for p in PLAN if p[0] == 'SET']
xwalks = [p for p in PLAN if p[0] == 'XWALK']
env = tc.load_env(); q = tc.QBO(env)

# collision guard: WC SKUs we SET must not already be another item's Sku
print(f"[i] {'EXECUTE' if a.execute else 'DRY-RUN'}  sets={len(sets)} xwalk={len(xwalks)}")
for _, wc, iid, _, name, label in sets:
    hit = q.query(f"select Id,Name from Item where Sku = '{wc}'").get('Item', [])
    other = [h for h in hit if h['Id'] != iid]
    print(f"  SET   item {iid} \"{name[:30]}\" Sku={wc}   {'⚠ COLLISION '+str(other) if other else 'free ✓'}   ({label})")
for _, wc, iid, code, name, label in xwalks:
    print(f"  XWALK {wc} -> {code}   ({label})")

if not a.execute:
    print("\n[i] dry-run only."); raise SystemExit

ts = datetime.now(timezone.utc).strftime('%Y-%m-%dT%H-%M-%SZ')
rb = {'sets': [], 'xwalk': []}
ok = err = 0
for _, wc, iid, _, name, label in sets:
    cur = q.query(f"select Id,Name,Sku,SyncToken from Item where Id='{iid}'").get('Item', [])
    if not cur:
        print(f"  ✗ SET {iid} not found"); err += 1; continue
    url = f"{tc.QBO_BASE}/{q.realm}/item?minorversion=70"
    st, d = tc._req(url, 'POST', q._h(),
                    json.dumps({'Id': iid, 'SyncToken': cur[0]['SyncToken'], 'sparse': True, 'Sku': wc}))
    if st == 200:
        ok += 1; rb['sets'].append({'id': iid, 'prior_sku': cur[0].get('Sku'), 'new_sku': wc})
        print(f"  ✓ SET item {iid} \"{name[:28]}\" Sku={wc}")
    else:
        err += 1; print(f"  ✗ SET item {iid} FAIL {str(d)[:120]}")

# crosswalk backup + update-or-append
bak = m11.XWALK_CSV + f'.bak-{ts}'
with open(m11.XWALK_CSV, 'rb') as s, open(bak, 'wb') as d: d.write(s.read())
print(f"  = crosswalk backed up -> {os.path.basename(bak)}")
desired = {wc: (code, label, name) for _, wc, iid, code, name, label in xwalks}
for ch in m11.apply_xwalk(desired, execute=True):
    rb['xwalk'].append(ch)
    if ch['action'] != 'ok':
        print(f"  ✓ XWALK {ch['action']} {ch['wc']} -> {ch['new']}")
json.dump(rb, open(os.path.join(HERE, f'qbo-michelle-final7-{ts}.applied.json'), 'w'), indent=2, ensure_ascii=False)
print(f"[i] done: {ok} sets, {err} failed, {len(desired)} crosswalk rows.")
