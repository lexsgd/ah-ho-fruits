#!/usr/bin/env python3
"""
Server-side runner for the WooCommerce -> QuickBooks sync (Vodien, python3.9).

Two entry points, both landing here:
  * monthly  — cPanel cron on the 1st, closes the previous month
  * manual   — Michelle clicks "Sync now" in wp-admin, which drops a trigger
               file; the watcher cron sees it and runs us

Writes state/last-run.json for the wp-admin page to render, so Michelle can see
WHY an order did not record, not just that it did not.

Usage:  python3 run-sync.py monthly
        python3 run-sync.py manual
"""
import os, sys, json, re, subprocess
from datetime import datetime, timezone, date

HERE = os.path.dirname(os.path.abspath(__file__))
STATE = os.path.join(HERE, 'state')
SYNC = os.path.join(HERE, 'b2c-qbo-salesreceipt-sync.py')

# QBO rejects writes into a closed accounting period. June 2026 and earlier are
# closed, so never build a window that reaches back past go-live.
FLOOR = '2026-07-01'
STATUSES = ('completed', 'processing')


def since_date():
    """First day of the previous month, never earlier than FLOOR."""
    t = date.today()
    prev = date(t.year - 1, 12, 1) if t.month == 1 else date(t.year, t.month - 1, 1)
    return max(prev.isoformat(), FLOOR)


def run(status, since):
    p = subprocess.run([sys.executable, SYNC, '--status', status,
                        '--limit', '100', '--since', since, '--execute'],
                       cwd=HERE, capture_output=True, text=True, timeout=900)
    return (p.stdout or '') + (p.stderr or '')


def main():
    trigger = sys.argv[1] if len(sys.argv) > 1 else 'manual'
    os.makedirs(STATE, exist_ok=True)
    since = since_date()
    started = datetime.now(timezone.utc)

    out = []
    for s in STATUSES:
        out.append(f"--- status={s} ---")
        try:
            out.append(run(s, since))
        except subprocess.TimeoutExpired:
            out.append("[error] sync timed out after 15 min")
    text = "\n".join(out)

    posted = len(re.findall(r'✅ Invoice', text))
    skipped = len(re.findall(r'already in QBO', text))
    blocked = re.findall(r'\[BLOCKED\] unmapped product: (.+)', text)
    errors = re.findall(r'\[error\] (.+)', text)

    result = {
        'finished': datetime.now(timezone.utc).isoformat(timespec='seconds'),
        'started': started.isoformat(timespec='seconds'),
        'trigger': trigger,
        'since': since,
        'posted': posted,
        'already_there': skipped,
        # Both mean "did not record" and both need a human.
        'blocked': [b.strip() for b in blocked],
        'errors': [e.strip()[:200] for e in errors],
        'ok': not blocked and not errors,
        'log': text[-20000:],
    }

    with open(os.path.join(STATE, 'last-run.json'), 'w') as f:
        json.dump(result, f, indent=2, ensure_ascii=False)

    month_log = os.path.join(STATE, f"sync-{datetime.now(timezone.utc):%Y-%m}.log")
    with open(month_log, 'a') as f:
        f.write(f"\n===== {result['finished']}  trigger={trigger}  since={since} =====\n")
        f.write(text + "\n")

    print(f"posted={posted} already={skipped} blocked={len(blocked)} errors={len(errors)}")
    return 0 if result['ok'] else 1


if __name__ == '__main__':
    sys.exit(main())
