# Ah Ho Product Add-ons - Installation & Testing Guide

**Version:** 1.0.0
**Date:** 2026-01-25
**Plugin Name:** Ah Ho Fruit - Product Add-ons

---

## What This Plugin Does

Adds two customer customization features to WooCommerce products:

1. **Product Notes/Remarks** - Customers can specify preferences, allergies, or special requests
   Example: "More strawberries please" or "No bananas - allergic"

2. **Gift Messages** - Customers can mark products as gifts and add personalized greeting messages
   Example: "Happy Birthday! Enjoy these fresh fruits. Love, Sarah"

Both features:
- Can be enabled/disabled per product
- Display in cart, checkout, and order details
- Show prominently on packing slips and delivery orders (with PDF integration)
- Include character counters and validation

---

## Installation Steps

### Step 1: Upload Plugin Files

**Location:** `/wp-content/plugins/ah-ho-product-addons/`

Upload the entire plugin folder to your WordPress plugins directory. You can use:
- **FTP/SFTP** - Upload via FileZilla or similar
- **cPanel File Manager** - Upload and extract ZIP
- **WordPress Admin** - Plugins > Add New > Upload Plugin (ZIP the folder first)

**Verify structure:**
```
/wp-content/plugins/ah-ho-product-addons/
├── ah-ho-product-addons.php
├── includes/
│   ├── class-admin-settings.php
│   ├── class-frontend-display.php
│   ├── class-cart-handler.php
│   └── class-order-handler.php
├── assets/
│   ├── css/product-addons.css
│   └── js/product-addons.js
├── readme.txt
├── INSTALLATION-GUIDE.md (this file)
└── TESTING-CHECKLIST.md
```

### Step 2: Activate Plugin

1. Go to WordPress Admin > **Plugins**
2. Find "Ah Ho Fruit - Product Add-ons"
3. Click **Activate**
4. You should see a success message
5. No configuration needed - ready to use!

**Requirements Check:**
- ✅ WordPress 6.0+
- ✅ WooCommerce 8.0+
- ✅ PHP 7.4+

If you see an error about WooCommerce missing, activate WooCommerce first.

---

## Configuration Per Product

### Enable for Omakase Fruit Box (Example)

1. Go to **Products > All Products**
2. Click **Edit** on "Omakase Fruit Box" product
3. Scroll to **Product Data** panel
4. Click **General** tab
5. Scroll down to find "**Product Add-ons**" section

### Configure Product Notes

In the **📝 Product Notes/Remarks** section:

| Field | Recommended Value | Purpose |
|-------|-------------------|---------|
| **Enable Product Notes** | ✅ Checked | Turn on notes feature |
| **Field Label** | `Special Requests` | What customers see |
| **Placeholder Text** | `E.g., "More strawberries" or "No bananas - allergic"` | Example text in field |
| **Character Limit** | `300` | Maximum length (50-1000) |
| **Make Required** | ☐ Unchecked | Optional (check if mandatory) |

### Configure Gift Messages

In the **🎁 Gift Message** section:

| Field | Recommended Value | Purpose |
|-------|-------------------|---------|
| **Enable Gift Message** | ✅ Checked | Turn on gift feature |
| **Placeholder Text** | `Enter your heartfelt message here...` | Example text |
| **Character Limit** | `250` | Maximum length (50-500) |
| **Require Message** | ☐ Unchecked | Optional (check to require message when gift checkbox is ticked) |

### Save Product

Click **Update** button to save changes.

---

## What Customers See

### On Product Page

When customers visit the Omakase Fruit Box page:

**Product Notes Section** (always visible if enabled):
```
┌─────────────────────────────────────────────┐
│  Special Requests (Preferences / Allergies) │
│  ┌─────────────────────────────────────┐   │
│  │ E.g., "More strawberries" or        │   │
│  │ "No bananas - allergic"              │   │
│  │                                       │   │
│  └─────────────────────────────────────┘   │
│  0 / 300 characters                         │
└─────────────────────────────────────────────┘
```

**Gift Section** (checkbox initially visible, message hidden):
```
┌─────────────────────────────────────────────┐
│  🎁 ☐ This is a gift                        │
│                                              │
│  [When checked, message field appears:]     │
│                                              │
│  Gift Message                                │
│  ┌─────────────────────────────────────┐   │
│  │ Enter your heartfelt message...      │   │
│  └─────────────────────────────────────┘   │
│  0 / 250 characters                         │
└─────────────────────────────────────────────┘

[Add to Basket]
```

### In Cart & Checkout

Addon data displays as item meta:
```
Omakase Fruit Box × 1

Special Requests: More strawberries, no bananas please
Gift: 🎁 Yes
Gift Message: Happy Birthday! Enjoy these fresh fruits. Love, Sarah
```

---

## What Admin Sees

### In Order Details (WooCommerce Admin)

When viewing an order with addons:

**Product Notes** (Green box):
```
┌────────────────────────────────────────────┐
│ 📝 SPECIAL REQUESTS:                       │
│ More strawberries, no bananas please       │
└────────────────────────────────────────────┘
```

**Gift Items** (Yellow box):
```
┌────────────────────────────────────────────┐
│ 🎁 GIFT ITEM                               │
│ Message: "Happy Birthday! Enjoy these      │
│ fresh fruits. Love, Sarah"                 │
│ ⚠️ Remember to print gift card for         │
│    delivery                                 │
└────────────────────────────────────────────┘
```

