# Ah Ho Fruit — Pivot from Intuit Connector → MyWorks Sync (2026-06-27)

## Why
Intuit/OneSaas "WooCommerce Connector by QuickBooks" has imported **0 orders in 10 days**
under verified-correct config (Items=Non-inventory+Match-by-SKU, Sales-Local income, 0% OS tax,
start date set). Support (Valerie/OneSaas) sent the **same canned "manual sync, wait 3-4h" reply
twice** (Jun 24 + Jun 26); both produced 0 imports. Integration Transactions
(Processing/Imported/Excluded) all empty each time. → Intuit-side fault, abandon.

## Target: MyWorks Sync for WooCommerce & QuickBooks Online
- Free **Launch** plan (forever-free) — Ah Ho's order volume is tiny, fits free tier.
  Paid tiers Rise $19 / Grow $45 / Scale $79 / Soar $99 (annual) if volume/automation grows.
- Free WP plugin + free MyWorks account. Has a real **AutoMap** product-mapping UI (the thing
  the Intuit connector lacked), a setup wizard, and **manual push of existing orders** (no waiting
  on a poll cycle — we can push #5003/#4997/#5009 immediately).
- Syncs customers, orders, payments, products, inventory. Orders can map to QBO invoice / sales
  receipt / estimate, with control per order-status & payment-method.

## Pre-req — AVOID DOUBLE-SYNC
1. **Disconnect the Intuit WooCommerce Connector first** (QBO → Integration transactions →
   WooCommerce Settings → Disconnect) so two integrations don't both create invoices = dupes.
2. Phase-1 SKU work stays valid — MyWorks matches by SKU too (192 items already carry SKUs).

## Steps (needs Lex go-ahead before touching the live site)
1. Disconnect Intuit connector in QBO.
2. Install "MyWorks Sync for WooCommerce & QuickBooks Online" plugin on ahhofruit.com (WP admin).
3. Create/connect free MyWorks account → authorize WooCommerce (WP session) + QBO (Lex OTP login).
4. Run setup wizard; mirror the agreed mapping:
   - Orders → **draft Sales Invoice**, auto invoice #.
   - Income acct **41000 Sales-Local**; deposits → Undeposited Funds; fees → 61200 Bank Charges.
   - Tax: WC "No Tax" → **9% SR, tax-INCLUSIVE** (DECIDED 2026-06-27 by Lex: website prices
     already include GST, customer pays no extra 9% → QBO must record the embedded output GST).
   - Match products by SKU; AutoMap the 64 shared-item + 5 OMAKASE leftovers (or accept auto-create).
   - **Start date = today** (no historical backfill of the 73 old WC orders → avoids dupes vs
     Michelle's manual QBO invoices).
5. **Manually push the 3 test/live orders** (#5003, #4997, #5009) → confirm they land in QBO.
6. Place 1 fresh live-style order → confirm auto-sync fires.
7. Tell Michelle it's live; hand her the "where website orders now appear in QBO" note.

## Open decisions for Lex
- **GST treatment** (unchanged from before): residential/website prices are GST-INCLUSIVE,
  wholesale = +GST. 0% OS under-reports embedded output GST on website sales. Pick:
  (a) map to 9% SR tax-inclusive (auto, correct), or (b) 0% OS + staff set GST per draft (manual).
  Confirm with bookkeeper.
- Free Launch plan order cap — confirm Ah Ho's monthly online order count stays under it
  (very likely yes given ~a handful/week).
```

## Sources
- MyWorks WooCommerce↔QBO: https://myworks.software/integrations/woocommerce-quickbooks-sync/
- QBO app listing: https://quickbooks.intuit.com/app/apps/appdetails/woocommerce_quickbooks_online_automatic_sync_myworks_software/en-us/
- WP plugin: https://wordpress.org/plugins/myworks-woo-sync-for-quickbooks-online/
- Pricing: https://www.g2.com/products/myworks-sync/pricing
