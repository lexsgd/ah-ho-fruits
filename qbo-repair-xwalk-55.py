#!/usr/bin/env python3
"""Repair the 55 auto-repairable broken crosswalk rows: each row's "QBO code" was a
QBO item-number PREFIX (not a Sku); the prefix uniquely identifies one QBO item that
DOES have a Sku. Repoint the crosswalk "QBO code" to that real Sku. Deterministic +
reversible. LIVE-validates every target Sku exists before writing. CSV backed up.
Reuses apply_xwalk() from qbo-apply-michelle-11.py. Dry-run default; --execute writes."""
import os, json, argparse, importlib.util
from datetime import datetime, timezone
HERE = os.path.dirname(os.path.abspath(__file__))
spec = importlib.util.spec_from_file_location('m11', os.path.join(HERE, 'qbo-apply-michelle-11.py'))
m11 = importlib.util.module_from_spec(spec); spec.loader.exec_module(m11)
tc = m11.tc

rem = json.load(open('/tmp/ahho-xwalk-remediation.json'))
# repairable entries: [wc, bad_prefix_code, target_sku, qbo_name]
desired = {}
for wc, code, sku, name in rem['repairable']:
    if wc:                       # skip any blank-WC rows
        desired[wc] = (sku, '', name)

ap = argparse.ArgumentParser(); ap.add_argument('--execute', action='store_true'); a = ap.parse_args()
print(f"[i] {'EXECUTE' if a.execute else 'DRY-RUN'}  repair rows: {len(desired)}")

# LIVE-validate each target Sku actually exists in QBO now (guard against another 9049 class)
env = tc.load_env(); q = tc.QBO(env)
targets = sorted({v[0] for v in desired.values()})
missing = []
for s in targets:
    esc = s.replace("'", "\\'")
    hit = q.query(f"select Id,Name from Item where Sku = '{esc}'").get('Item', [])
    if not hit:
        missing.append(s)
if missing:
    print(f"[!] {len(missing)} target Skus DO NOT exist live — aborting to avoid new broken rows:")
    for s in missing: print(f"    {s}")
    # drop rows whose target is missing rather than write a broken one
    desired = {wc: v for wc, v in desired.items() if v[0] not in set(missing)}
    print(f"[i] proceeding with {len(desired)} validated rows only")
else:
    print(f"[i] all {len(targets)} target Skus validated live ✓")

for ch in m11.apply_xwalk(desired, execute=False):
    tag = {'append': 'APPEND', 'update': f"UPDATE(was {ch['old']!r})", 'ok': 'already-correct'}[ch['action']]
    print(f"  {ch['wc'][:26]:26} -> {ch['new']:22} [{tag}]")
if not a.execute:
    print("\n[i] dry-run only."); raise SystemExit

ts = datetime.now(timezone.utc).strftime('%Y-%m-%dT%H-%M-%SZ')
bak = m11.XWALK_CSV + f'.bak-{ts}'
with open(m11.XWALK_CSV, 'rb') as s, open(bak, 'wb') as d: d.write(s.read())
print(f"  = backed up -> {os.path.basename(bak)}")
changes = m11.apply_xwalk(desired, execute=True)
json.dump(changes, open(os.path.join(HERE, f'qbo-repair-xwalk55-{ts}.applied.json'), 'w'), indent=2, ensure_ascii=False)
n = {k: sum(1 for c in changes if c['action'] == k) for k in ('append', 'update', 'ok')}
print(f"[i] done: {n['append']} appended, {n['update']} updated, {n['ok']} already-correct.")
