#!/usr/bin/env python3
"""Apply the 27 clearly-correct WC-SKU->QBO-code crosswalk rows (the 'QBO item HAS
a code' website-tweak bucket that resolves my side instead of Michelle editing the
website). Targets pre-validated as real QBO Skus. Update-or-append, CSV backed up.
Reuses apply_xwalk() from qbo-apply-michelle-11.py. Dry-run default; --execute to write."""
import os, json, argparse, importlib.util
from datetime import datetime, timezone
HERE = os.path.dirname(os.path.abspath(__file__))
spec = importlib.util.spec_from_file_location('m11', os.path.join(HERE, 'qbo-apply-michelle-11.py'))
m11 = importlib.util.module_from_spec(spec); spec.loader.exec_module(m11)

desired_raw = json.load(open('/tmp/ahho-safe-xwalk.json'))       # {wc: [code, prod, qboname]}
desired = {wc: (v[0], v[1], v[2]) for wc, v in desired_raw.items()}

ap = argparse.ArgumentParser(); ap.add_argument('--execute', action='store_true'); a = ap.parse_args()
print(f"[i] {'EXECUTE' if a.execute else 'DRY-RUN'}  crosswalk rows: {len(desired)}")
for ch in m11.apply_xwalk(desired, execute=False):
    tag = {'append': 'APPEND', 'update': f"UPDATE(was {ch['old']!r})", 'ok': 'already-correct'}[ch['action']]
    print(f"  {ch['wc']:24} -> {ch['new']:22} [{tag}]")
if not a.execute:
    print("\n[i] dry-run only."); raise SystemExit
ts = datetime.now(timezone.utc).strftime('%Y-%m-%dT%H-%M-%SZ')
bak = m11.XWALK_CSV + f'.bak-{ts}'
with open(m11.XWALK_CSV, 'rb') as s, open(bak, 'wb') as d: d.write(s.read())
print(f"  = backed up -> {os.path.basename(bak)}")
changes = m11.apply_xwalk(desired, execute=True)
json.dump(changes, open(os.path.join(HERE, f'qbo-safe-xwalk27-{ts}.applied.json'), 'w'), indent=2, ensure_ascii=False)
n = {k: sum(1 for c in changes if c['action'] == k) for k in ('append', 'update', 'ok')}
print(f"[i] done: {n['append']} appended, {n['update']} updated, {n['ok']} already-correct.")
