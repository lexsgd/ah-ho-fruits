# Where the QuickBooks sync runs

> Operational notes only. The full guide — what it does, how often, how to check it,
> what the numbers mean, troubleshooting — is `docs/QUICKBOOKS-SYNC-GUIDE.md`.

**It runs on Vodien, not on any Mac.** Do not run `b2c-qbo-salesreceipt-sync.py`
locally with `--execute`: Intuit rotates the refresh token on every refresh and
the server persists the new one, so a local run will either fail with
`invalid_grant` or steal the token and break the live sync.

    Live install : /home2/contactl/ahho-qbo/        (outside the web root, dir 0700, .env 0600)
    Monthly cron : 1st of month 09:00 -> run-sync.py monthly
    Watcher cron : every 5 min       -> watch-trigger.sh  (picks up the wp-admin button)
    Results      : state/last-run.json, rendered at WooCommerce > QuickBooks Sync

## Important: the live docroot is NOT public_html

    live site : /home2/contactl/public_html/ah-ho-fruit/     <-- ahhofruit.com
    stale copy: /home2/contactl/public_html/                 <-- different WP, do not deploy here

`deploy.sh` still points at `public_html`; the GitHub Actions secret `VODIEN_PATH`
is the one that matters. Verify the target before any deploy.

## No shell access

SSH shell is disabled on the account. Use `tools/cpanel.py` (cPanel API) for
files/cron. Note `%` is special in crontab — escape it or avoid it in commands
run via `cpanel.py sh`.
