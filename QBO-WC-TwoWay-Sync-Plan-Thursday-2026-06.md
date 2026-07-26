# WooCommerce ↔ QuickBooks Two-Way Sync — Planning Doc (for Thursday)

**Date:** 2026-06-02 (updated 2026-06-04) · **Status:** SCOPE LOCKED — Option B · **Goal:** customers + orders sync, free, no Lex maintenance.

> **DECISION (2026-06-04) — Option B "free/simple":**
> - **Tool:** free Intuit WooCommerce Connector (OneSaas) — free + Intuit-maintained, no Lex upkeep. ✅ **SG availability CONFIRMED 2026-06-04** — visible + installable ("Get integration now") in Ah Ho's SG QBO app marketplace; Developer = Intuit; SG/GST edition (setup screen shows GST + Tax settings). Our EVDance app connection already shows under "My integrations".
> - **Customers:** QBO→WC done (728); new WC customers → QBO via connector.
> - **Orders:** WC orders auto-create as **GST-exclusive invoices** in QBO. **Michelle manually adds the 9% GST line + salesperson Class** per invoice (same as today, but invoice is pre-created → less work). GST + commission stay accurate, just manual.
> - **Stock:** website-only (no QBO inventory).
> - **QBO invoices → WC:** OFF.
> - **Prep work (the real task):** map the 316 WC products ↔ QBO items — write WC SKUs into QBO items' empty SKU field (we have API access) so the connector matches by SKU. Cartons done by Michelle; we draft the loose/by-piece ones.
> - **Not doing** (would need paid custom build): auto-GST, auto-Class, inventory sync.

---

## 1. Current state (verified today)

| Thing | Reality |
|---|---|
| **Order sync WC→QBO** | **Never done.** 0 of 21,825 QBO invoices reference a WC order. The Feb "quickbooks online – API" key was an abandoned trial. Greenfield. |
| **QBO role** | The *real* accounting system — 21,825 invoices, created directly in QBO (incl. B2B done offline), dated through Jun 2026. |
| **WC role** | The storefront — **67 orders** (62 completed), **316 products**, ~99% with SKUs (`AHF-…`), stock managed in WC. |
| **Customers** | ✅ Synced QBO→WC on 2026-06-02 (728 total, tagged `qbo_customer_id`). |
| **Catalog link** | ❌ **None.** WC products use SKUs (`AHF-BANANA-18KG`); QBO's 1,160 items have **no SKU at all** and numeric-code names ("0001"). No shared key. |
| **QBO inventory** | ❌ Items are **Non-inventory** — QBO does not track stock today. |

---

## 2. The goal, scoped honestly

"Two-way sync of everything" needs reframing into what's actually valuable AND safe, per entity:

| Entity | Realistic direction | Notes |
|---|---|---|
| **Customers** | QBO→WC ✅ done; **WC→QBO** for new website signups (add) | Two-way, low risk. |
| **Orders** | **WC→QBO** (website order → QBO invoice) | The core new work. QBO's own B2B invoices do *not* need to flow to WC (WC isn't the books). So orders are effectively one-way WC→QBO. |
| **Inventory** | **WC→QBO only, and only if QBO items become inventory-type** | The hard one — see §5. True two-way inventory needs both systems to track stock; today only WC does. |

**Plain takeaway:** customers (done) + orders (WC→QBO) are very achievable. *Inventory two-way is the one that needs a real accounting decision before we touch it.*

---

## 3. The core challenge — catalog mapping

To turn a WC order into a QBO invoice, every line item (`AHF-BANANA-18KG`) must point to a QBO item. But the 316 WC products and 1,160 QBO items share **no key** (QBO items have no SKU; names are codes). So we must establish the mapping. Three options:

- **A. Map WC products → existing QBO items** (manual/assisted, 316 rows). Preserves QBO's catalog + history. No inventory tracking (items stay non-inventory). ~1–2 days mapping effort + review.
- **B. Push WC products → QBO as NEW inventory items.** Gives a clean, SKU'd, stock-tracked QBO catalog → enables inventory sync. BUT creates 316 new items beside the old 1,160, and historical invoices still point at the old ones. **Accounting impact — needs the bookkeeper.**
- **C. Hybrid:** map the ~top products that actually sell online to existing items now (orders work immediately); defer the full inventory-item rebuild.

