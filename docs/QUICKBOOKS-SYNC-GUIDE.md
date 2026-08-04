# Ah Ho Fruit — Website → QuickBooks Sync
## Complete Guide & Handover

**Version:** 1.1
**Date:** 4 August 2026
**Applies to:** ahhofruit.com (WooCommerce) → QuickBooks Online, "AH HO FRUIT TRADING COMPANY"
**Runs on:** the Vodien web host — **not** on any Mac
**Last verified working:** 4 August 2026 (live dry-run against QuickBooks, twice)

---

## Table of Contents

1. [Executive Summary](#1-executive-summary)
2. [What It Does](#2-what-it-does)
3. [How Often It Runs](#3-how-often-it-runs)
4. [How to Check It's Working](#4-how-to-check-its-working)
5. [Understanding the Result](#5-understanding-the-result)
6. [Running a Sync by Hand](#6-running-a-sync-by-hand)
7. [Email Alerts](#7-email-alerts)
8. [Troubleshooting](#8-troubleshooting)
9. [Technical Architecture](#9-technical-architecture)
10. [Known Limitations & Open Decisions](#10-known-limitations--open-decisions)
11. [Appendix: File & Command Reference](#11-appendix-file--command-reference)

---

## 1. Executive Summary

Website orders are copied into QuickBooks automatically as **invoices**, so the shop's
online sales appear in the accounts without anyone re-typing them.

The sync runs **on the web host**, once a month, and closes off the previous month. It is
**safe to re-run** — it never creates the same order twice.

Wholesale (B2B) orders are **not** touched by this system.

**For Michelle:** you don't need to do anything. It runs by itself on the 1st of each month.
If you want to check, there's a page in the website admin that shows the last result — see
[section 4](#4-how-to-check-its-working).

---

## 2. What It Does

For every eligible website order, it creates one **Invoice** in QuickBooks:

| Element | Value |
|---|---|
| Document number | `woo-<order number>` — e.g. order #5224 becomes `woo-5224` |
| Customer | matched by email, created in QuickBooks if new |
| Product lines | matched by SKU to the QuickBooks item |
| Delivery | added as a separate "Delivery / Shipping" line |
| Card fee | added as a separate "Credit Card Fee" line where charged |
| GST | 9% SR, embedded (see below) |

### Which orders are included

| Order status | Synced? |
|---|---|
| Processing | Yes |
| Completed | Yes |
| Processing - B2B (`processing-b2b`) | **No** — wholesale is handled separately |
| Out for Delivery, Pending payment, Cancelled | **No** (see [section 10](#10-known-limitations--open-decisions)) |

### How GST is handled

Website prices are **GST-inclusive** — the customer pays one number and 9% is already inside it.
QuickBooks is set company-wide to add tax on top, so the sync sends the **net** amount
(gross ÷ 1.09) with tax code **SR 9%**. QuickBooks adds the 9% back, and the invoice total
matches exactly what the customer paid.

Worked example, order #5238:

```
Customer paid on the website : $100.00
Sent to QuickBooks (net)     :  $91.74
QuickBooks adds 9% SR        :   $8.26
Invoice total                : $100.00   ✓ matches
```

### Products it doesn't recognise

Products are matched by SKU, first against a mapping file of 283 products, then by looking the
SKU up live in QuickBooks. If a product genuinely cannot be matched, that **whole order** is
held back rather than recording a wrong amount — and it is reported as `blocked` so someone
can map it and re-run.

---

## 3. How Often It Runs

| When | What runs | Purpose |
|---|---|---|
| **1st of month, 09:00** | monthly sync | Closes off the previous month |
| **2nd of month, 09:00** | retry | Safety net if the 1st never fired. Normally does nothing and stays silent |
| **Every 22 minutes** | watcher | Picks up the "Send orders to QuickBooks now" button from the website admin |

So a website order placed in August normally reaches QuickBooks on **1 September**.
If it's needed sooner, press the button on that page ([section 6](#6-running-a-sync-by-hand)).

---

## 4. How to Check It's Working

### The easy way — the website admin

Log in to the website admin and go to **WooCommerce → QuickBooks Sync**.

The page shows the last run: when it ran, what period it covered, how many orders were added,
how many were already there, and anything that needs attention. There's a "Show technical log"
link for the full detail.

The bracketed note after the date says what started the run — `(automatic monthly run)`,
`(automatic follow-up check)` on the 2nd, or `(you pressed the button)`. Before 4 Aug 2026 this
only tested for the monthly run and labelled everything else a button press, so the safety net
reported itself as a manual action.

**What "healthy" looks like:** a recent run, some number of orders "already there", and
**zero** blocked and **zero** errors.

### The precise way — the result file on the server

`/home2/contactl/ahho-qbo/state/last-run.json` holds the full outcome of the last run.
`/home2/contactl/ahho-qbo/state/cron.log` holds one line per run:

```
posted=3 already=25 blocked=0 errors=0 emailed=True     <- 1 Aug 2026, monthly
posted=0 already=28 blocked=0 errors=0 emailed=False    <- 2 Aug 2026, retry
```

### Proving the connection still works, without changing anything

The sync is **dry-run by default** — it only writes to QuickBooks when given `--execute`.
A dry run is the safest possible health check: it connects, matches products, calculates
totals, and reports what it *would* do.

```bash
cd $HOME/ahho-qbo && python3 b2c-qbo-salesreceipt-sync.py --status completed --since 2026-08-01
```

If that prints order lines with `item=<number>` against each product, everything works:
the QuickBooks login, the product mapping and the GST maths are all proven in one go.

> **Run it on the server, never on a Mac.** See the warning in [section 9](#9-technical-architecture).

---

## 5. Understanding the Result

Every run reports four numbers:

| Term | Meaning | Action needed |
|---|---|---|
| `posted` | New invoices created in QuickBooks | None — this is the sync doing its job |
| `already` | Orders already in QuickBooks, skipped | None — this is the duplicate protection working |
| `blocked` | Orders held back because a product isn't matched | **Yes** — map the product, then re-run |
| `errors` | Orders that failed to send | **Yes** — read the log, fix, re-run |

`posted=0` is **not** a problem on its own. On the retry run it is the expected, healthy result —
it means the monthly run already did the work.

Re-running is always safe: orders already in QuickBooks are skipped by document number.

---

## 6. Running a Sync by Hand

**From the website admin (recommended):** WooCommerce → QuickBooks Sync → **Send orders to QuickBooks now**.
This drops a request that the watcher picks up within 22 minutes.

**On the server directly:**

```bash
cd $HOME/ahho-qbo && python3 run-sync.py manual
```

---

## 7. Email Alerts

A summary is emailed after month-end runs and after anything that needs a human. A clean
manual run stays silent deliberately — no inbox noise for a routine check.

- Recipients come from `QBO_ALERT_TO` in `~/ahho-qbo/.env` (comma-separated).
  **If it isn't set, no mail is sent** — the system never guesses an address.
- Delivery is attempted three ways in order — local mail program, then plain localhost SMTP,
  then the mail host over SSL — because a single mail route failing is how a failed sync
  goes unnoticed.
- If every route fails, the reason is appended to `state/notify-errors.log`. A failed
  notification never affects the sync itself.

---

## 8. Troubleshooting

| Symptom | Likely cause | Fix |
|---|---|---|
| Admin page shows an old run | The 1st-of-month cron didn't fire | Check cron jobs exist in cPanel; press the button on that page to catch up |
| `blocked` above zero | A product has no matching QuickBooks item | Map the SKU (or let the sync create it on the next `--execute` run), then re-run |
| `errors` above zero | QuickBooks rejected something | Read `state/sync-<month>.log` for the order and the message |
| `invalid_grant` when running locally | Expected — see the warning below | Run on the server instead |
| No alert email | `QBO_ALERT_TO` unset, or all three mail routes failed | Check `state/notify-errors.log` |
| An order never appears | Its status isn't Processing or Completed | See the status table in [section 2](#2-what-it-does) |

---

## 9. Technical Architecture

```
Vodien host  /home2/contactl/ahho-qbo/          (outside the web root, dir 0700, .env 0600)
  ├── run-sync.py                    orchestrates a run, writes state, sends the alert
  ├── b2c-qbo-salesreceipt-sync.py   does the work: fetch orders -> build lines -> post
  ├── watch-trigger.sh               picks up the admin "Send orders now" request
  ├── QBO-WC-Crosswalk-FINAL-*.csv   283 SKU -> QuickBooks code mappings
  └── state/
      ├── last-run.json              full result of the last run (rendered in wp-admin)
      ├── cron.log                   one summary line per run
      ├── sync-YYYY-MM.log           full per-order detail
      └── notify-errors.log          alert-email failures only
```

- **Language:** Python, standard library only — no packages to install or keep updated.
- **WooCommerce side:** read via the WooCommerce REST API (`WC_CONSUMER_KEY` / `SECRET`).
  The order fetch is paginated; the API caps a page at 100, so a long gap would otherwise
  silently drop the oldest orders.
- **QuickBooks side:** `quickbooks.api.intuit.com/v3/company/<realm>` with `minorversion=70`.
- **Admin page:** `wp-content/plugins/ah-ho-custom/includes/qbo-sync-admin.php`, registered under
  WooCommerce. It uses an **absolute** path to `~/ahho-qbo` on purpose — the live site sits at
  `public_html/ah-ho-fruit/`, one level deeper than a path derived from `ABSPATH` would assume.
- **No SSH shell** on this hosting account. Use `tools/cpanel.py` (cPanel API) for files and cron.

### ⚠️ These files deploy by two different routes

A `git push` does **not** update the sync. Only the wp-admin page ships that way.

| File | How it reaches the server |
|---|---|
| `wp-content/plugins/ah-ho-custom/includes/qbo-sync-admin.php` | `git push` → GitHub Actions → FTP |
| `server/run-sync.py`, `b2c-qbo-salesreceipt-sync.py`, the crosswalk | **cPanel upload only** — `server/**` and `**/*.py` are excluded from the deploy so they never land in the public web root |

Caught the hard way on 2026-08-04: a label was fixed in both files and pushed, which corrected
the admin page while the monthly email kept sending the old wrong text. Upload the Python
separately and confirm it compiles on the server afterwards.

### ⚠️ The refresh token — the one thing that can break everything

Intuit issues a **new refresh token on every single connection** and invalidates the old one.
The server holds the live token and writes each new one back.

**Never run the sync on a Mac with `--execute`.** It will either fail with `invalid_grant` or
succeed, take the new token for itself, and leave the live sync permanently broken. A
`invalid_grant` error from a local machine is expected and means nothing is wrong on the server.

Verified 4 Aug 2026: two consecutive server runs both succeeded, confirming the rotated token
is being persisted correctly.

---

## 10. Known Limitations & Open Decisions

1. **Invoices are created unpaid.** Website orders are prepaid by PayNow, but they arrive in
   QuickBooks with the full amount outstanding:
   `Invoice #51406  Total=$80.00  Balance=$80.00 (unpaid)`.
   Unless each is cleared against the bank deposit, receivables will show website sales as money
   still owed. **This needs confirming with the bookkeeper** — it may be the intended manual
   reconciliation, or it may need the sync to record payment as well.

2. **Orders in custom statuses aren't synced.** Only Processing and Completed are picked up.
   An order left in "Out for Delivery" never reaches QuickBooks until it moves to Completed.

3. **Up to a month's lag.** By design the sync closes the previous month. An order placed on
   2 August reaches QuickBooks on 1 September unless someone presses the button on that page.

4. **B2B is a separate system.** Wholesale orders (`processing-b2b`) are explicitly excluded here.

---

## 11. Appendix: File & Command Reference

**Cron jobs (cPanel → Cron Jobs):**

```
0 9 1 * *    /usr/bin/python3 $HOME/ahho-qbo/run-sync.py monthly >> $HOME/ahho-qbo/state/cron.log 2>&1
0 9 2 * *    /usr/bin/python3 $HOME/ahho-qbo/run-sync.py retry   >> $HOME/ahho-qbo/state/cron.log 2>&1
*/22 * * * * $HOME/ahho-qbo/watch-trigger.sh
```

**Useful commands** (all from `$HOME/ahho-qbo` on the server):

```bash
python3 b2c-qbo-salesreceipt-sync.py --status completed --since 2026-08-01   # dry run, safe
python3 b2c-qbo-salesreceipt-sync.py --status completed --since 2026-08-01 --execute
python3 b2c-qbo-salesreceipt-sync.py --only 5224                             # one order
python3 run-sync.py manual                                                   # full run + alert
```

**Credentials** live in `~/ahho-qbo/.env` on the server (mode 0600) and are never in the
website folder or in git. The local copy in `ah-ho-fruits/.env` is **not** authoritative for
the QuickBooks token.

---

*Related: `server/README.md` (short operational notes) · `docs/AH-HO-FRUITS-SYSTEM-DOCUMENTATION.md` (whole system).*
