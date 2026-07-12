# Ah Ho — QuickBooks integration: let's agree the scope

**For:** Michelle · **From:** Lex · **Date:** 2026-07-12

## First, the honest trade-off (why scope matters)
Because you'd prefer **not to pay for an off-the-shelf tool** (e.g. the ready-made connectors run ~S$26+/month, forever), we're **building the link ourselves** — which means **$0/month software**. That's the right call on cost, but it comes with a fair trade-off you should know:

- **We build and maintain it.** Unlike a paid product with a vendor supporting it 24/7, this is custom. If QuickBooks or the website changes something, it may need a fix from us.
- **Every extra feature adds risk and upkeep.** A simple, focused link is reliable and cheap to keep running. A "sync everything both ways" build is where things break, double-up, or post wrong figures — and it's far more to maintain.
- **So our strong recommendation: keep it to the essential scope** — do the one or two things that genuinely help your bookkeeping, and *not* build features you don't really need.

## What the link does **today** (the current, minimal scope)
- **Website orders → QuickBooks.** Each paid website (residential/B2C) order is recorded in QuickBooks as a **sales receipt** — the customer, the fruits, the prices, and GST (inside the price), total matching what they paid. So your books show every online sale and reconcile against your Stripe→UOB payouts.
- **New website customers** are added to QuickBooks automatically (existing ones aren't touched).
- **Products** are matched to your **existing** QuickBooks items.

## What it does **NOT** do (current limitations — by design, to stay simple/safe)
- ❌ **No stock/inventory sync.** Stock stays on the website only; QuickBooks doesn't track quantities.
- ❌ **No auto-creating products** in QuickBooks (so it can't clutter or duplicate your catalogue). Unmatched products are **held** until matched.
- ❌ **No live two-way customer sync.** (QuickBooks customers were imported to the website once, back in June; it's not a constant feed.)
- ❌ **Wholesale (B2B) is separate** — those invoices stay in your current plugin, not this link.
- ❌ **No pushing QuickBooks changes back to the website.**

---

## Questions to lock the scope — please answer these

1. **Core goal.** Is the main point simply *"see all website sales in QuickBooks so the books are complete and reconcile against the bank"*? Or is there something else you specifically need it to do?

2. **Inventory / stock.** Do you need QuickBooks to track stock levels? *(Heads-up: this is a big change — it means converting your items to inventory-type with opening balances, an accounting exercise for your bookkeeper, and more to maintain. We recommend keeping stock on the website only.)*

3. **Products.** Is it enough that website orders **match your existing** QuickBooks items? Or do you want new website products to **auto-create** items in QuickBooks? *(We recommend match-only, to protect your catalogue.)*

4. **Customers.** New website buyers are already added to QuickBooks. Do you need anything more — e.g. a customer's details changing in QuickBooks and flowing to the website (or vice versa) on an ongoing basis?

5. **Wholesale (B2B).** Keep wholesale separate (as now), or do you want website *and* wholesale both flowing through this link?

6. **How often.** Is **once a day** fine for website orders to appear in QuickBooks, or do you need them **near-instant**?

7. **Order types.** Only **paid/completed** orders? How should **refunds / cancellations** show up in QuickBooks (or handle those manually)?

8. **Start point.** Start from **today onwards** (cleanest), or also bring in **past** online orders (and if so, how far back)?

9. **On each record.** Anything you specifically want on each QuickBooks entry — e.g. salesperson/Class, the website order number, delivery date, a memo?

---

**Our recommendation in one line:** keep it to **#1 (website sales into QuickBooks) + new-customer add + match-to-existing-products**, run **once a day**, **today-onwards**, wholesale stays separate. That's the reliable, low-maintenance, $0/month version. Anything beyond that, let's discuss the added risk/upkeep before building it.
