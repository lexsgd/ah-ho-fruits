#!/bin/bash
# Runs every 5 minutes from cPanel cron. If wp-admin dropped a trigger file
# (Michelle clicked "Sync now"), consume it and run the sync.
#
# The trigger is removed BEFORE the sync runs, so a long sync cannot be
# re-triggered by the next tick, and a click during a run queues one more pass.
set -uo pipefail
HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
TRIGGER="$HERE/state/trigger"
LOCK="$HERE/state/.running"

[ -f "$TRIGGER" ] || exit 0

# Don't stack runs: two syncs at once would race on the rotating refresh token.
if [ -f "$LOCK" ] && [ -n "$(find "$LOCK" -mmin -20 2>/dev/null)" ]; then
  exit 0
fi

rm -f "$TRIGGER"
touch "$LOCK"
/usr/bin/python3 "$HERE/run-sync.py" manual >> "$HERE/state/watcher.log" 2>&1
rm -f "$LOCK"
exit 0