---

## 4. Field mapping (WC Order → QBO Invoice)

| WC order field | QBO invoice field | Notes |
|---|---|---|
| Customer (`customer_id`) | `CustomerRef` | We already stored `qbo_customer_id` on WC customers → clean link. Guest orders → match by email or a "Walk-in/Web" customer. |
| Order number | `DocNumber` / `PrivateNote` | Tag invoice with WC order # for idempotency (never double-post). |
| Line items (SKU, qty, price) | `Line[].SalesItemLineDetail` | Needs the §3 product map. |
| Tax (GST) | `TxnTaxDetail` / line `TaxCodeRef` | Map WC tax → QBO GST code (we have all 79). `is_vat_exempt` seen on orders → handle. |
| Discounts / fees / shipping | Discount line / service items | QBO needs dedicated items for shipping & fees. |
| Payment + method | linked `Payment` / `SalesReceipt` | Paid web orders → SalesReceipt or Invoice+Payment; map Stripe/PayNow to a QBO deposit account. |
| Order date / status | `TxnDate` | Decide which WC statuses post (e.g. processing + completed only). |

**Customer (WC→QBO) and Product mappings** documented similarly once direction A/B/C is chosen.

---

## 5. The inventory decision (needs Michelle + accountant)

QBO items are **Non-inventory** → QBO tracks no stock. For QBO to reflect/sync stock, items must be **Inventory-type** (QBO Plus supports it). Converting/creating inventory items on a live book with 21,825 invoices has **COGS, opening-balance and valuation implications** — this is an accounting change, not just a technical one.

**Options:**
1. **Keep stock in WooCommerce only** (status quo). QBO never tracks qty. Simplest, safest. Inventory is *not* synced — and that's arguably fine, since Kumar manages stock in WC and QBO is for accounting. *(recommended unless the accountant wants stock in QBO)*
2. **Make QBO inventory-type items + push WC stock → QBO.** Real WC→QBO inventory sync. Requires accountant sign-off, opening quantities/values, and a clean cutover. Bigger project.

> We should not "make inventory sync work" by quietly converting item types — it would change the books. This is a conversation with Michelle's bookkeeper.

---

## 6. Build vs Buy — the key Thursday decision

| | **MyWorks Sync** (plugin) | **Custom build** (extend our scripts) |
|---|---|---|
| Two-way customers/orders/products/inventory/payments | ✅ built-in, real-time (5-min) | We build each, incl. scheduling, retries, conflict handling |
| Product mapping | UI with AutoMap (by name/SKU) + manual | We build a mapping table + admin |
| Maintenance after handover | Vendor maintains | **We own it forever** (bad for a handover project) |
| Ah Ho quirks (bilingual names, GST, PDF invoicing) | Configurable, may need tweaks | Full control |
| Cost | Free "Launch" tier; ~US$19–79/mo for volume/real-time | $0 license, but build + upkeep time |
| Time to live | Days (config + mapping) | Weeks (build + test + harden) |

### Re-weighted for AI-assisted build (added 2026-06-02)

AI (Claude Code) **collapses the build cost** — proven this session: the customer sync (cleaning, dedup, idempotency, structured addresses) took ~1 hour, not weeks. So the "custom takes weeks" strike is mostly gone. Custom now means: no monthly fee, full control of GST/bilingual/PDF quirks, and reuse of work already done (`qbo_customer_id` linking).

**But AI does NOT change the real cost** of a live daily financial sync: operational reliability (API downtime, rate limits, token refresh, partial failures, **conflict resolution**), maintenance after handover (Intuit/WC API changes), and financial liability (wrong GST / double-post). A managed engine carries that 24/7; custom puts it on us/Ah Ho.

**Decision is scope-dependent:**
- **One-way (WC orders → QBO), low volume, no inventory** → **AI-built custom wins** (own it, $0/mo, full control, low maintenance for a one-way push). This is likely the actual need.
- **Full two-way + inventory + "seamless daily"** → conflict-resolution + reliability favour **MyWorks** ($0–26/mo); AI building it fast doesn't reduce ongoing risk.
- **Hybrid (lean):** AI builds the bespoke low-risk parts (product-map tool, customer linking ✅, one-way order push, reconciliation report); use MyWorks only if true two-way inventory with no maintainer is required.

