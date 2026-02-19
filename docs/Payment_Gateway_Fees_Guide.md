# Ah Ho Fruit

## Payment Gateway Fees System Guide

Operational + technical guide for adding processing fees based on payment method at checkout.

**Version 1.0 • Generated on 1 Feb 2026**

---

| | |
|---------------------------|------------------------------------------------------------------|
| **What it does** | Adds processing fees to checkout based on selected payment method (GrabPay, PayNow, Credit Card, etc.). |
| **Why it matters** | Recover payment gateway costs without raising product prices. Transparent fees shown before checkout. |
| **Core workflow** | Customer selects payment → Fee calculated → Shown in order total → Collected automatically. |
| **Biggest operational win** | Zero manual fee calculation. Different rates for different gateways. |
| **Key risk** | Legal compliance (must disclose fees clearly) and customer perception. |

**Promise:** After setup, payment fees are automatic. No manual adjustments, no spreadsheets, no missed charges.

---

## 1. How Payment Gateway Fees Work

This plugin adds a fee line item to the cart when customers select specific payment methods at checkout.

```
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│ Customer adds   │────▶│ Selects payment │────▶│ Fee calculated  │────▶│ Total updated   │
│ items to cart   │     │ method          │     │ automatically   │     │ with fee        │
└─────────────────┘     └─────────────────┘     └─────────────────┘     └─────────────────┘
                                                        │
                                                        ▼
                                               ┌─────────────────────┐
                                               │ Fee shown as line   │
                                               │ item: "GrabPay      │
                                               │ Processing Fee: $3" │
                                               └─────────────────────┘
```

**[ Real-time checkout updates ]**

| Event | What Happens | Customer Sees |
|-------|--------------|---------------|
| Select Credit Card | 2.9% fee added | "Card Processing Fee: $2.90" |
| Switch to GrabPay | Fee recalculates to 3% | "GrabPay Fee: $3.00" |
| Switch to PayNow | Fee removed (if no fee set) | No fee line item |
| Complete checkout | Fee included in order total | Receipt shows fee |

**Key behavior:** Fees update instantly when payment method changes. No page reload required.

---

## 2. Fee Types Explained

Three ways to charge fees, depending on your cost structure.

| Fee Type | Formula | Example ($100 cart) | Best For |
|----------|---------|---------------------|----------|
| **Percentage** | Cart × Rate% | 3% = $3.00 | GrabPay, PayNow (% based fees) |
| **Fixed** | Flat amount | $2.00 = $2.00 | Bank transfers, COD handling |
| **Fixed + Percentage** | Flat + (Cart × Rate%) | $0.30 + 2.9% = $3.20 | Credit cards (Stripe/PayPal model) |

### Stripe Fee Reference (Singapore)

| Payment Method | Stripe Charges You | Suggested Customer Fee |
|----------------|-------------------|------------------------|
| Credit Card | 3.4% + $0.50 | 3.5% or $0.50 + 3.4% |
| GrabPay | 3.0% | 3.0% |
| PayNow | 0.5% (capped) | 0.5% or absorb |
| Apple/Google Pay | 3.4% + $0.50 | Same as credit card |

**Non-negotiable:** Always verify current Stripe rates at dashboard.stripe.com before setting fees.

---

## 3. Admin Settings Interface

Access: **WooCommerce → Gateway Fees**

The settings page auto-detects all enabled payment gateways and shows configuration for each.

```
┌──────────────────────────────────────────────────────────────────┐
│ Payment Gateway Fees                                              │
├──────────────────────────────────────────────────────────────────┤
│                                                                   │
│  ┌────────────────────────────────────────────────────────────┐  │
│  │ Credit Card (stripe)                        [✓] Enable Fee │  │
│  ├────────────────────────────────────────────────────────────┤  │
│  │ Fee Label:    [Card Processing Fee          ]              │  │
│  │ Fee Type:     [Fixed + Percentage           ▼]              │  │
│  │ Percentage:   [2.9    ] %                                   │  │
│  │ Fixed Amount: [0.30   ] $                                   │  │
│  │ Taxable:      [ ] Apply tax to this fee                     │  │
│  └────────────────────────────────────────────────────────────┘  │
│                                                                   │
│  ┌────────────────────────────────────────────────────────────┐  │
│  │ GrabPay (stripe_grabpay)                    [✓] Enable Fee │  │
│  ├────────────────────────────────────────────────────────────┤  │
│  │ Fee Label:    [GrabPay Processing Fee       ]              │  │
│  │ Fee Type:     [Percentage                   ▼]              │  │
│  │ Percentage:   [3.0    ] %                                   │  │
│  └────────────────────────────────────────────────────────────┘  │
│                                                                   │
│                                        [ Save Changes ]           │
└──────────────────────────────────────────────────────────────────┘
```

### Settings Reference

| Setting | Description | Default |
|---------|-------------|---------|
| **Enable Fee** | Toggle fee on/off for this gateway | Off |
| **Fee Label** | Text shown to customer at checkout | "Processing Fee" |
| **Fee Type** | Percentage, Fixed, or Both | Percentage |
| **Percentage** | % of cart subtotal | 0 |
| **Fixed Amount** | Flat fee in store currency | 0 |
| **Taxable** | Whether GST applies to the fee | Off |

---

## 4. Setup Checklist

