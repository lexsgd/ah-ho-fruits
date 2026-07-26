#!/bin/bash
# Monthly WooCommerce -> QuickBooks sync (runs 1st of each month via launchd).
#
# Posts the PREVIOUS month's paid B2C orders as unpaid QBO invoices so Michelle
# can close month-end. Safe to run any number of times: the sync skips orders
# whose DocNumber (woo-<id>) already exists in QuickBooks.
#
# Manual run:  ~/ah-ho-fruits/qbo-monthly-sync.sh
# Log:         ~/ah-ho-fruits/logs/qbo-sync-YYYY-MM.log
# Exit code:   0 = clean, 1 = something needs a human (see log)

set -uo pipefail
HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$HERE" || exit 1

# Never reach back past go-live. QBO rejects writes into a closed accounting
# period ("Account Period Closed") — June 2026 and earlier are closed.
FLOOR="2026-07-01"

mkdir -p logs
PREV_MONTH="$(date -v-1m +%Y-%m-01)"
SINCE="$PREV_MONTH"
[[ "$SINCE" < "$FLOOR" ]] && SINCE="$FLOOR"
LOG="logs/qbo-sync-$(date +%Y-%m).log"

blocked=0
errors=0
{
  echo "================================================================"
  echo "run: $(date '+%Y-%m-%d %H:%M:%S %Z')   window: --since $SINCE"
  echo "================================================================"

  for status in completed processing; do
    echo "--- status=$status ---"
    out="$(/usr/bin/python3 b2c-qbo-salesreceipt-sync.py \
             --status "$status" --limit 100 --since "$SINCE" --execute 2>&1)"
    echo "$out"
    # An unmapped product holds the whole order; a POST failure loses it entirely.
    # Both mean "did not record" and both need a human.
    blocked=$((blocked + $(printf '%s' "$out" | grep -c '\[BLOCKED\]')))
    errors=$((errors + $(printf '%s' "$out" | grep -c '\[error\]')))
  done

  echo
  if [ "$blocked" -gt 0 ] || [ "$errors" -gt 0 ]; then
    echo "*** NEEDS ATTENTION — orders did NOT record:"
    [ "$blocked" -gt 0 ] && echo "***   $blocked blocked (unmapped product; map it in QBO, then re-run)"
    [ "$errors" -gt 0 ]  && echo "***   $errors failed to post (see [error] lines above)"
  else
    echo "clean: every order in the window posted or was already present."
  fi
  echo
} >> "$LOG" 2>&1

if [ "$blocked" -gt 0 ] || [ "$errors" -gt 0 ]; then
  exit 1
fi
exit 0
