# Ah Ho Fruit ↔ QuickBooks — Work Summary, 2026-07-12

## Goal
Review the Ah Ho website (WooCommerce) → QuickBooks integration, fix the issues, and make sure data flows correctly.

## Headline
The integration went from "untested, buggy tax handling" to **live-proven, correct, and self-service** in one session. It now posts website orders into QuickBooks as **unpaid invoices** (Michelle's chosen model), and Michelle is mapping the last few products herself. **88% of orders already flow; ~6 product codes remain.** Full daily switch-on is deliberately held until she finishes and gives the nod.

## What we did

**1. Backup first.** Full snapshot of both repos + latest QuickBooks/WooCommerce data before any changes (`~/ahho-qbo-backups/20260712-125642/`).

**2. Reviewed the whole integration.** Confirmed the **custom Python sync** is the right path — it's the only genuinely $0/month, no-order-cap option (the paid connectors cap the free tier below Ah Ho's volume). Secrets are secure; auth and data reads verified.

**3. Hardened the sync (3 fixes):**
- **Unmapped = hard stop** — any order with a product not matched to QuickBooks is *held*, never posted partial (no wrong figures, no duplicate items created).
- **Reconciliation guard** — every order's total is checked against what the customer paid; mismatches are held.
- **GST handling** — corrected (see #4).

**4. Caught and fixed a real tax bug via a careful single-order live test.** The first live post recorded **$87.20 instead of $80.00** — QuickBooks here ignores the "tax-inclusive" setting and adds 9% on top. We reverted to the proven method (net + 9% GST = the price paid), deleted the wrong record, and re-posted correctly. *This is exactly why we test one order first.*

**5. Michelle raised a valid accounting point** — a "paid sales receipt" doesn't fit how Stripe pays (monthly, net of fees), so it wouldn't reconcile. **She decided she wants unpaid invoices** she can close herself with the fee deducted. No accountant dependency.

**6. Built invoice mode and live-tested it.** The sync now creates **unpaid invoices** by default (`--doctype invoice`). Order #5134 is in QuickBooks as **Invoice #48865, $80.00, unpaid** — the exact model Michelle asked for.

**7. Product mapping — now self-service.** The website has more product sizes than QuickBooks (they share items), so instead of us writing codes, **Michelle adds the SKU to the matching QuickBooks item herself** and our sync links it automatically. She's already done 4; **88% of orders now flow**, with ~6 loose-fruit codes left. We flagged 2 she mapped onto "RETURN" items by mistake.

**8. Communication.** Sent Michelle: the product-mapping sheet, a scope questionnaire (framing the build-it-free-vs-maintenance trade-off), and replies confirming the invoice approach + the SKU double-checks.

## Where it stands now
- ✅ **Live and correct** — one real order posted as a proper unpaid invoice.
- ✅ **Model locked** — unpaid invoices, Michelle reconciles with Stripe fee.
- ✅ **88% of orders would flow**; the rest auto-unblock as Michelle adds the last ~6 SKUs.
- ✅ **All code + docs committed and pushed** (`lexsgd/ah-ho-fruits`, main).
- ⏸️ **Full daily go-live held** until Michelle finishes mapping and confirms.

## Scope (deliberately narrow, for reliability + low maintenance)
- **Sales:** website orders → QuickBooks unpaid invoices (GST-inclusive). ✅
- **Customers:** new website buyers added to QuickBooks; existing not overwritten. ✅
- **Products:** matched to existing QuickBooks items (not auto-created). ✅
- **Inventory:** not synced — website stays the single source of stock. ❌ (by design)
- **Wholesale (B2B):** separate (CRM Perks plugin). ❌ (by design)

## Next steps
1. Michelle finishes the last ~6 product SKUs + fixes the 2 RETURN-item maps.
2. One `--since 2026-07-12 --execute` run posts the backlog as invoices.
3. Optionally schedule a daily run; optionally tidy 2 old June test receipts.
