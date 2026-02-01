<?php
/**
 * Delivery Order Template
 *
 * Large text, easy to read while driving
 * Focus: Address, contact, delivery instructions, signature
 *
 * @package AhHoInvoicing
 * @since 1.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Load company settings
$company_name = get_option('ah_ho_company_name', 'Ah Ho Fruits Pte Ltd');
$company_address = get_option('ah_ho_company_address', '123 Fruit Lane, Singapore 123456');
$company_phone = get_option('ah_ho_company_phone', '+65 1234 5678');
$company_email = get_option('ah_ho_company_email', 'hello@ahhofruits.com');
$company_logo = AH_HO_INVOICING_PLUGIN_DIR . 'assets/images/ah-ho-logo.png';

// Document title for header
$document_title = 'DELIVERY ORDER';

// Get delivery summary
$summary = AH_HO_Delivery_Order::get_delivery_summary($order);
$instructions = AH_HO_Delivery_Order::get_delivery_instructions($order);

// Delivery date (auto-detect meta key)
$delivery_date = AH_HO_Delivery_Date_Helper::get_delivery_date($order, 'Y-m-d');
if (empty($delivery_date)) {
    $delivery_date = $order->get_date_created()->format('Y-m-d');
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style><?php include AH_HO_INVOICING_PLUGIN_DIR . 'templates/delivery-order/style.css'; ?></style>
</head>
<body>

<?php include AH_HO_INVOICING_PLUGIN_DIR . 'templates/shared/header.php'; ?>

<!-- Order Number & Delivery Date (Large) -->
<div style="background-color: #3498db; color: white; padding: 20px; margin-bottom: 20px; text-align: center;">
    <h1 style="margin: 0; font-size: 48px; font-weight: bold;">
        Order #<?php echo esc_html($order->get_order_number()); ?>
    </h1>
    <div style="font-size: 24px; margin-top: 10px;">
        Delivery Date: <strong><?php echo esc_html(date('l, d M Y', strtotime($delivery_date))); ?></strong>
    </div>
</div>

<!-- DELIVERY ADDRESS (EXTRA LARGE) -->
<div style="background-color: #fff3cd; border: 4px solid #ffc107; padding: 30px; margin-bottom: 20px;">
    <h2 style="margin: 0 0 15px 0; font-size: 24px; color: #856404;">DELIVER TO:</h2>
    <div style="font-size: 28px; line-height: 1.8; color: #000;">
        <strong><?php echo esc_html($order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name()); ?></strong><br>
        <?php if ($order->get_shipping_company()): ?>
            <strong style="color: #e74c3c;"><?php echo esc_html($order->get_shipping_company()); ?></strong><br>
        <?php endif; ?>
        <?php echo esc_html($order->get_shipping_address_1()); ?><br>
        <?php if ($order->get_shipping_address_2()): ?>
            <strong style="color: #e74c3c;"><?php echo esc_html($order->get_shipping_address_2()); ?></strong> (Unit/Floor)<br>
        <?php endif; ?>
        <?php echo esc_html($order->get_shipping_city()); ?><br>
        <strong style="font-size: 36px; color: #e74c3c;">Singapore <?php echo esc_html($order->get_shipping_postcode()); ?></strong>
    </div>
</div>

<!-- CONTACT INFORMATION (LARGE) -->
<div style="background-color: #d1ecf1; border: 3px solid #17a2b8; padding: 20px; margin-bottom: 20px;">
    <h2 style="margin: 0 0 10px 0; font-size: 20px; color: #0c5460;">CUSTOMER CONTACT:</h2>
    <div style="font-size: 32px; font-weight: bold; color: #000;">
        <?php echo esc_html($order->get_billing_phone()); ?>
    </div>
    <?php if ($order->get_billing_email()): ?>
        <div style="font-size: 14px; margin-top: 5px; color: #0c5460;">
            Email: <?php echo esc_html($order->get_billing_email()); ?>
        </div>
    <?php endif; ?>
</div>

<?php if (!empty($instructions)): ?>
<!-- DELIVERY INSTRUCTIONS (HIGHLIGHTED) -->
<div style="background-color: #f8d7da; border: 3px solid #dc3545; padding: 20px; margin-bottom: 20px;">
    <h2 style="margin: 0 0 10px 0; font-size: 20px; color: #721c24;">DELIVERY INSTRUCTIONS:</h2>
    <?php foreach ($instructions as $instruction): ?>
        <div style="margin-bottom: 10px;">
            <strong style="color: #721c24; font-size: 16px;"><?php echo esc_html($instruction['label']); ?>:</strong><br>
            <span style="font-size: 18px; color: #000; font-weight: bold;">
                <?php echo nl2br(esc_html($instruction['value'])); ?>
            </span>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ORDER SUMMARY -->
<div style="background-color: #e8f4f8; border: 2px solid #3498db; padding: 20px; margin-bottom: 20px;">
    <h3 style="margin: 0 0 10px 0; font-size: 18px; color: #2c3e50;">Order Summary:</h3>
    <table style="width: 100%; font-size: 16px;">
        <tr>
            <td style="padding: 5px;"><strong>Total Items:</strong></td>
            <td style="padding: 5px; text-align: right; font-size: 20px; font-weight: bold;">
                <?php echo esc_html($summary['item_count']); ?> items
            </td>
        </tr>
        <tr>
            <td style="padding: 5px;"><strong>Total Weight:</strong></td>
            <td style="padding: 5px; text-align: right; font-size: 20px; font-weight: bold;">
                <?php echo esc_html(number_format($summary['total_weight'], 2)); ?> kg
            </td>
        </tr>
        <tr>
            <td style="padding: 5px;"><strong>Payment Method:</strong></td>
            <td style="padding: 5px; text-align: right; font-size: 18px; font-weight: bold;">
                <?php echo esc_html($summary['payment_method']); ?>
            </td>
        </tr>
        <?php if ($summary['amount_to_collect'] > 0): ?>
        <tr style="background-color: #fff3cd;">
            <td style="padding: 10px;"><strong style="color: #856404; font-size: 18px;">$$$ COLLECT PAYMENT:</strong></td>
            <td style="padding: 10px; text-align: right; font-size: 28px; font-weight: bold; color: #e74c3c;">
                $<?php echo esc_html(number_format($summary['amount_to_collect'], 2)); ?>
            </td>
        </tr>
        <?php endif; ?>
    </table>
</div>

<!-- ITEMS LIST (Compact for driver reference) -->
<h3 style="margin-top: 20px; margin-bottom: 10px; font-size: 16px; background-color: #2c3e50; color: white; padding: 10px;">
    ITEMS IN THIS DELIVERY
</h3>
<table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
    <thead>
        <tr style="background-color: #34495e; color: white; font-size: 12px;">
            <th style="padding: 8px; text-align: left; border: 1px solid #ddd;">Item</th>
            <th style="padding: 8px; text-align: center; width: 60px; border: 1px solid #ddd;">Qty</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($order->get_items() as $item_id => $item): ?>
            <tr style="font-size: 14px;">
                <td style="padding: 8px; border: 1px solid #ddd;">
                    <strong><?php echo esc_html($item->get_name()); ?></strong>
                    <?php
                    // Extract product notes and gift data for special highlighting
                    $product_notes = wc_get_order_item_meta($item_id, __('Special Requests', 'ah-ho-fruits'), true);
                    $is_gift = wc_get_order_item_meta($item_id, __('Gift', 'ah-ho-fruits'), true);
                    $gift_message = wc_get_order_item_meta($item_id, __('Gift Message', 'ah-ho-fruits'), true);

                    // Display item meta (variations - excluding our custom addons)
                    $item_data = $item->get_formatted_meta_data();
                    if (!empty($item_data)):
                    ?>
                        <br><small style="color: #666;">
                            <?php foreach ($item_data as $meta):
                                // Skip our custom addon fields (they're shown below)
                                if (in_array($meta->display_key, [__('Special Requests', 'ah-ho-fruits'), __('Gift', 'ah-ho-fruits'), __('Gift Message', 'ah-ho-fruits')])) {
                                    continue;
                                }
                            ?>
                                <?php echo esc_html($meta->display_key); ?>: <?php echo wp_kses_post($meta->display_value); ?>
                            <?php endforeach; ?>
                        </small>
                    <?php endif; ?>

                    <?php
                    // Display Product Notes (B&W compatible: single border, dotted pattern)
                    if (!empty($product_notes)):
                    ?>
                        <div style="margin-top: 6px; padding: 8px; background-color: #e8f5e9; background-image: repeating-linear-gradient(45deg, transparent, transparent 10px, rgba(46,125,50,0.05) 10px, rgba(46,125,50,0.05) 20px); border: 2px solid #2E7D32; border-radius: 3px; font-size: 12px;">
                            <strong style="color: #2E7D32; font-size: 13px;">** Customer Requests (Preferences/Allergies):</strong><br>
                            <span style="font-weight: bold; color: #000; font-size: 14px;"><?php echo nl2br(esc_html($product_notes)); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php
                    // Display Gift Message (B&W compatible: double border, checkered pattern)
                    if ($is_gift === __('Yes', 'ah-ho-fruits')):
                    ?>
                        <div style="margin-top: 6px; padding: 10px; background-color: #fff3cd; background-image: repeating-linear-gradient(0deg, transparent, transparent 5px, rgba(255,111,0,0.08) 5px, rgba(255,111,0,0.08) 10px), repeating-linear-gradient(90deg, transparent, transparent 5px, rgba(255,111,0,0.08) 5px, rgba(255,111,0,0.08) 10px); border: 3px double #ff6f00; border-radius: 3px; font-size: 12px;">
                            <strong style="color: #ff6f00; font-size: 15px;">*** GIFT - Include Gift Card! ***</strong>
                            <?php if (!empty($gift_message)): ?>
                                <br><span style="font-style: italic; color: #000; font-weight: bold; font-size: 13px;">
                                    "<?php echo nl2br(esc_html($gift_message)); ?>"
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </td>
                <td style="padding: 8px; text-align: center; border: 1px solid #ddd;">
                    <strong style="font-size: 18px;"><?php echo esc_html($item->get_quantity()); ?></strong>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- DRIVER CHECKLIST -->
<div style="background-color: #d4edda; border: 2px solid #28a745; padding: 20px; margin-top: 30px; margin-bottom: 30px;">
    <h3 style="margin: 0 0 10px 0; font-size: 18px; color: #155724;">Driver Checklist:</h3>
    <div style="font-size: 14px; line-height: 2.0;">
        <div>☐ Verify delivery address before leaving warehouse</div>
        <div>☐ Check all items against packing slip</div>
        <div>☐ Read customer delivery instructions above</div>
        <?php if ($summary['amount_to_collect'] > 0): ?>
            <div><strong style="color: #e74c3c;">☐ COLLECT PAYMENT: $<?php echo esc_html(number_format($summary['amount_to_collect'], 2)); ?></strong></div>
        <?php endif; ?>
        <div>☐ Call customer if cannot locate address</div>
        <div>☐ Get customer signature below</div>
        <div>☐ Leave copy of delivery order with customer</div>
    </div>
</div>

<!-- SIGNATURE SECTION (LARGE) -->
<div style="border: 3px solid #000; padding: 30px; margin-top: 30px; page-break-inside: avoid;">
    <h2 style="margin: 0 0 20px 0; font-size: 20px;">DELIVERY CONFIRMATION</h2>

    <table style="width: 100%;">
        <tr>
            <td style="width: 50%; vertical-align: top; padding-right: 10px;">
                <strong style="font-size: 16px;">Driver Name:</strong><br>
                <div style="border-bottom: 2px solid #000; width: 100%; margin-top: 20px; height: 40px;"></div>
                <small>(Print Name)</small>
            </td>
            <td style="width: 50%; vertical-align: top; padding-left: 10px;">
                <strong style="font-size: 16px;">Time Delivered:</strong><br>
                <div style="border-bottom: 2px solid #000; width: 100%; margin-top: 20px; height: 40px;"></div>
                <small>(Date & Time)</small>
            </td>
        </tr>
    </table>

    <div style="margin-top: 30px;">
        <strong style="font-size: 16px;">Customer Signature:</strong><br>
        <div style="border: 2px solid #000; width: 100%; height: 120px; margin-top: 10px; background-color: #f8f9fa;"></div>
        <small>I acknowledge receipt of the items listed above in good condition.</small>
    </div>

    <div style="margin-top: 20px;">
        <strong style="font-size: 16px;">Customer Name (Print):</strong><br>
        <div style="border-bottom: 2px solid #000; width: 60%; margin-top: 10px; height: 40px;"></div>
    </div>

    <?php if ($summary['amount_to_collect'] > 0): ?>
    <div style="margin-top: 20px; background-color: #fff3cd; padding: 15px; border: 2px solid #ffc107;">
        <strong style="font-size: 18px; color: #856404;">$$$ PAYMENT RECEIVED:</strong><br>
        <div style="margin-top: 10px;">
            <span style="font-size: 24px; font-weight: bold;">
                $ <span style="border-bottom: 2px solid #000; display: inline-block; width: 200px; text-align: center;"></span>
            </span>
        </div>
        <small style="color: #856404;">(Amount collected)</small>
    </div>
    <?php endif; ?>
</div>

<!-- EMERGENCY CONTACTS -->
<div style="margin-top: 20px; padding: 15px; background-color: #f8f9fa; border: 1px solid #dee2e6;">
    <strong style="font-size: 14px;">Emergency Contacts:</strong><br>
    <div style="font-size: 12px; margin-top: 5px;">
        Warehouse: <?php echo esc_html($company_phone); ?><br>
        Office: <?php echo esc_html($company_email); ?>
    </div>
</div>

<?php include AH_HO_INVOICING_PLUGIN_DIR . 'templates/shared/footer.php'; ?>

</body>
</html>
