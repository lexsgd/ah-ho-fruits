<?php
/**
 * Packing Slip Template (Single Order)
 *
 * Focus: SKU, Quantity, Weight
 * NO PRICES - for storeman/warehouse staff
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
$document_title = 'PACKING SLIP';
?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style><?php include AH_HO_INVOICING_PLUGIN_DIR . 'templates/packing-slip/style.css'; ?></style>
</head>
<body>

<?php include AH_HO_INVOICING_PLUGIN_DIR . 'templates/shared/header.php'; ?>

<!-- Order Information -->
<table style="width: 100%; margin-bottom: 20px; font-size: 12px;">
    <tr>
        <td style="width: 50%; vertical-align: top;">
            <strong style="font-size: 14px; display: block; margin-bottom: 5px;">Order #<?php echo esc_html($order->get_order_number()); ?></strong>
            <strong>Order Date:</strong> <?php echo esc_html($order->get_date_created()->format('d M Y')); ?><br>
            <strong>Delivery Date:</strong> <?php
                $delivery_date = AH_HO_Delivery_Date_Helper::get_delivery_date($order, 'd M Y');
                echo esc_html($delivery_date ?: 'Not specified');
            ?><br>
            <strong>Weight:</strong> <?php echo esc_html(AH_HO_Packing_Slip::get_order_weight($order)); ?> kg
        </td>
        <td style="width: 50%; text-align: right; vertical-align: top;">
            <strong style="font-size: 14px; display: block; margin-bottom: 5px;">Delivery Address:</strong>
            <strong><?php echo esc_html($order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name()); ?></strong><br>
            <?php if ($order->get_shipping_company()): ?>
                <?php echo esc_html($order->get_shipping_company()); ?><br>
            <?php endif; ?>
            <?php echo esc_html($order->get_shipping_address_1()); ?><br>
            <?php if ($order->get_shipping_address_2()): ?>
                <?php echo esc_html($order->get_shipping_address_2()); ?><br>
            <?php endif; ?>
            <?php echo esc_html($order->get_shipping_city()); ?><br>
            <strong>Singapore <?php echo esc_html($order->get_shipping_postcode()); ?></strong><br>
            <strong>Tel:</strong> <?php echo esc_html($order->get_billing_phone()); ?>
        </td>
    </tr>
</table>

<?php
// Display customer notes if present
echo AH_HO_Packing_Slip::format_customer_notes($order);
?>

<!-- Items to Pack -->
<h3 style="margin-top: 20px; margin-bottom: 10px; font-size: 16px; background-color: #2c3e50; color: white; padding: 10px;">
    ITEMS TO PACK
</h3>

<table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
    <thead>
        <tr style="background-color: #34495e; color: white;">
            <th style="padding: 10px; text-align: left; border: 1px solid #ddd;">Item</th>
            <th style="padding: 10px; text-align: center; width: 80px; border: 1px solid #ddd;">SKU</th>
            <th style="padding: 10px; text-align: center; width: 80px; border: 1px solid #ddd;">Qty</th>
            <th style="padding: 10px; text-align: center; width: 80px; border: 1px solid #ddd;">Weight (kg)</th>
            <th style="padding: 10px; text-align: center; width: 60px; border: 1px solid #ddd;">OK</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $total_items = 0;
        $total_weight = 0;
        foreach ($order->get_items() as $item_id => $item):
            $product = $item->get_product();
            $quantity = $item->get_quantity();
            $total_items += $quantity;

            $item_weight = $product ? ((float) $product->get_weight() * $quantity) : 0;
            $total_weight += $item_weight;
        ?>
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px; vertical-align: top; border: 1px solid #ddd;">
                    <strong><?php echo esc_html($item->get_name()); ?></strong>
                    <?php
                    // Extract product notes and gift data - check multiple possible meta keys
                    $special_request_keys = ['Special Requests', __('Special Requests', 'ah-ho-fruits'), 'special_requests', '_special_requests'];
                    $gift_keys = ['Gift', __('Gift', 'ah-ho-fruits'), 'gift', '_gift'];
                    $gift_message_keys = ['Gift Message', __('Gift Message', 'ah-ho-fruits'), 'gift_message', '_gift_message'];

                    $product_notes = '';
                    $is_gift = '';
                    $gift_message = '';

                    // Try each possible key for special requests
                    foreach ($special_request_keys as $key) {
                        $value = wc_get_order_item_meta($item_id, $key, true);
                        if (!empty($value)) {
                            $product_notes = $value;
                            break;
                        }
                    }

                    // Try each possible key for gift
                    foreach ($gift_keys as $key) {
                        $value = wc_get_order_item_meta($item_id, $key, true);
                        if (!empty($value)) {
                            $is_gift = $value;
                            break;
                        }
                    }

                    // Try each possible key for gift message
                    foreach ($gift_message_keys as $key) {
                        $value = wc_get_order_item_meta($item_id, $key, true);
                        if (!empty($value)) {
                            $gift_message = $value;
                            break;
                        }
                    }

                    // Build list of keys to skip in general meta display
                    $skip_keys = array_merge($special_request_keys, $gift_keys, $gift_message_keys);

                    // Display item meta (variations, custom options - excluding our custom addons)
                    $item_data = $item->get_formatted_meta_data();
                    $has_other_meta = false;
                    if (!empty($item_data)):
                        foreach ($item_data as $meta):
                            // Skip our custom addon fields (they're shown below with highlighting)
                            $should_skip = false;
                            foreach ($skip_keys as $skip_key) {
                                if (strcasecmp($meta->display_key, $skip_key) === 0) {
                                    $should_skip = true;
                                    break;
                                }
                            }
                            if ($should_skip) continue;

                            // Also check if we haven't captured the value yet (fallback)
                            if (empty($product_notes) && stripos($meta->display_key, 'special') !== false) {
                                $product_notes = strip_tags($meta->display_value);
                                continue;
                            }
                            if (empty($is_gift) && strcasecmp($meta->display_key, 'Gift') === 0) {
                                $is_gift = strip_tags($meta->display_value);
                                continue;
                            }
                            if (empty($gift_message) && stripos($meta->display_key, 'gift message') !== false) {
                                $gift_message = strip_tags($meta->display_value);
                                continue;
                            }

                            if (!$has_other_meta) {
                                echo '<br><small style="color: #666;">';
                                $has_other_meta = true;
                            }
                            echo esc_html($meta->display_key) . ': ' . wp_kses_post($meta->display_value) . '<br>';
                        endforeach;
                        if ($has_other_meta) {
                            echo '</small>';
                        }
                    endif;
                    ?>

                    <?php
                    // Display Customer Requests (GREEN box - prominent for packer attention)
                    if (!empty($product_notes)):
                    ?>
                        <div style="margin-top: 10px; padding: 12px; background-color: #e8f5e9; border: 3px solid #2E7D32; border-radius: 4px;">
                            <strong style="color: #2E7D32; font-size: 13px; display: block; margin-bottom: 5px;">** Customer Requests (Preferences/Allergies):</strong>
                            <span style="font-weight: bold; color: #000; font-size: 12px;"><?php echo nl2br(esc_html($product_notes)); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php
                    // Display Gift Section (ORANGE box - prominent for packer attention)
                    $is_gift_order = (strtolower($is_gift) === 'yes' || $is_gift === '1' || $is_gift === 'true' || !empty($gift_message));
                    if ($is_gift_order):
                    ?>
                        <div style="margin-top: 10px; padding: 12px; background-color: #fff8e1; border: 3px solid #FF6F00; border-radius: 4px;">
                            <strong style="color: #FF6F00; font-size: 13px; display: block; margin-bottom: 5px;">*** GIFT - Include Gift Card! ***</strong>
                            <?php if (!empty($gift_message)): ?>
                                <span style="font-style: italic; color: #000; font-weight: bold; font-size: 12px;">"<?php echo nl2br(esc_html($gift_message)); ?>"</span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </td>
                <td style="padding: 10px; text-align: center; vertical-align: top; border: 1px solid #ddd;">
                    <?php if ($product && $product->get_sku()): ?>
                        <strong><?php echo esc_html($product->get_sku()); ?></strong>
                    <?php else: ?>
                        <span style="color: #999;">-</span>
                    <?php endif; ?>
                </td>
                <td style="padding: 10px; text-align: center; vertical-align: top; border: 1px solid #ddd;">
                    <strong style="font-size: 16px;"><?php echo esc_html($quantity); ?></strong>
                </td>
                <td style="padding: 10px; text-align: center; vertical-align: top; border: 1px solid #ddd;">
                    <?php if ($item_weight > 0): ?>
                        <?php echo esc_html(number_format($item_weight, 2)); ?>
                    <?php else: ?>
                        <span style="color: #999;">-</span>
                    <?php endif; ?>
                </td>
                <td style="padding: 10px; text-align: center; vertical-align: top; border: 1px solid #ddd; background-color: #f8f9fa;">
                    <!-- Checkbox for storeman to tick off items -->
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr style="background-color: #ecf0f1; font-weight: bold;">
            <td style="padding: 10px; text-align: right; border: 1px solid #ddd;" colspan="2">
                <strong>TOTAL:</strong>
            </td>
            <td style="padding: 10px; text-align: center; border: 1px solid #ddd;">
                <strong style="font-size: 16px;"><?php echo esc_html($total_items); ?> items</strong>
            </td>
            <td style="padding: 10px; text-align: center; border: 1px solid #ddd;">
                <strong><?php echo esc_html(number_format($total_weight, 2)); ?> kg</strong>
            </td>
            <td style="padding: 10px; border: 1px solid #ddd;"></td>
        </tr>
    </tfoot>
</table>

<!-- Packing Instructions -->
<div style="background-color: #e8f4f8; border-left: 4px solid #3498db; padding: 15px; margin-top: 20px;">
    <h4 style="margin-top: 0; color: #2c3e50;">Packing Instructions:</h4>
    <ul style="margin: 5px 0; padding-left: 20px; line-height: 1.6;">
        <li>Check all items against this packing slip</li>
        <li>Verify quantities and SKUs match order</li>
        <li><strong>Pay special attention to customer notes above</strong></li>
        <li>Ensure fragile items are properly protected</li>
        <li>Include this packing slip in the delivery package</li>
        <li>Tick the OK column as you pack each item</li>
    </ul>
</div>

<!-- Packed By Section -->
<div style="margin-top: 30px; border-top: 1px solid #ddd; padding-top: 20px;">
    <table style="width: 100%;">
        <tr>
            <td style="width: 50%;">
                <strong>Packed By:</strong><br>
                <div style="border-bottom: 1px solid #000; width: 200px; margin-top: 10px;"></div>
                <small>(Name)</small><br><br>
                <div style="border-bottom: 1px solid #000; width: 200px; margin-top: 10px;"></div>
                <small>(Signature)</small><br><br>
                <div style="border-bottom: 1px solid #000; width: 200px; margin-top: 10px;"></div>
                <small>(Date & Time)</small>
            </td>
            <td style="width: 50%;">
                <strong>Checked By:</strong><br>
                <div style="border-bottom: 1px solid #000; width: 200px; margin-top: 10px;"></div>
                <small>(Name)</small><br><br>
                <div style="border-bottom: 1px solid #000; width: 200px; margin-top: 10px;"></div>
                <small>(Signature)</small><br><br>
                <div style="border-bottom: 1px solid #000; width: 200px; margin-top: 10px;"></div>
                <small>(Date & Time)</small>
            </td>
        </tr>
    </table>
</div>

<?php include AH_HO_INVOICING_PLUGIN_DIR . 'templates/shared/footer.php'; ?>

</body>
</html>
