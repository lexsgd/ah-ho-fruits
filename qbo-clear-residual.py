#!/usr/bin/env python3
"""Clear the residual B2C crosswalk gaps after Michelle's mappings.
REPOINT: target item already has a Sku -> repoint crosswalk code to that Sku.
SET:     target item has NO Sku -> set item.Sku = the (primary) WC SKU (direct match).
SIBLING: additional WC SKU sharing a target -> crosswalk to the primary's Sku.
Semantically-doubtful legacy pairings are SKIPPED (listed) for manual confirm.
Reversible: rollback json + CSV backup. Dry-run default; --execute to apply."""
import os, json, argparse, importlib.util
from datetime import datetime, timezone
HERE = os.path.dirname(os.path.abspath(__file__))
spec = importlib.util.spec_from_file_location('m11', os.path.join(HERE, 'qbo-apply-michelle-11.py'))
m11 = importlib.util.module_from_spec(spec); spec.loader.exec_module(m11); tc = m11.tc

# 11 safe repoints: wc -> existing real Sku on the (now-keyed) target item
REPOINT = {
    'AHF-BINGTANGLI-6': 'AHF-BTL', 'AHF-BINGTANGLI-9': 'AHF-BTL',
    'AHF-CONFERENCE': 'AHF-CONFERENCE-1', 'AHF-CONFERENCE-MANUAL': 'AHF-CONFERENCE-1',
    'AHF-HAMIGUA-1': 'B2C-HAMI', 'AHF-HAMIGUA-4': 'AHF-4HAMI', 'AHF-HAMIGUA-4-1': 'AHF-5HAMI',
    'AHF-LONGAN-500': 'AHF-LONGAN-ORG-1KG',
    'AHF-ORANGE-CN-L': 'AHF-ORANGE-EGY-1', 'AHF-ORANGE-CN-S': 'AHF-ORANGE-EGY-1',
    'AHF-SUNKIST-LL': 'AHF-SUNKIST-L',
}
# SET item.Sku = primary WC SKU (target item currently has no Sku)
SETS = [
    ('1043', 'AHF-KOREAPEAR',      'K1.3 8 KOREAN PEAR'),
    ('1098', 'AHF-REDSCANDY',      'SPL 10kg AUS S CANDY PLUM'),
    ('1170', 'AHF-BLUEBERRY-J-10-2','1003.10 12x125g JUMBO BLUEBERRY'),
    ('120',  'AHF-PLUM-RED-10KG',  '1019.1 10kg AUS RED PLUM'),
    ('1212', 'AHF-BINGTANGLI-20',  'BT.3 18 BING TANG LI'),
    ('158',  'AHF-NECTARINE-10KG', '1042 10kg AUS NECTARINE'),
    ('311',  'AHF-ENVY-28',        '2033 28 USA ENVY APPLE'),
    ('343',  'AHF-USAPRO-56',      '3001.2 56 USA PROSPECT NAVEL'),
    ('344',  'AHF-USAPROS-72',     '3001.3 72 USA PROSPECT NAVEL'),
    ('420',  'AHF-ORANGE-EGY-40',  '3033.2 40 EGYPT ORANGE'),
    ('449',  'AHF-ORANGE-CARA-USA','3045.2 56 USA SUNKIST CARA CARA'),
    ('735',  'AHF-PEACH-18',       '7006.1 18 AUS WHITE PEACH'),
    ('778',  'AHF-HUNNYZ-1-1',     '9017 APPLE pcs'),
    ('783',  'B2C-GRAPE-GRN',      '9020 GREEN GRAPES 1kg pkt'),
    ('785',  'AHF-AVOCADO-S',      '9022 AVOCADO pcs'),
    ('790',  'AHF-POMEGRANATE-9',  '9026.3 8/10 SA POMEGRANATE'),
    ('921',  'AHF-LOCSWEET',       '2040-1 138 LOCSWEET ORANGE'),
]
# siblings sharing a target -> crosswalk to the primary SET Sku
SIBLINGS = {
    'AHF-BLUEBERRY-J-12': 'AHF-BLUEBERRY-J-10-2',
    'AHF-NZA1-1-1-1-1-1': 'AHF-HUNNYZ-1-1',
}
# SKIPPED (semantic doubt / no item) -> manual confirm list
SKIP = {
    'AHF-CHERRYTOMATOVINE-1': 'legacy row points to CUSTARD APPLE (wrong)',
    'AHF-PLUM-AMBER-10KG-2-2-1-1': 'points to SA SUGAR PRUNE (doubtful)',
    'AHF-SUNKIST-56-1': 'points to LOCSWEET ORANGE (doubtful)',
    'AHF-MULBERRY-1': 'no matching QBO item (code MUL .1)',
    'AHF-PLUM-AMBER-10KG': 'ambiguous — 3 items prefixed 9060',
}

