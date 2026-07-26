# Ah Ho Fruit — Catalogue Sorting Issue Diagnosis

**Date:** 2026-06-26
**Reported by:** Michelle (relaying Kel): "Kel can only manually sort the APPLES — the rest he did and saved but went back to default."

## TL;DR

- The storefront sorting **setting is correct** ("Default sorting (custom ordering + name)").
- The built-in **Products → Sorting drag-and-drop tool actually works** — I tested it live on Pears and the change saved + showed on the website. So nothing is fundamentally broken.
- The real problem is in the **data**: many products share the **same internal sort number (`menu_order`)** — left over from the original CSV import. When several products share the same number, WooCommerce can't tell them apart and falls back to **alphabetical/name order** for that group. That is the "went back to default" Kel sees.
- "Apples works" is a coincidence: in Apples, Kel's intended order happens to line up with alphabetical order, so it looks like it stuck. In the other categories his intended order differs from alphabetical, so the tied products snap back.

## Evidence

1. **Setting (Customizer → WooCommerce → Product Catalog → Default product sorting):**
   `Default sorting (custom ordering + name)` = `menu_order`, then title as tiebreaker. ✅ Correct.

2. **Frontend honors it.** Live Pears page rendered in exactly `menu_order` ascending, with ties broken alphabetically — identical to the admin "Sorting" view (which uses `orderby=menu_order title`).

3. **Tied `menu_order` values everywhere** (from WC REST API):
   | Category | Products | Distinct values | Products sharing a tied value |
   |---|---|---|---|
   | Apples | 47 | 28 | 25 |
   | Berries | 27 | 15 | 16 |
   | Pears | 27 | 18 | 14 |
   | Citrus | 42 | 27 | 22 |
   | Tropical | 60 | 40 | 28 |
   | Stone Fruits | 27 | 14 | 18 |

   Example: the 42″ / 45″ / 52″ / 60″ SA Pears **all have `menu_order = 80`** → forced into alphabetical order, no manual arrangement among them can hold.

4. **Live drag test (Pears, fully reversed afterward):** dragging 52″ above 38″ in **Products → Sorting** saved instantly AND re-spaced the tied group to distinct values (142/144/149/150/151). Confirms the native tool works and even *fixes* ties — when used in that specific view. (Restored to original 63/68/72/74/80/80/80/80 immediately after.)

## Most likely reason Kel's attempts "revert"

The native tool works, so Kel is probably **not sorting in the right place**, or is hitting the tie problem:

1. **Not in the "Sorting" view.** Drag-to-reorder only works when the **"Sorting"** link at the top is active (bold). It saves **instantly on drop — there is no Save button**. If he dragged in the normal list, or clicked a column header (Name/Price) afterward, or used the website preview, nothing persists.
2. **Quick Edit "Order" field with duplicate/blank numbers** → recreates ties → alphabetical fallback.
3. **Tied groups** (the 80/80/80/80 problem) — even in the right view, if the import left products tied, his early arrangement of those looks like it reverts.
4. (Worth ruling out) a **QBO↔WC product sync/import re-running** and resetting `menu_order` — only relevant if the revert happens *after a delay*, not immediately.

## Recommended fix

**A. One-time cleanup (recommended):** re-index every category's `menu_order` to clean, distinct, gap-spaced values (10, 20, 30…) **preserving the current order**. Nothing visibly changes, but ties disappear forever, so every future drag sticks reliably. Reversible (full backup of current values taken first). Writes confirmed working via the WC API.

**B. Correct method for Kel (the reliable workflow):**
1. WordPress admin → **Products**.
2. Filter to a category (e.g. "Berries") with the category dropdown → **Filter**.
3. Click the **"Sorting"** link at the top (next to All / Published / Trash) — it goes bold.
4. **Drag rows** up/down by the row. It **saves automatically on drop** — do NOT look for a Save button, and do NOT click a column header afterward.
5. Refresh the category page on the website to confirm.

Do **A** first, then Kel only ever needs **B**.
