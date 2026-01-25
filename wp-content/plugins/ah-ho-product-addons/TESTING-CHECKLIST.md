# Testing Checklist - Ah Ho Product Add-ons

**Version:** 1.0.0
**Date:** 2026-01-25

Use this checklist to thoroughly test the plugin before going live.

---

## Pre-Testing Setup

- [ ] Plugin installed in `/wp-content/plugins/ah-ho-product-addons/`
- [ ] Plugin activated successfully
- [ ] WooCommerce 8.0+ is active
- [ ] Test product ready (e.g., Omakase Fruit Box)
- [ ] Browser console open (F12) to catch errors

---

## 1. Admin Configuration Testing

### Product Settings

- [ ] Navigate to Products > Edit Product
- [ ] Find "Product Add-ons" section in General tab
- [ ] Verify two sections visible:
  - [ ] 📝 Product Notes/Remarks (green background)
  - [ ] 🎁 Gift Message (yellow background)

### Product Notes Configuration

- [ ] Check "Enable Product Notes" checkbox
- [ ] Enter custom field label: `Special Requests`
- [ ] Enter placeholder: `E.g., "More strawberries" or "No bananas"`
- [ ] Set character limit: `300`
- [ ] Leave "Make Required" unchecked
- [ ] Click **Update** button
- [ ] Reload page - verify settings saved correctly

### Gift Message Configuration

- [ ] Check "Enable Gift Message" checkbox
- [ ] Enter placeholder: `Enter your heartfelt message...`
- [ ] Set character limit: `250`
- [ ] Leave "Require Message" unchecked
- [ ] Click **Update** button
- [ ] Reload page - verify settings saved correctly

---

## 2. Frontend Display Testing

### Product Page - Initial Load

- [ ] Visit product page as customer (logged out)
- [ ] Verify addon wrapper appears above "Add to Basket" button
- [ ] Verify Product Notes section visible:
  - [ ] Label shows "Special Requests (Preferences / Allergies)"
  - [ ] Textarea visible
  - [ ] Placeholder text correct
  - [ ] Character counter shows "0 / 300 characters"
- [ ] Verify Gift section visible:
  - [ ] "🎁 This is a gift" checkbox visible
  - [ ] Gift message field is HIDDEN initially
  - [ ] No JavaScript errors in console

### Gift Message Toggle

- [ ] Click gift checkbox
- [ ] Verify gift message field slides down (smooth animation)
- [ ] Verify cursor focuses in textarea
- [ ] Verify character counter shows "0 / 250 characters"
- [ ] Unclick gift checkbox
- [ ] Verify gift message field slides up
- [ ] Verify message is cleared
- [ ] Check gift checkbox again for next tests

### Character Counter - Product Notes

- [ ] Type in product notes field: `More strawberries please`
- [ ] Verify counter updates in real-time
- [ ] Type until ~270 characters (90% of limit)
- [ ] Verify counter turns orange/warning color
- [ ] Continue typing to 300 characters
- [ ] Verify typing stops at limit (maxlength enforced)

### Character Counter - Gift Message

- [ ] Type in gift message: `Happy Birthday!`
- [ ] Verify counter updates in real-time
- [ ] Type until ~225 characters (90% of limit)
- [ ] Verify counter turns orange
- [ ] Continue typing to 250 characters
- [ ] Verify typing stops at limit

---

## 3. Validation Testing

### Optional Fields (Default)

- [ ] Leave both fields empty
- [ ] Click "Add to Basket"
- [ ] Verify product adds to cart successfully (no errors)

### With Product Notes Only

- [ ] Clear cart
- [ ] Enter product notes: `No bananas - allergic`
- [ ] Leave gift checkbox unchecked
- [ ] Click "Add to Basket"
- [ ] Verify product adds successfully

### With Gift Message Only

- [ ] Clear cart
- [ ] Leave product notes empty
- [ ] Check gift checkbox
- [ ] Enter gift message: `Enjoy! Love, Sarah`
- [ ] Click "Add to Basket"
- [ ] Verify product adds successfully

### With Both

- [ ] Clear cart
- [ ] Enter product notes: `Extra strawberries please`
- [ ] Check gift checkbox
- [ ] Enter gift message: `Happy Birthday Mom!`
- [ ] Click "Add to Basket"
- [ ] Verify product adds successfully

### Required Validation (Optional - Enable First)

If you enabled "Make Required" in admin:

- [ ] Clear cart
- [ ] Leave product notes empty (if set as required)
- [ ] Try to add to cart
- [ ] Verify error message appears
- [ ] Verify cannot add to cart
- [ ] Fill in notes
- [ ] Verify now adds to cart