ap = argparse.ArgumentParser(); ap.add_argument('--execute', action='store_true'); a = ap.parse_args()
env = tc.load_env(); q = tc.QBO(env)
print(f"[i] {'EXECUTE' if a.execute else 'DRY-RUN'}  repoint={len(REPOINT)} set={len(SETS)} sibling={len(SIBLINGS)} skip={len(SKIP)}")

# collision guard for SETs (primary WC SKU must not already be another item's Sku)
for iid, wc, nm in SETS:
    hit = [h for h in q.query(f"select Id,Name from Item where Sku='{wc}'").get('Item', []) if h['Id'] != iid]
    if hit: print(f"  ⚠ SET {iid} {wc}: COLLISION {hit}")
# validate repoint + sibling targets exist as real Sku
for tgt in set(list(REPOINT.values()) + list(SIBLINGS.values())):
    hit = q.query(f"select Id from Item where Sku='{tgt}'").get('Item', [])
    if not hit: print(f"  ⚠ target Sku missing (will be set this run if primary): {tgt}")

if not a.execute:
    for iid, wc, nm in SETS: print(f"  SET   item {iid} Sku={wc}  ({nm})")
    for wc, tgt in REPOINT.items(): print(f"  RPT   {wc} -> {tgt}")
    for wc, tgt in SIBLINGS.items(): print(f"  SIB   {wc} -> {tgt}")
    for wc, why in SKIP.items(): print(f"  SKIP  {wc}  ({why})")
    print("\n[i] dry-run only."); raise SystemExit

ts = datetime.now(timezone.utc).strftime('%Y-%m-%dT%H-%M-%SZ')
rb = {'sets': [], 'xwalk': []}
ok = err = 0
for iid, wc, nm in SETS:
    cur = q.query(f"select Id,Sku,SyncToken from Item where Id='{iid}'").get('Item', [])
    if not cur: print(f"  ✗ SET {iid} not found"); err += 1; continue
    url = f"{tc.QBO_BASE}/{q.realm}/item?minorversion=70"
    st, d = tc._req(url, 'POST', q._h(), json.dumps({'Id': iid, 'SyncToken': cur[0]['SyncToken'], 'sparse': True, 'Sku': wc}))
    if st == 200:
        ok += 1; rb['sets'].append({'id': iid, 'prior_sku': cur[0].get('Sku'), 'new_sku': wc}); print(f"  ✓ SET item {iid} Sku={wc}")
    else:
        err += 1; print(f"  ✗ SET item {iid} FAIL {str(d)[:100]}")

bak = m11.XWALK_CSV + f'.bak-{ts}'
open(bak, 'wb').write(open(m11.XWALK_CSV, 'rb').read()); print(f"  = crosswalk backed up -> {os.path.basename(bak)}")
desired = {}
for wc, tgt in {**REPOINT, **SIBLINGS}.items():
    desired[wc] = (tgt, '', '')
for ch in m11.apply_xwalk(desired, execute=True):
    rb['xwalk'].append(ch)
json.dump(rb, open(os.path.join(HERE, f'qbo-clear-residual-{ts}.applied.json'), 'w'), indent=2, ensure_ascii=False)
print(f"[i] done: {ok} sets, {err} failed, {len(desired)} crosswalk rows. Skipped {len(SKIP)} for manual.")