**Two deciding questions for Thursday:** (1) Is the real scope one-way orders or genuinely two-way + inventory? (2) Who maintains it after handover — we stay lightly involved (→ custom fine) or fully handed off to non-technical team (→ managed)?

### Client cost constraint (Michelle/Kelvin, 2026-06-03) — resolves the decision
Michelle: "if it's free okay, extra $26/month not practical"; Kelvin wants all costs before deciding. This is decisive:
- MyWorks free tier caps at **20 orders/mo** and Ah Ho spikes to 23 → **MyWorks-free is not reliably free** for them.
- **→ Go CUSTOM (AI-built) for orders WC→QBO** = genuinely $0/mo forever, no order cap. Fits one-way, low-volume, cost-sensitive perfectly.
- **→ Inventory: keep stock WEBSITE-ONLY.** Kumar only updates stock on the website; putting stock in QBO needs the accountant to enable inventory-type items (their one-time cost) and isn't needed unless they want QBO stock reports. Recommend website-only = $0, no setup.
- Michelle has already matched the **carton** products; **loose/by-piece items** are the remaining matching work — we draft, her team verifies.
- **All-in cost: S$0/month software.** One-time: product matching (we draft) + (only if they ever want QBO stock) accountant setup.

> Note: AutoMap matches by name or SKU. Our catalogs match on neither, so **expect a manual/assisted mapping pass of 316 products regardless of tool.** This is the main labour item.

### MyWorks cost for Ah Ho's volume
Ah Ho averages **~20 orders/month** (Mar 23, Apr 19, May 18). MyWorks tiers (USD, billed annually): **Launch FREE = up to 20 orders/mo**, Rise $19/mo, Grow $45/mo (~300), Scale $79, Soar $99.
- **Recommendation: start FREE (Launch)** — fits the average and the quiet months. If consistently >20/mo, upgrade to **Rise ~$19/mo (~S$26)**.
- Net: **likely $0, worst case ~S$26/mo.** The real cost is the one-time 316-product mapping, not the software. Paid tiers only add near-real-time sync + volume, which we don't need at this scale.

---

## 6b. How the free (custom) sync works — the mechanism

Same pattern as the customer import, pointed at orders + run on a timer:
1. **Both systems already have an API** — WC key (created) + QBO OAuth token (authorised by Michelle, auto-renews free).
2. **A scheduled script** reads new WC orders → maps customer (via stored `qbo_customer_id`), maps line items (the product map), applies GST/nett tax code → creates the QBO invoice. Each invoice tagged with the WC order # → never double-posts (idempotent).
3. **Runs on a free scheduler** — Vodien cPanel cron (recommended, lives on their hosting, survives handover) or a scheduled GitHub job.

**Why $0 and stays $0 as volume grows:** the WC + QBO APIs have no per-order fee (QBO allows thousands of calls/day; Ah Ho does ~20–60/mo), the scheduler is free, and we own the script (no licence). MyWorks does the *same* API-to-API-on-a-schedule thing but charges by volume. Trade-off: we maintain the script vs a vendor — small/stable at this scale, reuses the proven customer-sync pattern.

## 6c. Decision reframed — Plugin vs No-plugin (Lex won't do unpaid maintenance)

Lex won't maintain a custom sync for free. That removes "free custom build" as a default — "free" only meant Lex absorbing maintenance. So:

**Option 1 — Use a plugin (recommended).** Vendor maintains it → zero ongoing burden on Lex; Lex bills the one-time setup (product mapping). Two candidates:
- **Intuit's own "WooCommerce Connector by QuickBooks" (OneSaas)** — *free to use*, two-way, hourly sync, GST tax-mapping page, **maintained by Intuit**. Catches: free tier has a **monthly transaction cap then auto-bills bundles** (can surprise); **Singapore availability must be verified**; clunkier than MyWorks. → **Try this first** (free + Intuit-maintained satisfies both Michelle's "free" and Lex's "no unpaid work").
- **MyWorks Sync** — fallback. Free ≤20 orders/mo, ~S$26/mo above; better mapping UI; global (SG-safe).