- [ ] Clear cart
- [ ] Check gift checkbox
- [ ] Leave gift message empty (if set as required when gift checked)
- [ ] Try to add to cart
- [ ] Verify error message appears
- [ ] Fill in message
- [ ] Verify now adds to cart

---

## 4. Cart Display Testing

### View Cart Page

- [ ] Add product with both notes and gift message
- [ ] Navigate to Cart page
- [ ] Verify product shows addon data:
  - [ ] "Special Requests: [your notes text]"
  - [ ] "Gift: 🎁 Yes"
  - [ ] "Gift Message: [your message text]"
- [ ] Verify line breaks preserved (if you entered multi-line text)
- [ ] Verify special characters display correctly

### Cart Item Uniqueness

- [ ] Add product with notes: `Request A`
- [ ] Add same product again with notes: `Request B`
- [ ] Verify TWO separate line items in cart (not merged)
- [ ] Verify each has correct notes

---

## 5. Checkout Testing

### Checkout Page Display

- [ ] Proceed to checkout
- [ ] Verify addon data visible in order review:
  - [ ] Product notes shown
  - [ ] Gift indicator shown
  - [ ] Gift message shown
- [ ] Complete checkout (test mode payment)
- [ ] Verify order success page shows addon data

---

## 6. Admin Order View Testing

### Order Details Page

- [ ] Go to WooCommerce > Orders
- [ ] Click on the test order
- [ ] Scroll to order items section
- [ ] Verify product notes display:
  - [ ] Green box with 📝 icon
  - [ ] Label: "SPECIAL REQUESTS:"
  - [ ] Text in bold, readable
- [ ] Verify gift display:
  - [ ] Yellow box with 🎁 icon
  - [ ] "GIFT ITEM" header
  - [ ] Gift message in quotes
  - [ ] Reminder: "Remember to print gift card for delivery"

### Order Item Meta

- [ ] Expand order item details
- [ ] Verify meta data visible:
  - [ ] "Special Requests" with value
  - [ ] "Gift" with value "Yes"
  - [ ] "Gift Message" with value

---

## 7. PDF Generation Testing

### Generate Packing Slip

- [ ] In order admin, find "Generate PDF" section (from Ah Ho Invoicing plugin)
- [ ] Click "Generate Packing Slip" button
- [ ] Download and open PDF
- [ ] Verify product notes display:
  - [ ] Green box visible
  - [ ] "📝 SPECIAL REQUESTS:" label
  - [ ] Notes text in bold
  - [ ] Box has green left border
- [ ] Verify gift message display:
  - [ ] Yellow box visible
  - [ ] "🎁 GIFT ITEM" label
  - [ ] Message in quotes
  - [ ] "Remember to print gift card" warning visible
  - [ ] Box has orange left border

### Generate Delivery Order

- [ ] Click "Generate Delivery Order" button
- [ ] Download and open PDF
- [ ] Verify product notes display:
  - [ ] Green box in items list
  - [ ] "📝 Customer Requests:" label
  - [ ] Text larger than packing slip (14px font)
  - [ ] Readable for driver
- [ ] Verify gift message display:
  - [ ] Yellow box in items list
  - [ ] "🎁 GIFT - Include Gift Card!" label
  - [ ] Message prominent and bold
  - [ ] Clear visual indicator

### PDF Print Quality

- [ ] Print packing slip PDF
- [ ] Verify colors print clearly:
  - [ ] Green boxes distinguishable
  - [ ] Yellow boxes distinguishable
  - [ ] Text readable
- [ ] Verify borders visible when printed

---

## 8. Edge Case Testing

### Special Characters

- [ ] Add product with notes containing:
  - [ ] Line breaks (press Enter)
  - [ ] Quotes: `"This is quoted"`
  - [ ] Apostrophes: `Sarah's favorite`
  - [ ] Symbols: `@ # $ % & *`
- [ ] Add to cart and complete order
- [ ] Verify all characters display correctly in:
  - [ ] Cart
  - [ ] Admin order view
  - [ ] PDFs

### Maximum Length

- [ ] Enter exactly 300 characters in product notes
- [ ] Verify counter shows "300 / 300 characters"
- [ ] Verify orange warning color
- [ ] Try typing more - should stop at 300
- [ ] Add to cart
- [ ] Verify full text saved (not truncated)

### Unicode & Emoji

- [ ] Enter notes with emoji: `🍓 strawberries only 🍇`
- [ ] Enter gift message with emoji: `💐 Happy Birthday! 🎉`
- [ ] Add to cart and check order
- [ ] Verify emoji display correctly everywhere

### Empty Strings

- [ ] Type in notes field then delete all text
- [ ] Uncheck gift checkbox after typing message
- [ ] Add to cart
- [ ] Verify no empty meta keys saved to order