### On Packing Slips (PDF)

When generating packing slips, addon data appears:
- Product notes in **green box** with 📝 icon
- Gift messages in **yellow box** with 🎁 icon
- Bold "Remember to print gift card" reminder for gifts

### On Delivery Orders (PDF)

Driver sees addon data highlighted:
- Product notes: "Customer Requests" in green
- Gift messages: "GIFT - Include Gift Card!" in yellow
- Larger font for driver readability

---

## PDF Integration (Already Done)

The plugin is **already integrated** with your existing "Ah Ho Invoicing" plugin. Changes were made to:

1. `/wp-content/plugins/ah-ho-invoicing/templates/packing-slip/packing-slip.php`
2. `/wp-content/plugins/ah-ho-invoicing/templates/delivery-order/delivery-order.php`

These templates now automatically display product notes and gift messages with special highlighting.

**No additional configuration needed!**

---

## Testing Checklist

See `TESTING-CHECKLIST.md` for a comprehensive testing guide.

Quick smoke test:
1. ✅ Activate plugin
2. ✅ Enable both features on Omakase Box product
3. ✅ Visit product page - see both sections
4. ✅ Add product with notes and gift message
5. ✅ Check cart - addon data visible
6. ✅ Complete checkout
7. ✅ View order in admin - see highlighted boxes
8. ✅ Generate packing slip PDF - verify display

---

## Enabling for Other Products

You can enable Product Notes and/or Gift Messages for **any product**:

### Use Cases

| Product Type | Enable Notes? | Enable Gift? | Reason |
|--------------|---------------|--------------|--------|
| Omakase Fruit Box | ✅ Yes | ✅ Yes | Highly customizable, often gifted |
| Premium Gift Hamper | ☐ No | ✅ Yes | Pre-curated, gift-only |
| Individual Fruits | ✅ Yes | ☐ No | Preferences matter, rarely gifted |
| Fruit Platters | ✅ Yes | ✅ Yes | Corporate gifts, preferences |
| Add-ons (honey, etc.) | ☐ No | ☐ No | No customization needed |

**Pro Tip:** Enable features conservatively at first. You can always enable more later.

---

## Uninstalling (If Needed)

1. Go to **Plugins**
2. **Deactivate** "Ah Ho Product Add-ons"
3. Click **Delete** (this removes plugin files)

**Note:** Existing order data (notes/messages) will remain in order meta even after uninstall. This is intentional - historical orders keep their data.

---

## Troubleshooting

### Plugin activated but sections don't appear on product page

**Check:**
- Is the feature enabled in Product Data > General > Product Add-ons?
- Did you click **Update** to save the product?
- Hard refresh page (Ctrl+F5 / Cmd+Shift+R)

### Gift message field doesn't slide down when checkbox is clicked

**Check:**
- Browser console for JavaScript errors (F12)
- Is jQuery loaded? (WooCommerce should load it)
- Clear browser cache

### Addon data doesn't save to order

**Check:**
- View cart page - is data visible there?
- Check WooCommerce > Status > Logs for errors
- Verify WooCommerce version is 8.0+

### PDF doesn't show addon data

**Check:**
- Is "Ah Ho Invoicing" plugin active?
- Regenerate PDF (delete cached version first)
- Check if order actually has addon data (view order in admin)

### Character counter doesn't work

**Check:**
- Is JavaScript enabled in browser?
- Check browser console (F12) for errors
- Try different browser to isolate issue

---

## Advanced Configuration

### Changing Character Limits

Limits are configurable per product in Product Data panel:
- **Product Notes:** 50-1000 characters (default: 300)
- **Gift Messages:** 50-500 characters (default: 250)

### Making Fields Required

You can make fields mandatory:
- **Product Notes Required:** Customer MUST enter notes to add to cart
- **Gift Message Required:** When gift checkbox is ticked, message is mandatory

**Recommendation:** Keep both optional for better UX. Customers who need them will use them.

### Custom Labels

You can change the label for Product Notes per product:
- Default: "Special Requests"
- Could be: "Dietary Requirements", "Preferences", "Allergies", etc.

Gift Message label is fixed: "Gift Message"

---

## Performance Notes

- **Lightweight:** Only ~150 lines of PHP code
- **No database tables:** Uses existing WooCommerce infrastructure
- **Minimal JS:** ~100 lines for character counters and toggle
- **Small CSS:** ~150 lines for styling
- **No external dependencies:** Pure WordPress/WooCommerce

**Impact:** Negligible. Plugin loads only on product pages.

---

## Support

For issues or questions, contact your development team.

**Plugin Files:**
- Main plugin: `/wp-content/plugins/ah-ho-product-addons/`
- PDF integration: `/wp-content/plugins/ah-ho-invoicing/templates/`

**Documentation:**
- `readme.txt` - Full plugin documentation
- `INSTALLATION-GUIDE.md` - This file
- `TESTING-CHECKLIST.md` - Testing procedures

---

## Version History

### v1.0.0 - 2026-01-25
- Initial release
- Product Notes/Remarks feature
- Gift Message feature
- PDF integration with Ah Ho Invoicing plugin
- Character counters
- Validation
- Responsive design
- Accessibility features

---

**Happy Fruit Selling! 🍓🍇🍊🍌🥝**