| Step | What to do | Verify |
|------|------------|--------|
| **Activate plugin** | Plugins → Payment Gateway Fees → Activate | Plugin appears in list |
| **Access settings** | WooCommerce → Gateway Fees | Settings page loads |
| **Enable Stripe gateways** | Ensure payment methods enabled in Stripe plugin | Gateways appear in settings |
| **Configure fees** | Set fee type and amount for each gateway | Values saved |
| **Test checkout** | Add item, select payment, verify fee shows | Fee displays correctly |
| **Switch payments** | Change payment method at checkout | Fee updates instantly |
| **Complete test order** | Process a real $1 order | Fee appears on invoice |

**Ruthless rule:** Never launch without testing ALL payment methods. One broken gateway = lost sales.

---

## 5. Customer Experience

What your customers see at checkout:

```
┌─────────────────────────────────────────────────────────┐
│ Order Summary                                           │
├─────────────────────────────────────────────────────────┤
│ Premium Fruit Basket               ×1         $50.00   │
│ Organic Bananas (1kg)              ×2         $12.00   │
├─────────────────────────────────────────────────────────┤
│ Subtotal                                       $62.00   │
│ Delivery                                        $5.00   │
│ GrabPay Processing Fee                          $1.86   │  ◀── Fee line
├─────────────────────────────────────────────────────────┤
│ Total                                          $68.86   │
└─────────────────────────────────────────────────────────┘

Payment Method:
  ○ Credit Card
  ● GrabPay        ◀── Selected, fee applied
  ○ PayNow
```

### Fee Visibility Rules

| Scenario | Fee Shown? | Notes |
|----------|------------|-------|
| Gateway has fee enabled | Yes | Shows as separate line item |
| Gateway has no fee configured | No | No line item appears |
| Fee is $0 (0% of cart) | No | Line hidden if amount is zero |
| Customer switches gateway | Updates | Instant recalculation |

---

## 6. Legal Considerations (Singapore)

| Requirement | Status | Notes |
|-------------|--------|-------|
| Surcharging allowed | ✓ Yes | Singapore permits payment surcharges |
| Disclosure required | ✓ Yes | Must show fee before payment |
| Card network rules | ⚠ Check | Visa/Mastercard have surcharge caps |
| GST on fees | Optional | Consult accountant |

### Best Practices

1. **Clear labeling** — Use descriptive names like "GrabPay Processing Fee" not "Service Charge"
2. **Reasonable amounts** — Don't exceed actual gateway costs significantly
3. **Consistent policy** — Document your fee policy on checkout/FAQ pages
4. **Alternative offered** — Consider one fee-free option (e.g., PayNow)

**Recommendation:** Add a line to your checkout page: "Payment processing fees help us cover transaction costs and keep product prices low."

---

## 7. Troubleshooting

| Problem | Cause | Solution |
|---------|-------|----------|
| Fee doesn't appear | Gateway not enabled in settings | WooCommerce → Gateway Fees → Enable |
| Fee doesn't update when switching | JavaScript not loaded | Check for JS errors in browser console |
| Wrong fee amount | Incorrect percentage/fixed value | Verify settings, check decimal places |
| Gateway not showing in settings | Gateway disabled in WooCommerce | Enable gateway in WooCommerce → Payments |
| Fee shows on wrong gateway | Gateway ID mismatch | Check gateway ID in settings |

### Finding Gateway IDs

To find the correct gateway ID:

1. Go to your checkout page
2. Right-click on payment option → **Inspect**
3. Look for `value="gateway_id"` in the radio input

Common IDs:
- `stripe` — Credit cards (Payment Plugins)
- `stripe_grabpay` — GrabPay
- `stripe_paynow` — PayNow
- `ppcp-gateway` — PayPal

---

## 8. Technical Reference

### File Location

```
/wp-content/plugins/payment-gateway-fees/payment-gateway-fees.php
```

### WordPress Hooks Used

| Hook | Purpose |
|------|---------|
| `woocommerce_cart_calculate_fees` | Adds fee to cart |
| `woocommerce_after_checkout_form` | Injects JS for real-time updates |
| `admin_menu` | Adds settings page |
| `admin_init` | Registers settings |

### Data Storage

Fees stored in WordPress options table:

```
Option name: pgf_gateway_fees
Option value: {
  "stripe": {
    "enabled": true,
    "label": "Card Processing Fee",
    "type": "both",
    "percent": 2.9,
    "fixed": 0.30,
    "taxable": false
  },
  "stripe_grabpay": {
    "enabled": true,
    "label": "GrabPay Fee",
    "type": "percent",
    "percent": 3.0,
    "fixed": 0,
    "taxable": false
  }
}
```

---

## 9. Quick Reference Card

### Access Settings
**WooCommerce → Gateway Fees**

### Common Fee Configurations

| Gateway | Recommended Setup |
|---------|-------------------|
| Credit Card | Fixed + Percent: $0.30 + 2.9% |
| GrabPay | Percent: 3.0% |
| PayNow | Percent: 0.5% (or absorb) |
| Bank Transfer | Fixed: $0 (free) |

### Checklist Before Go-Live

- [ ] All payment gateways tested
- [ ] Fee labels are customer-friendly
- [ ] Fee amounts match actual costs
- [ ] Test order completed successfully
- [ ] Fee policy documented on website

---

**Ah Ho Fruit • Payment Gateway Fees System** — Page {PAGE}
