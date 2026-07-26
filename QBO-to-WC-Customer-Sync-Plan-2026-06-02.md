# QBO → WooCommerce Customer Sync — Execution Plan

**Date:** 2026-06-02
**Goal:** Import QuickBooks customers into WooCommerce, on top of existing WC customers (no duplicates, no data loss).
**Status:** ✅ DONE 2026-06-02 — all 692 imported (WC total 36→728), idempotent (re-run = updates, no dupes). Addresses cleaned: postcode in own field (610/676 = 90% have one; structured `PostalCode` + free-text parse), city=Singapore, country=SG, street/unit split, phone notes stripped. Tagged readable `qbo_customer_id` (NOT `_`-prefixed — WC REST hides those). New-account email suppressed during import then re-enabled. Tools: `qbo-to-wc-customers.py` (dry-run default, `--execute` to apply). WC REST key in `.env` (`WC_CONSUMER_KEY/SECRET`).
**Decisions locked (2026-06-02):** Sync **all 692** (the website is also used for **B2B orders** — Michelle picks these customers when creating orders). Emailless customers get a **placeholder email** `enquiry+<display-name-slug>@ahhofruit.com` (e.g. `enquiry+1168-fresh-fruits-trading@ahhofruit.com`) — readable, deliverable to enquiry@ via plus-addressing. DisplayNames are 100% unique; the 3 slug-collision pairs (differ only by trailing punctuation) get the QBO id appended to stay unique. Preserve full QBO DisplayName as the WC name/company for searchability.

---

## 1. The data reality (from the verified QBO backup)

692 QBO customers analysed (`qbo-backups/2026-06-02T09-19-12Z/Customer.json`):

| Field | Coverage |
|-------|----------|
| **Email** | **31 / 692 (4%)** — only **27 unique** |
| Phone | 159 | Mobile | 32 |
| Billing address | 676 (98%) |
| Shipping address | 232 |
| Company name | 6 |
| Outstanding balance | 206 |
| Sub-customers (Job) | 1 |

**These are B2B/trade accounts** (DisplayNames like "1168 FRESH FRUITS TRADING", "152 BISHAN") with a leading numeric customer code — invoiced offline, not consumers with logins.

### The core constraint
WooCommerce customer accounts are **keyed on a unique email** (required field). **96% of QBO customers have no email** → they cannot become standard WC customer accounts without fabricated data. This is the central design decision (Open Question #1).

---

## 2. Field mapping (QBO Customer → WC Customer)

| QBO field | WC field | Notes |
|-----------|----------|-------|
| `PrimaryEmailAddr.Address` **or** `enquiry+<slug(DisplayName)>@ahhofruit.com` | `email` | Real email if present; else **placeholder** = slugified DisplayName (lowercase, non-alnum→hyphen). Append `-<QBO-id>` only on the 3 slug collisions. Deliverable to enquiry@. |
| `DisplayName` (full, incl. numeric code) | `last_name` **and** `billing.company` | **Preserve as-is** so Michelle's order-screen search matches QuickBooks exactly. `first_name` left blank. |
| `PrimaryPhone` / `Mobile` | `billing.phone` | |
| `BillAddr.{Line1,City,PostalCode,CountrySubDivisionCode}` | `billing.{address_1,city,postcode,state}` | `country` = `SG`. |
| `ShipAddr.*` | `shipping.*` | Fallback to billing if absent. |
| `Id` (QBO customer id) | `meta_data._qbo_customer_id` | **Idempotency + order-linking + rollback tag.** |
| — | `meta_data._qbo_placeholder_email` | `true` when email was synthesised (so we can find/fix later if a real email arrives). |
| `Notes` | `meta_data._qbo_notes` | optional |
| — | `meta_data._qbo_synced_at` | ISO timestamp of this run |

**Email-suppression:** disable WooCommerce "New account" + customer order emails during the bulk import, restore after, to avoid flooding enquiry@. Run a one-off deliverability test to `enquiry+test@ahhofruit.com` first to confirm Vodien passes plus-addressed mail.

---

## 3. Dedup / "on top of existing" merge logic

For each QBO customer **with a usable email**:
1. `GET /wc/v3/customers?email=<email>` — does it already exist in WC?
2. **Exists** → `update`: fill only **empty** WC fields (never overwrite data the customer set themselves), add `_qbo_customer_id` meta. Non-destructive.
3. **New** → `create` with role `customer`.
4. Match key = lowercased email. The 1 duplicate-email value in QBO is de-duped before sync.

No QBO customer is ever created twice (guarded by `_qbo_customer_id` meta + email lookup → fully **idempotent**, safe to re-run).

---

## 4. Safety / rollback (same discipline as the QBO backup)

- **Pre-sync WC backup:** dump ALL existing WC customers to JSON first (`wc-backups/<ts>/customers.json`). Mirrors the QBO backup.
- **Dry-run first:** script reports `create=N, update=M, skip(no email)=K` and writes the full proposed diff to a file for review — **no writes** until approved.
- **Tagging:** every record we touch gets `_qbo_customer_id` + `_qbo_synced_at` → we can always identify exactly what the sync did.
- **Rollback:**
  - Created-by-us customers → delete by `_qbo_customer_id` (only ones with no orders).
  - Updated customers → restore from the pre-sync WC backup.
- **Batch + rate-limit:** `POST /customers/batch` (≤100/call), small batches, retries with backoff.

---

## 5. WooCommerce API access (one-time setup)

No WC REST keys exist yet (current scripts use WP-admin browser automation). Setup:
- Generate a **REST API key pair** (Read/Write) via WP Admin → WooCommerce → Settings → Advanced → REST API (we have admin creds), OR use a WordPress Application Password.
- Store as `WC_CONSUMER_KEY` / `WC_CONSUMER_SECRET` in gitignored `.env`.
- Auth: HTTP Basic over HTTPS. Base: `https://ahhofruit.com/wp-json/wc/v3/`.

---

## 6. Phased execution (once Open Question #1 is decided)

1. **Setup** — generate WC REST keys; confirm reachable; count existing WC customers.
2. **Backup** — dump existing WC customers to JSON.
3. **Transform + dry-run** — map QBO→WC, dedup, output proposed diff. **Review with Lex.**
4. **Execute** — batch create/update; tag everything.
5. **Verify** — re-count; spot-check 5 created + 5 updated; confirm no duplicates.
6. **Report** — summary + rollback instructions on standby.

---

## Open Questions (need Lex's decision)

**#1 — How to handle the 665 emailless QBO customers? (blocks execution)**
- **A. Skip them** — sync only the ~27 with real emails. Clean, safe, but small. *(recommended)*
- **B. Synthesised placeholder emails** (e.g. `qbo<id>@noemail.ahhofruit.com`) — creates all 692 as WC accounts, but they can't log in / receive mail, pollute the customer list, and risk bounce/spam if WC emails them. *(not recommended for a B2C store)*
- **C. CSV reference export only** — export the 665 emailless as a reference list for the team; create WC accounts only for the emailed ones.

**#2 — Is this even the right goal?** The QBO customers are B2B trade accounts that buy by carton offline; the website is B2C/residential. Worth a sanity check on what having them in WooCommerce actually buys us before we run it.

**#3 — Scope filter?** All 692, or only `Active` + those with a billing address / recent activity?