### Multiple Products

- [ ] Add Product A with notes
- [ ] Add Product B with gift message
- [ ] Add Product C with both
- [ ] Add Product D with neither
- [ ] Complete order
- [ ] Verify each product shows correct addon data in admin
- [ ] Generate packing slip
- [ ] Verify each item shows correct highlights

---

## 9. Responsive Design Testing

### Mobile View (375px)

- [ ] Open product page on mobile
- [ ] Verify addon sections stack properly
- [ ] Verify textareas are full-width
- [ ] Verify character counters visible
- [ ] Verify buttons don't overlap
- [ ] Test gift checkbox toggle
- [ ] Test typing in fields (no zoom on iOS)

### Tablet View (768px)

- [ ] Open product page on tablet
- [ ] Verify layout adapts properly
- [ ] Verify textareas sized appropriately
- [ ] Test all interactions

### Desktop View (1920px)

- [ ] Open on large desktop
- [ ] Verify addon sections don't stretch too wide
- [ ] Verify readable font sizes
- [ ] Test all interactions

---

## 10. Browser Compatibility Testing

### Chrome/Edge (Chromium)

- [ ] Product page loads
- [ ] Gift toggle works
- [ ] Character counters work
- [ ] Add to cart works
- [ ] No console errors

### Firefox

- [ ] Product page loads
- [ ] Gift toggle works
- [ ] Character counters work
- [ ] Add to cart works
- [ ] No console errors

### Safari (if accessible)

- [ ] Product page loads
- [ ] Gift toggle works
- [ ] Character counters work
- [ ] Add to cart works
- [ ] No console errors

---

## 11. Performance Testing

### Page Load Speed

- [ ] Open product page with DevTools Network tab
- [ ] Verify CSS loads (~5KB)
- [ ] Verify JS loads (~3KB)
- [ ] Total load time reasonable (<2s)

### No Memory Leaks

- [ ] Open/close product page 10 times
- [ ] Check browser memory usage (DevTools Performance)
- [ ] Verify no significant memory increase

---

## 12. Accessibility Testing

### Keyboard Navigation

- [ ] Tab through product page
- [ ] Verify can reach product notes textarea
- [ ] Verify can reach gift checkbox
- [ ] Verify can reach gift message textarea (when visible)
- [ ] Press Enter on gift checkbox - should toggle

### Screen Reader (Optional)

- [ ] Enable screen reader (NVDA/JAWS/VoiceOver)
- [ ] Verify field labels are announced
- [ ] Verify character counters are announced (aria-live)
- [ ] Verify required fields are indicated

---

## 13. Conflict Testing

### With Other Plugins

- [ ] Activate common plugins (if available):
  - [ ] Yoast SEO
  - [ ] Contact Form 7
  - [ ] Elementor
- [ ] Verify product page still works
- [ ] Verify no JavaScript conflicts

### With Theme

- [ ] Switch to default theme (Twenty Twenty-Three)
- [ ] Verify addon sections still appear
- [ ] Verify styling still applies
- [ ] Switch back to Ah Ho theme
- [ ] Verify everything still works

---

## 14. Data Persistence Testing

### Session Persistence

- [ ] Add product with addons to cart
- [ ] Close browser
- [ ] Reopen browser (same session)
- [ ] Visit cart page
- [ ] Verify addon data still there

### Order Persistence

- [ ] Place order with addons
- [ ] Wait 24 hours
- [ ] View order in admin
- [ ] Verify addon data still visible
- [ ] Regenerate PDFs
- [ ] Verify addon data still renders

---

## 15. Deactivation/Reactivation Testing

### Plugin Deactivation

- [ ] Deactivate plugin
- [ ] View existing orders with addon data
- [ ] Verify addon data still visible (stored in order meta)
- [ ] View product page
- [ ] Verify addon sections do not appear

### Plugin Reactivation

- [ ] Reactivate plugin
- [ ] View product page
- [ ] Verify addon sections reappear
- [ ] Add product with addons
- [ ] Complete order
- [ ] Verify works as before

---

## Bugs Found

Document any bugs here:

| # | Description | Steps to Reproduce | Severity | Status |
|---|-------------|-------------------|----------|--------|
| 1 | | | | |
| 2 | | | | |
| 3 | | | | |

---

## Sign-Off

### Tested By

- Name: ___________________________
- Date: ___________________________
- Environment: _____________________

### Test Results

- [ ] All tests passed
- [ ] Minor issues found (documented above)
- [ ] Major issues found (do not deploy)

### Approval

- [ ] Approved for production deployment
- [ ] Requires fixes before deployment

**Signature:** ___________________________

---

**Testing completed successfully? Time to deploy! 🚀**
