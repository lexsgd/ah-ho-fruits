=== Ah Ho Fruit - Product Add-ons ===
Contributors: Ah Ho Fruit
Requires at least: 6.0
Tested up to: 6.4
Requires PHP: 7.4
WC requires at least: 8.0
WC tested up to: 9.0
Version: 1.0.0
License: Proprietary

Custom product add-ons for gift messages and special requests (preferences/allergies).

== Description ==

This plugin adds two customizable features to WooCommerce products:

**1. Product Notes/Remarks**
Allow customers to specify preferences, allergies, or special requests for products (e.g., "More strawberries please" or "No bananas - allergic").

**2. Gift Messages**
Let customers mark products as gifts and add personalized greeting messages that will be printed and included with delivery.

Both features can be enabled/disabled independently per product.

== Features ==

* Per-product toggle for each feature
* Configurable character limits (50-1000 for notes, 50-500 for gifts)
* Optional required validation
* Custom placeholder text
* Custom field labels
* Character counters with visual feedback
* Display in cart, checkout, and order details
* Enhanced admin order view with visual indicators
* Reminder prompts for gift card printing
* Fully responsive design
* Accessibility-compliant (ARIA labels)

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/ah-ho-product-addons/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Edit any product and configure add-ons in the General tab of Product Data
4. Save the product
5. View the product page to see the add-on fields

== Usage ==

**Enable Product Notes:**
1. Edit a product (e.g., Omakase Fruit Box)
2. Scroll to the Product Data panel
3. In the General tab, find "Product Add-ons" section
4. Check "Enable Product Notes"
5. Configure:
   - Field Label (default: "Special Requests")
   - Placeholder Text
   - Character Limit (default: 300)
   - Make Required (optional)
6. Save product

**Enable Gift Messages:**
1. In the same Product Add-ons section
2. Check "Enable Gift Message"
3. Configure:
   - Placeholder Text
   - Character Limit (default: 250)
   - Require Message (optional - requires message when gift checkbox is ticked)
4. Save product

**Frontend Behavior:**
- Product Notes: Always visible textarea (if enabled)
- Gift Message: Hidden until "This is a gift" checkbox is ticked
- Both fields can coexist on the same product
- Character counters update in real-time
- Validation errors prevent checkout if required fields are empty

**Admin Order View:**
- Product notes displayed in green box with 📝 icon
- Gift items displayed in yellow box with 🎁 icon
- Gift messages shown with reminder to print gift card
- All data visible in order line item meta

== Frequently Asked Questions ==

= Can I use both features on the same product? =
Yes! Product Notes and Gift Messages can both be enabled on a single product.

= What happens if a customer unchecks the gift checkbox? =
The gift message field will slide up and the message will be cleared.

= Can I customize the field labels? =
Yes, you can customize the label for Product Notes. Gift Message uses a standard label.

= Does this integrate with the existing Ah Ho Invoicing plugin? =
Yes, see the PDF Integration section below for instructions.

= What's the maximum character limit? =
- Product Notes: 50-1000 characters
- Gift Messages: 50-500 characters

== PDF Integration ==

To display add-on data in packing slips and delivery orders, modify your existing Ah Ho Invoicing plugin:

**File:** `/wp-content/plugins/ah-ho-invoicing/includes/class-packing-slip.php`

Add after displaying product name/quantity (around line 150-200):

```php
// Display Product Notes
$product_notes = wc_get_order_item_meta( $item->get_id(), __( 'Special Requests', 'ah-ho-fruits' ), true );
if ( $product_notes ) {
    $html .= '<div style="margin-top: 8px; padding: 8px; background: #e8f5e9; border-left: 3px solid #2E7D32;">';
    $html .= '<strong>📝 SPECIAL REQUESTS:</strong><br>';
    $html .= '<div style="margin-top: 5px; font-size: 11px;">' . nl2br( esc_html( $product_notes ) ) . '</div>';
    $html .= '</div>';
}

// Display Gift Message
$is_gift = wc_get_order_item_meta( $item->get_id(), __( 'Gift', 'ah-ho-fruits' ), true );
$gift_message = wc_get_order_item_meta( $item->get_id(), __( 'Gift Message', 'ah-ho-fruits' ), true );

if ( $is_gift === __( 'Yes', 'ah-ho-fruits' ) ) {
    $html .= '<div style="margin-top: 8px; padding: 8px; background: #fff3cd; border-left: 3px solid #ff6f00;">';
    $html .= '<strong>🎁 GIFT ITEM</strong>';

    if ( $gift_message ) {
        $html .= '<div style="margin-top: 5px; font-style: italic; font-size: 11px;">';
        $html .= 'Message: "' . nl2br( esc_html( $gift_message ) ) . '"';
        $html .= '</div>';
    }

    $html .= '</div>';
}
```

Repeat for `class-delivery-order.php` if you want it on delivery orders too.

== Changelog ==

= 1.0.0 - 2026-01-25 =
* Initial release
* Product notes/remarks feature
* Gift message feature
* Per-product configuration
* Character counters
* Cart & checkout integration
* Admin order indicators
* Responsive design
* Accessibility enhancements

== Developer Notes ==

**Action Hooks:**
- `woocommerce_product_options_general_product_data` - Add admin fields
- `woocommerce_process_product_meta` - Save admin fields
- `woocommerce_before_add_to_cart_button` - Display frontend fields
- `woocommerce_checkout_create_order_line_item` - Save to order
- `woocommerce_after_order_itemmeta` - Display in admin

**Filter Hooks:**
- `woocommerce_add_to_cart_validation` - Validate input
- `woocommerce_add_cart_item_data` - Add to cart data
- `woocommerce_get_item_data` - Display in cart

**Meta Keys:**
- `_enable_product_notes` - Enable notes (yes/no)
- `_product_notes_label` - Custom label
- `_product_notes_placeholder` - Placeholder text
- `_product_notes_char_limit` - Character limit
- `_product_notes_required` - Required field (yes/no)
- `_enable_gift_message` - Enable gift (yes/no)
- `_gift_message_placeholder` - Placeholder text
- `_gift_message_char_limit` - Character limit
- `_gift_message_required` - Required when checked (yes/no)

== Support ==

For support, contact Ah Ho Fruit development team.

== Upgrade Notice ==

= 1.0.0 =
Initial release. No upgrade required.
