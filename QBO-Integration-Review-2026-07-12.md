# Ah Ho Fruit ↔ QuickBooks Integration — Review & Recommendation

**Date:** 2026-07-12 · **Reviewer:** Claude (for Lex) · **Mode:** read-only + dry-run (no writes to QuickBooks)
**Backup taken first:** `/Users/lexnaweiming/ahho-qbo-backups/20260712-125642/` (git bundle + code/docs tar + latest QBO/WC snapshots + 230 Test notes)

---

## 1. Where the integration actually stands

The approach pivoted **twice**:

1. **Intuit OneSaas "WooCommerce Connector"** → imported **0 orders in 10 days** under verified-correct config. Abandoned (Intuit-side fault).
2. **MyWorks Sync plugin** → real AutoMap UI, but **free tier caps at 20 orders/mo** and Ah Ho spikes to ~23. Michelle/Kelvin: "free okay, $26/mo not practical" → not reliably free.
3. **Custom Python sync (current build)** → `b2c-qbo-salesreceipt-sync.py`. **$0/mo, no order cap.** This is the live direction.

### What the custom build does
- Each **paid residential (B2C)** WooCommerce order (`processing` / `completed`) → a QuickBooks **Sales Receipt** (customer + fruit lines + GST-inclusive total = what the customer paid), deposited to UOB (acct 31).
- **B2B / wholesale** (`processing-b2b`) is **excluded** — handled separately by the CRM Perks plugin as invoices.
- Dedicated Intuit app "Ah Ho WooCommerce B2C Sync" (isolated from the B2B connection).
- **Dry-run by default**; only writes with `--execute`.
- Idempotent: skips orders whose `DocNumber = woo-<id>` already exists.
- Atomic token persistence (a refresh bug can't blank `.env`).
- **Customers:** 728 synced QBO→WC. **Items:** 192 linked by SKU. **Tested on 2 real orders end-to-end ✅**, then cleaned up.

**Status: NOT live.** Auto-sync is off, pending bookkeeper sign-off on GST treatment, Stripe→bank reconciliation, and start date.

---

## 2. Code review — `b2c-qbo-salesreceipt-sync.py`

### ✅ Solid
- **Security:** `.env` is gitignored and untracked — no secrets in the repo.
- **Token handling:** atomic rewrite; rotated refresh tokens persisted safely.
- **Idempotency:** `DocNumber = woo-<id>` + existence check prevents double-posting (on execute).
- **B2B isolation:** hard guard refuses `processing-b2b`; separate Intuit app.
- **GST (single-line):** net = gross ÷ 1.09; QBO re-adds 9% SR → penny-exact. Verified live in dry-run ($50/$60/$80/$150 all reconcile).

### 🔴 Must fix before going live

**F1 — Incomplete SKU mapping silently drops revenue.**
Only 192 items carry SKUs. Live orders routinely contain SKUs that aren't mapped. Observed in dry-run:
- Order #5139 ($100): `B2B-MANGOTAIWAN` unmapped → only the $50 cherry line survives.
- Order #5111 ($60): `AHF-NAMSHUI-M` + `AHF-KIWI-GOLD-1` unmapped → real receipt ≈ $28.

In **dry-run** these lines are dropped. In **`--execute`** they're **auto-created as new QBO NonInventory items** (`create_item`), duplicating items that already exist in the 1,180-item catalog but lack a SKU. Either way the books are wrong (under-stated revenue, or duplicate items).
→ **Finish the SKU crosswalk** for every SKU that appears in real B2C orders, and **disable silent auto-create** (make unmapped a hard stop that reports, not a create).

**Quantified (scan of 52 recent B2C orders, 2026-07-12):** **39 of 52 orders (75%) have ≥1 unmapped line.** 20 distinct products are unmapped, in two classes:
- **15 have a WC SKU that isn't on the matching QBO item** → fix = write the SKU into the QBO item (same as the phase-1 `qbo-write-skus.py` pass): `AHF-AVOCADO, AHF-BLUEBERRY-J-125, AHF-BLUEBERRY-J-200, AHF-ENVY-L, AHF-FUJI-CN-M, AHF-GOLDEN-1, AHF-HUNNYZ-1-1, AHF-KIWI-GOLD-1, AHF-LONGAN-500, AHF-NAMSHUI-L, AHF-NAMSHUI-M, AHF-SUNKIST-S-1, B2B-MANGOTAIWAN, B2C-MANGO-THAI, B2C-USAGREEN`.
- **5 have NO SKU in WooCommerce at all** → can't be matched until a WC SKU is added first (data-entry in WP admin): `500g SA. ANGELINO PLUM 非洲黑李子, AUS LOCSWEET VALENCIA 澳洲水橙, EUTHOPIA STRAWBERRY 埃塞俄比亚草莓 (250g), NZ QUEEN APPLE 纽西兰皇后苹果, PERU GREEN GRAPES 秘鲁青葡萄 (kg)`.

**Bottom line: the integration is NOT ready to go live** — flipping `--execute` today would mis-post ~75% of orders.

**F2 — Misleading dry-run total.**
`sync_order()` prints `would POST SalesReceipt total≈${order.total}` — it prints the *WooCommerce order total*, not the sum of the lines it actually built. So when F1 drops lines, the dry-run **still says the total reconciles** when it doesn't. A reviewer can't trust the output.
→ Print the **actual computed receipt total** (`sum(net) × 1.09`) and **flag any mismatch** vs `order.total`.

**F3 — `B2B-*` SKU inside a B2C order.**
Order #5139 (residential) contained `B2B-MANGOTAIWAN`. The B2C/B2B split is by **order status**, but a wholesale product can be bought on a residential order. Decide how these map (likely fine to sell as B2C, but confirm it shouldn't route to the B2B/CRM-Perks invoice path).

### 🟠 Should fix for clean reconciliation

**F4 — Multi-line rounding drift.**
Netting each line then letting QBO re-add 9% can land a cent off the Stripe charge (two $10 lines → receipt $19.99 vs $20.00 paid), breaking penny-exact bank reconciliation.
→ **Switch to `GlobalTaxCalculation: TaxInclusive`** and send the **gross** line amounts. QBO backs out the embedded GST, `TotalAmt == order.total` exactly, and the net-rounding problem disappears entirely. This is simpler *and* correct, and matches the "website prices already include GST" rule.

**F5 — Discounts / coupons not modelled.**
No handling of order-level `discount_total` / `coupon_lines`. If a coupon was applied cart-wide, line totals may not sum to `order.total`. (TaxInclusive + a reconciliation check per F2 will catch this.)

**F6 — Hardcoded environment IDs.**
`SR_TAXCODE_ID=45`, `DEPOSIT_ACCOUNT_ID=31`, income-account-by-name-substring. Fragile if QBO config changes; fine short-term but document them.

**F7 — Deposit account vs stated plan.**
Code deposits straight to **UOB (31)**; the 06-30 note to Michelle described **Undeposited Funds** as the holding account. Pick one and make code + message agree (this is a bookkeeper call).

---

## 3. The "products autofill" bug (QuickBooks invoice line)

**Root cause:** an **Intuit server-side regression** (~end June 2026). QBO's invoice Product/Service typeahead changed from **multi-token, any-order** matching to **strict contiguous-phrase** matching. Two symptoms:
1. **Punctuation breaks matches** (`"`, `.`) — e.g. `7 ind pine` no longer found `7" IND. PINEAPPLE`.
2. **Can't skip a middle word** — `40 sa orange` no longer finds `40" SA. NAVEL ORANGE` (because "NAVEL" sits between).

**What we did:** normalized **all 1,363 items** (Jul 1) to strip punctuation/brackets → `40" SA. NAVEL ORANGE` became `40 SA NAVEL ORANGE`. Rollback snapshots saved.

**Honest state:**
- ✅ Symptom 1 (punctuation) is **fixed** — names are now punctuation-clean, so typing without `"`/`.` matches.
- 🔴 Symptom 2 (any-order / skip-a-word) is **NOT fixable by renaming** — it's Intuit's match engine. No item name can make every word-skip combination contiguous.

**Options for symptom 2:**
- **(a) Push the Intuit case** (filed 2026-07-01, realm 9341454180558366) for restoration of token matching. Only real fix; timeline unknown.
- **(b) Train staff** to type the **leading contiguous words** (product number + first word), which now works reliably post-normalization.
- **(c) Not worth further renaming** — reordering names to favour common searches trades one skip-pattern for another and churns the catalog.

> Note: this bug is about **Michelle typing in QBO's UI**. It does **not** affect the custom sync — that matches by **SKU via API**, not by typeahead.

---

## 4. Recommendation on the integration path

**Go with the custom Python sync** — it's the only genuinely $0/mo, no-cap option, it's built and tested, and it fits the real scope (one-way, low-volume, B2C receipts). MyWorks and the Intuit connector are both worse on the constraints that actually bind here (cost cap / proven-broken).

**But "done smoothly + data flowing" requires closing F1–F2 (and ideally F4) first.** As-is, flipping `--execute` would post under-stated receipts and/or spawn duplicate items. Concretely, in priority order:

1. **F1 — finish the SKU crosswalk** for every SKU appearing in real B2C orders; turn unmapped into a **hard, reported stop** (no silent auto-create).
2. **F2 — fix the dry-run total** to show the true computed total and flag mismatches (this is the safety net that makes every future dry-run trustworthy).
3. **F4 — switch to TaxInclusive + gross amounts** for penny-exact reconciliation.
4. **Then** re-run dry-run over a wide batch until **every** order reports a clean, matched total.
5. **Then** (with your go-ahead + bookkeeper sign-off on F7/GST/start-date) run `--execute` on a **single** order, verify in QBO, and only then widen.

None of steps 1–4 write to QuickBooks. Step 5 is the first live write and stays gated on your explicit approval.

---

## 5. Fixes APPLIED 2026-07-12 (code-only, no QuickBooks writes)

Applied to `b2c-qbo-salesreceipt-sync.py` and verified by dry-run over ~24 real orders:

- **F1 — unmapped = hard stop.** Removed silent `create_item` on execute. Any line whose SKU isn't on a QBO item now **blocks the whole order** (`return "unmapped"`) with a per-product `[BLOCKED]` message. No partial receipts, no auto-created duplicate items.
- **F2 — truthful total + reconciliation guard.** Dry-run now prints the **actual built total** (not `order.total`) and flags any `[MISMATCH]` vs what the customer paid; in execute mode a mismatch **holds** the order (`return "mismatch"`) to protect Stripe/bank reconciliation.
- **F4 — TaxInclusive + gross amounts.** Switched `GlobalTaxCalculation` to `TaxInclusive` and send **gross** line amounts; QBO backs out the embedded 9% SR. Verified penny-exact, including a 5-line order ($187.50 = $187.50). Removed the now-dead `net_of_gst()`.

**Verified behaviour (dry-run, 2026-07-12):** fully-mapped orders report a total exactly equal to WooCommerce; every order containing an unmapped product is held. The sync can no longer post an under-stated or mis-reconciled receipt.

### Still outstanding (not done — needs your go-ahead, involves live writes)
- **The 20 unmapped products (§2 F1)** still need mapping before those orders can flow — 15 via a QBO SKU-write pass, 5 via adding a WC SKU first. Until then they're safely **blocked** (correct, but they won't sync).
- **F3** (B2B SKU on B2C orders), **F6** (hardcoded IDs — documented), **F7** (deposit account vs Undeposited Funds) — bookkeeper decisions.
- No `--execute` run, no messages to Michelle.

## 6. What I did NOT do
- No writes to QuickBooks (dry-run only, as agreed).
- No changes to the live WooCommerce site or QBO catalog.
- No messages sent to Michelle.
