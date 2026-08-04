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
import os, sys, json, re, smtplib, ssl, subprocess
from email.message import EmailMessage
from datetime import datetime, timezone, date

HERE = os.path.dirname(os.path.abspath(__file__))
STATE = os.path.join(HERE, 'state')
SYNC = os.path.join(HERE, 'b2c-qbo-salesreceipt-sync.py')

# QBO rejects writes into a closed accounting period, so never build a window
# that reaches into one. Move this forward as Michelle closes each month.
#   2026-07-01  go-live: June 2026 and earlier were closed
#   2026-08-01  2026-08-04, Michelle: "I closed the July orders already today"
# Costs nothing to raise: every July order was already in QuickBooks before she
# closed it, so there is nothing back there left to sync.
FLOOR = '2026-08-01'
STATUSES = ('completed', 'processing')


def since_date():
    """First day of the month before last, never earlier than FLOOR.

    Two months, not one, and deliberately so. The window is chosen by ORDER
    DATE, but an order can be marked Completed weeks after it was placed — and
    only Completed/Processing orders are synced. With a one-month window a July
    order first completed in mid-August fell below the September run's cutoff
    while July's own runs were long past, so it reached QuickBooks never.

    Re-scanning the extra month costs nothing: anything already in QuickBooks is
    skipped by document number, which is also why re-running is always safe.

    FLOOR still applies, so this can never reach into a period closed before
    go-live. Note that if a month is closed in QuickBooks after its orders have
    synced, a straggler from it will be rejected by QBO and reported as an
    error rather than lost silently — which is the outcome we want.
    """
    t = date.today()
    month = t.month - 2
    year = t.year
    if month <= 0:
        month += 12
        year -= 1
    return max(date(year, month, 1).isoformat(), FLOOR)


def run(status, since):
    # 500, not 100: the fetch pages now, so this is a real ceiling rather than a
    # silent truncation. Ah Ho does ~25 orders/month, so this covers a long gap.
    p = subprocess.run([sys.executable, SYNC, '--status', status,
                        '--limit', '500', '--since', since, '--execute'],
                       cwd=HERE, capture_output=True, text=True, timeout=900)
    return (p.stdout or '') + (p.stderr or '')


def load_env():
    e = {}
    p = os.path.join(HERE, '.env')
    if os.path.exists(p):
        for line in open(p):
            line = line.strip()
            if line and not line.startswith('#') and '=' in line:
                k, v = line.split('=', 1)
                e[k.strip()] = v.strip().strip('"').strip("'")
    return e


def notify(result, env):
    """Email the outcome. Silent on a clean manual run — only month-end
    summaries and anything needing a human are worth an inbox interruption.

    Recipients come from QBO_ALERT_TO (comma-separated) in .env; if it is not
    set we send nothing rather than guess an address.
    """
    to = [a.strip() for a in (env.get('QBO_ALERT_TO') or '').split(',') if a.strip()]
    needs_human = not result['ok']
    # 'retry' is the safety net that runs on the 2nd in case the 1st never fired.
    # Normally it finds nothing to do and stays quiet; if it DID post something,
    # the monthly run failed silently and that is worth knowing about.
    worth_sending = (needs_human
                     or result['trigger'] == 'monthly'
                     or (result['trigger'] == 'retry' and result['posted'] > 0))
    if not to or not worth_sending:
        return False

    n_posted, n_there = result['posted'], result['already_there']
    if needs_human:
        subject = 'Ah Ho: some website orders did NOT reach QuickBooks'
    else:
        subject = f"Ah Ho: {n_posted} website order(s) added to QuickBooks"

    # Three triggers, not two. Anything-but-monthly used to be reported as a
    # button press, which told Michelle she had started the 2nd-of-month safety
    # net herself. The button is also named "Send orders to QuickBooks now" —
    # there has never been a "Sync now" button to press.
    started_by = {
        'monthly': 'automatic monthly run',
        'retry':   'automatic follow-up check',
        'manual':  'the "Send orders to QuickBooks now" button',
    }.get(result['trigger'], result['trigger'])

    lines = [
        "Website orders -> QuickBooks" ,
        "",
        f"When    : {result['finished']} (UTC)",
        f"Started by: {started_by}",
        f"Covering: orders from {result['since']} onwards",
        "",
        f"Added to QuickBooks : {n_posted} new invoice(s)",
        f"Already there       : {n_there} (skipped, no duplicates)",
    ]
    if result['blocked']:
        lines += ["", "NOT RECORDED - these products aren't matched to QuickBooks yet,",
                  "so their whole order was held back rather than recording a wrong amount:"]
        lines += [f"  - {b}" for b in result['blocked']]
        lines += ["", "Send this list to Lex to match up, then press the button again."]
    if result['errors']:
        lines += ["", "FAILED TO SEND (please forward to Lex):"]
        lines += [f"  - {e}" for e in result['errors']]
    if result['ok']:
        lines += ["", "All good - every order in the period is now in QuickBooks."]
    lines += ["", "See WooCommerce > QuickBooks Sync in the website admin for detail."]

    msg = EmailMessage()
    msg['Subject'] = subject
    msg['From'] = env.get('VODIEN_MAIL_USER', 'enquiry@ahhofruit.com')
    msg['To'] = ', '.join(to)
    msg.set_content("\n".join(lines))

    # A missed alert is how a failed sync goes unnoticed, so don't rely on one
    # transport: local MTA first, then plain localhost SMTP, then the SSL host.

    def via_sendmail():
        p = subprocess.run(['/usr/sbin/sendmail', '-t', '-oi'],
                           input=msg.as_bytes(), capture_output=True, timeout=60)
        if p.returncode != 0:
            raise RuntimeError(f"sendmail rc={p.returncode} {p.stderr[:200]!r}")

    def via_smtp(host, port, use_ssl, login):
        cls = smtplib.SMTP_SSL if use_ssl else smtplib.SMTP
        kw = {'context': ssl.create_default_context()} if use_ssl else {}
        with cls(host, port, timeout=60, **kw) as s:
            if not use_ssl:
                try:
                    s.starttls(context=ssl.create_default_context())
                except Exception:
                    pass
            if login:
                s.login(env['VODIEN_MAIL_USER'], env['VODIEN_MAIL_PASS'])
            s.send_message(msg)

    attempts = [
        ('sendmail', via_sendmail),
        ('localhost:587', lambda: via_smtp('localhost', 587, False, True)),
        ('ssl-host', lambda: via_smtp(env.get('VODIEN_MAIL_HOST', ''),
                                      int(env.get('VODIEN_MAIL_PORT', 465)), True, True)),
    ]
    errors_seen = []
    try:
        for kind, send in attempts:
            try:
                send()
                return True
            except Exception as exc:
                errors_seen.append(f"{kind}: {exc!r}")
        raise RuntimeError('; '.join(errors_seen))
    except Exception as exc:
        # A failed notification must never mask the sync's own result — and the
        # logging of that failure must not throw either (STATE may not exist yet).
        try:
            os.makedirs(STATE, exist_ok=True)
            with open(os.path.join(STATE, 'notify-errors.log'), 'a') as f:
                f.write(f"{datetime.now(timezone.utc).isoformat()} {exc!r}\n")
        except Exception:
            pass
        return False


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

    sent = notify(result, load_env())

    print(f"posted={posted} already={skipped} blocked={len(blocked)} "
          f"errors={len(errors)} emailed={sent}")
    return 0 if result['ok'] else 1


if __name__ == '__main__':
    sys.exit(main())