**Option 2 — No plugin (custom): the consequences.** Free software + no cap, BUT Lex owns build (billable) AND maintenance (Lex won't do free) → only viable as a paid build + support retainer, or hand-over-as-is where future breakages are new paid jobs. Risk: unmaintained → silent sync failure → books drift.

**Both plugins still need the 316-product manual mapping** (no shared SKU/name) — unavoidable regardless of tool.

## 6d. Deep-dive: can Intuit's free connector (OneSaas) do the full job?

**Verdict: Yes for the core job (orders→QBO invoices w/ GST/nett + customers), free + Intuit-maintained — IF (1) available for Singapore QBO [UNVERIFIED — #1 to check] and (2) we do one-time product-mapping prep.**

- Orders → QBO **as invoices** ✅ (set "create as invoice", not sales receipt → unpaid, ready to close in QBO = matches their flow)
- GST + nett ✅ via tax-mapping page (WC tax rate → QBO GST code)
- Customers ✅; line items/discounts/fees ✅ (discount needs a dedicated QBO item)
- Closing only in QBO ✅ (don't enable payment sync-back)
- **Product matching ⚠️** — matches WC-SKU→QBO-*Name* or →QBO-*SKU*; Ah Ho matches on neither. **Fix:** map 316 once, then write each WC SKU into the QBO item's empty SKU field (we have API access) → connector matches by SKU forever, no duplicate items. (Alt: let it create new items = 316 duplicates beside the 1,160 — avoid.)
- Inventory ⚠️ only if QBO items inventory-type (accounting change) → skip, keep stock website-only
- Free ✅ at <~100 orders/mo (Intuit made these free). Caveats: free tier no grouping/summarise/journal entries, **multiple payment methods per order** can break (their orders are single-payment → fine).
- Reliability: OneSaas known for occasional slow/unreliable sync + thin support; manageable at this volume, less polished than MyWorks. ("OneSaas is dead" online = the *Xero* version Intuit killed; QBO one lives on.)

**Must-verify before committing:** (1) Singapore availability in Ah Ho's QBO app store; (2) SG GST edition tax-mapping; (3) WP permalinks set to "pretty" (plain breaks it).

## 6e. Safe sync configuration (additive, no overwrites) — confirmed possible

Principle: **one direction per entity + match-or-add, never overwrite.** No entity is bidirectional, so the systems can't clobber each other.

| Flow | Direction | Additive rule |
|---|---|---|
| Website orders | WC → QBO | each order CREATES a new QBO invoice; tracked by order # → never double-posts; never touches the 21,825 existing invoices |
| Website customers | WC → QBO | create if new; if same email exists, LINK (don't overwrite existing QBO details) |
| QBO customers | QBO → WC | (one-off 728 done) ongoing: create new on WC, match by email |
| QBO invoices → WC | **OFF** | QBO B2B invoices never appear on website (as required) |

Match keys: email (customers), order # (invoices), SKU (products). Tag everything (`qbo_customer_id` ✅, order # on invoices) → traceable + reversible. Review transactions before enabling auto-sync.

**Inventory → QBO is the one exception:** requires QBO items to be inventory-type = accountant setup (opening qty/value, COGS impact). Possible + still additive, but not a mere toggle. If stock is managed only on the website and QBO stock *valuation* isn't needed → leave inventory out, keep website-only.

## 6f. NEW requirement — salesperson → QBO "Class" (commission tracking)

Michelle assigns each QBO invoice a **Class = salesperson** (KUMAR, LIEW TAH SIANG, LIM CHIN AIK, LIM TECK SOON, SNG KOK HUA, XIAO MA) to count commission. WC already stores the salesperson per order (`_assigned_salesperson_id` + `_commission*` meta). Getting it into QBO Class:
- **Free Intuit connector:** ❌ basic field mapping only (customer/products/tax/terms) — almost certainly **cannot set Class per invoice**. Invoice auto-creates; Michelle still sets Class manually (= status quo for that click, but invoice-creation is saved).
- **MyWorks (paid):** ✅ maps WC **role → QBO Class**; needs one WC role per salesperson (they currently share one `ah_ho_salesperson` role) + the ~$26/mo tier.
- **Custom:** ✅ set `ClassRef` from `_assigned_salesperson_id` directly — cleanest match to her workflow; but Lex maintains.
- **Possible middle path:** free connector creates the invoice + a tiny scheduled helper stamps the Class from the salesperson (small custom add-on, less than a full sync engine).

**Decision:** start free (Class stays manual, still less work than today); add auto-Class via paid/custom only if the manual step becomes a pain. Confirm free-connector Class capability when verifying SG availability.

## 6g. WooCommerce build audit — what it CHANGES (decisive)

Full audit of the custom plugins (`ah-ho-custom`, `ah-ho-invoicing`, etc.) revealed the build is bespoke in ways that **break plug-and-play connectors** for Michelle's two key needs:

- **GST is NOT real WooCommerce tax.** It's a *display-only* 9% calc on the invoice PDF driven by custom order meta **`_b2b_add_gst`** (auto-enabled for wholesale orders). The WC order total is GST-exclusive; `get_total_tax()` ≈ 0; no `is_vat_exempt`. **Every connector maps WC-tax→QBO-tax-code → would sync invoices as no-tax → GST WRONG.** This fails Michelle's hard requirement. Neither Intuit-free nor MyWorks handles it (mechanism is custom).
- **Salesperson→Class:** custom meta `_assigned_salesperson_id` (→ user `display_name`); all reps share ONE role `ah_ho_salesperson` → MyWorks role→Class won't work; free connector can't do Class. Custom or manual only.
- **Wholesale pricing is order-creator-based, not customer-based** (B2B customer on public site pays retail). Must use **stored line totals** (`_wholesale_price_applied` lines), not catalogue price. Connectors do use line totals → OK.
- Mappable fine: fees (3.5% card = real fee line), `_payment_terms`→QBO Terms, `_delivery_date`, invoice number = WC order number (idempotency key).
- HPOS store → read via `wc_get_order()->get_meta()` / WC REST.

**Implication — "free + maintained + full job" is NOT on the table** once GST + commission are required. Two paid paths:
1. **Custom sync** that understands the bespoke meta (`_b2b_add_gst`→GST, `_assigned_salesperson_id`→Class, fees, terms). Correct tool; Lex builds + maintains (paid).
2. **Rework WC first** so GST = native tax + one role per rep → then an off-the-shelf connector works (vendor-maintained). The rework is itself paid custom work + changes site/PDF GST handling.

Stale-doc warning: `docs/AH-HO-FRUITS-SYSTEM-DOCUMENTATION.md` claims sequential `AHF-…` invoice numbers + `_ah_ho_invoice_number` — **not in code**; trust code (invoice # = order #).

## 7. Risks & safety (non-negotiable)

- **Test in a QBO sandbox first** — never dry-run writes against the live 21k-invoice book. (We own the Intuit app; sandbox is free.)
- **Backups before any write** — QBO backup ✅ done; re-run before changes. WC export too.
- **Idempotency** — tag every QBO invoice with the WC order # so re-runs never double-post.
- **Status filter** — only post real orders (e.g. processing/completed), never drafts (we saw a `checkout-draft`).
- **No destructive writes to existing QBO data** — additive only; never touch the 1,160 legacy items or 21k invoices.
- **GST accuracy** — wrong tax code = wrong GST filing. Map explicitly, verify with a few test invoices, confirm with bookkeeper.

---

## 8. Decisions needed (bring to Thursday)

1. **Build vs Buy** — MyWorks Sync (recommended) vs custom?
2. **Inventory** — keep stock in WC only (recommended), or invest in QBO inventory items (needs accountant)?
3. **Product mapping** — who owns confirming the 316 WC↔QBO product matches (us-assisted + Michelle/accountant verifies)?
4. **Order scope** — which WC statuses post to QBO; invoice vs sales-receipt; how to treat guest/web vs B2B customers.
5. **Accountant involved?** — GST mapping + any inventory change must be signed off.

---

## 9. Proposed phased rollout (once decisions made)

1. **Phase 0 — Sandbox + decisions** (Thu): pick tool, set up QBO sandbox, confirm inventory direction.
2. **Phase 1 — Product mapping**: build assisted WC↔QBO map (316), Michelle/accountant verify.
3. **Phase 2 — Orders WC→QBO in sandbox**: post the 67 existing + test new; verify GST, totals, customer link, idempotency.
4. **Phase 3 — Go live on orders**: switch to production, monitor daily, reconcile.
5. **Phase 4 — Customers WC→QBO** (new signups) + **inventory** (only if Option 2 chosen, with accountant).
6. **Phase 5 — Daily reconciliation report** so Michelle sees both systems agree.
