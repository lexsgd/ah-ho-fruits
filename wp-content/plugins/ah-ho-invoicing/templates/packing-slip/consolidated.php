<?php
/**
 * Consolidated Packing Slip Template (Multiple Orders)
 *
 * CRITICAL FEATURE: Multiple orders sorted by delivery date FIRST, then postal code
 * Used by storeman to prepare deliveries grouped by route/delivery schedule
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
$document_title = 'CONSOLIDATED PACKING SLIP';

// Calculate totals
$total_orders = count($orders_data);
$total_items = 0;
$total_weight = 0;
foreach ($orders_data as $data) {
    $order = $data['order'];
    foreach ($order->get_items() as $item) {
        $total_items += $item->get_quantity();
    }
    $total_weight += AH_HO_Packing_Slip::get_order_weight($order);
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style><?php include AH_HO_INVOICING_PLUGIN_DIR . 'templates/packing-slip/style.css'; ?></style>
</head>
<body>

<?php include AH_HO_INVOICING_PLUGIN_DIR . 'templates/shared/header.php'; ?>

<!-- Summary -->
<div style="background-color: #3498db; color: white; padding: 15px; margin-bottom: 20px;">
    <table style="width: 100%; color: white;">
        <tr>
            <td style="width: 33%; text-align: center;">
                <h2 style="margin: 0; font-size: 32px;"><?php echo esc_html($total_orders); ?></h2>
                <div>Total Orders</div>
            </td>
            <td style="width: 33%; text-align: center;">
                <h2 style="margin: 0; font-size: 32px;"><?php echo esc_html($total_items); ?></h2>
                <div>Total Items</div>
            </td>
            <td style="width: 33%; text-align: center;">
                <h2 style="margin: 0; font-size: 32px;"><?php echo esc_html(number_format($total_weight, 1)); ?> kg</h2>
                <div>Total Weight</div>
            </td>
        </tr>
    </table>
</div>

<!-- Sorting Information -->
<div style="background-color: #f39c12; color: white; padding: 10px; margin-bottom: 20px; font-weight: bold;">
    Orders sorted by: <strong>Date</strong> (primary) then <strong>Postal Code</strong> (secondary)
</div>

<?php
// Group by delivery date for visual separation
$current_date = null;
$date_counter = 1;

foreach ($orders_data as $index => $data):
    $order = $data['order'];
    $delivery_date = $data['delivery_date'];
    $postal_code = $data['postal_code'];

    // Show date header when date changes
    if ($delivery_date !== $current_date):
        if ($current_date !== null):
            // Close previous date group
            echo '</div>';
        endif;
        $current_date = $delivery_date;
        ?>
        <div style="page-break-before: <?php echo $date_counter > 1 ? 'always' : 'avoid'; ?>;">
            <h2 style="background-color: #2c3e50; color: white; padding: 15px; margin-top: 0; margin-bottom: 20px;">
                Order Date: <?php echo esc_html(date('l, d F Y', strtotime($delivery_date))); ?>
            </h2>
        <?php
        $date_counter++;
    endif;
    ?>

    <!-- Order Card -->
    <div style="border: 2px solid #2c3e50; margin-bottom: 20px; padding: 15px; page-break-inside: avoid;">
        <!-- Order Header -->
        <table style="width: 100%; background-color: #ecf0f1; padding: 10px; margin-bottom: 10px;">
            <tr>
                <td style="width: 50%; vertical-align: top;">
                    <h3 style="margin: 0; color: #2c3e50; font-size: 18px;">
                        Order #<?php echo esc_html($order->get_order_number()); ?>
                    </h3>
                    <div style="font-size: 12px; margin-top: 5px;">
                        <strong>Postal Code:</strong> <span style="font-size: 16px; font-weight: bold; color: #e74c3c;"><?php echo esc_html($postal_code); ?></span>
                    </div>
                </td>
                <td style="width: 50%; text-align: right; vertical-align: top;">
                    <strong style="font-size: 14px;"><?php echo esc_html($order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name()); ?></strong><br>
                    <?php if ($order->get_shipping_company()): ?>
                        <?php echo esc_html($order->get_shipping_company()); ?><br>
                    <?php endif; ?>
                    <div style="font-size: 11px;">
                        <?php echo esc_html($order->get_shipping_address_1()); ?><br>
                        <?php if ($order->get_shipping_address_2()): ?>
                            <?php echo esc_html($order->get_shipping_address_2()); ?><br>
                        <?php endif; ?>
                        Singapore <?php echo esc_html($postal_code); ?>
                    </div>
                    <div style="margin-top: 5px; font-size: 12px;">
                        <strong>Tel:</strong> <?php echo esc_html($order->get_billing_phone()); ?>
                    </div>
                </td>
            </tr>
        </table>

        <?php
        // Display customer notes if present (with highlighting)
        echo AH_HO_Packing_Slip::format_customer_notes($order);
        ?>

        <!-- Items -->
        <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
            <thead>
                <tr style="background-color: #95a5a6; color: white; font-size: 11px;">
                    <th style="padding: 5px; text-align: left; border: 1px solid #ddd;">Item</th>
                    <th style="padding: 5px; text-align: center; width: 60px; border: 1px solid #ddd;">SKU</th>
                    <th style="padding: 5px; text-align: center; width: 40px; border: 1px solid #ddd;">Qty</th>
                    <th style="padding: 5px; text-align: center; width: 60px; border: 1px solid #ddd;">Weight</th>
                    <th style="padding: 5px; text-align: center; width: 30px; border: 1px solid #ddd;">OK</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $order_items = 0;
                $order_weight = 0;
                foreach ($order->get_items() as $item_id => $item):
                    $product = $item->get_product();
                    $quantity = $item->get_quantity();
                    $order_items += $quantity;
                    $item_weight = $product ? ((float) $product->get_weight() * $quantity) : 0;
                    $order_weight += $item_weight;
                ?>
                    <tr style="font-size: 11px;">
                        <td style="padding: 5px; border: 1px solid #ddd;">
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

                            // Display item meta (variations) - excluding custom addons
                            $item_data = $item->get_formatted_meta_data();
                            $has_other_meta = false;
                            if (!empty($item_data)):
                                foreach ($item_data as $meta):
                                    // Skip our custom addon fields
                                    $should_skip = false;
                                    foreach ($skip_keys as $skip_key) {
                                        if (strcasecmp($meta->display_key, $skip_key) === 0) {
                                            $should_skip = true;
                                            break;
                                        }
                                    }
                                    if ($should_skip) continue;

                                    // Fallback capture if not found via direct meta
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
                                    echo esc_html($meta->display_key) . ': ' . wp_kses_post($meta->display_value) . ' ';
                                endforeach;
                                if ($has_other_meta) {
                                    echo '</small>';
                                }
                            endif;
                            ?>

                            <?php
                            // Display Customer Requests (GREEN box - prominent for packer)
                            if (!empty($product_notes)):
                            ?>
                                <div style="margin-top: 6px; padding: 8px; background-color: #e8f5e9; border: 2px solid #2E7D32; border-radius: 3px; font-size: 10px;">
                                    <strong style="color: #2E7D32;">** Customer Requests (Preferences/Allergies):</strong><br>
                                    <span style="font-weight: bold; color: #000;"><?php echo nl2br(esc_html($product_notes)); ?></span>
                                </div>
                            <?php endif; ?>

                            <?php
                            // Display Gift Section (ORANGE box - prominent for packer)
                            $is_gift_order = (strtolower($is_gift) === 'yes' || $is_gift === '1' || $is_gift === 'true' || !empty($gift_message));
                            if ($is_gift_order):
                            ?>
                                <div style="margin-top: 6px; padding: 8px; background-color: #fff8e1; border: 2px solid #FF6F00; border-radius: 3px; font-size: 10px;">
                                    <strong style="color: #FF6F00;">*** GIFT - Include Gift Card! ***</strong>
                                    <?php if (!empty($gift_message)): ?>
                                        <br><span style="font-style: italic; color: #000; font-weight: bold;">"<?php echo nl2br(esc_html($gift_message)); ?>"</span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 5px; text-align: center; border: 1px solid #ddd;">
                            <?php if ($product && $product->get_sku()): ?>
                                <?php echo esc_html($product->get_sku()); ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td style="padding: 5px; text-align: center; border: 1px solid #ddd;">
                            <strong><?php echo esc_html($quantity); ?></strong>
                        </td>
                        <td style="padding: 5px; text-align: center; border: 1px solid #ddd;">
                            <?php if ($item_weight > 0): ?>
                                <?php echo esc_html(number_format($item_weight, 1)); ?> kg
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td style="padding: 5px; text-align: center; border: 1px solid #ddd; background-color: #f8f9fa;">
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background-color: #ecf0f1; font-weight: bold; font-size: 11px;">
                    <td style="padding: 5px; text-align: right; border: 1px solid #ddd;" colspan="2">
                        Order Total:
                    </td>
                    <td style="padding: 5px; text-align: center; border: 1px solid #ddd;">
                        <?php echo esc_html($order_items); ?>
                    </td>
                    <td style="padding: 5px; text-align: center; border: 1px solid #ddd;">
                        <?php echo esc_html(number_format($order_weight, 1)); ?> kg
                    </td>
                    <td style="padding: 5px; border: 1px solid #ddd;"></td>
                </tr>
            </tfoot>
        </table>
    </div>

<?php
endforeach;

// Close last date group
echo '</div>';
?>

<!-- Summary Footer -->
<div style="page-break-before: avoid; margin-top: 30px; border-top: 2px solid #2c3e50; padding-top: 20px;">
    <h3 style="color: #2c3e50;">PACKING SUMMARY</h3>
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background-color: #2c3e50; color: white;">
                <th style="padding: 10px; text-align: left; border: 1px solid #ddd;">Metric</th>
                <th style="padding: 10px; text-align: center; border: 1px solid #ddd;">Total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="padding: 10px; border: 1px solid #ddd;"><strong>Total Orders</strong></td>
                <td style="padding: 10px; text-align: center; border: 1px solid #ddd; font-size: 16px; font-weight: bold;">
                    <?php echo esc_html($total_orders); ?>
                </td>
            </tr>
            <tr>
                <td style="padding: 10px; border: 1px solid #ddd;"><strong>Total Items to Pack</strong></td>
                <td style="padding: 10px; text-align: center; border: 1px solid #ddd; font-size: 16px; font-weight: bold;">
                    <?php echo esc_html($total_items); ?>
                </td>
            </tr>
            <tr>
                <td style="padding: 10px; border: 1px solid #ddd;"><strong>Total Weight</strong></td>
                <td style="padding: 10px; text-align: center; border: 1px solid #ddd; font-size: 16px; font-weight: bold;">
                    <?php echo esc_html(number_format($total_weight, 2)); ?> kg
                </td>
            </tr>
        </tbody>
    </table>
</div>

<!-- Signatures -->
<div style="margin-top: 30px; border-top: 1px solid #ddd; padding-top: 20px;">
    <table style="width: 100%;">
        <tr>
            <td style="width: 50%;">
                <strong>Packed By:</strong><br>
                <div style="border-bottom: 1px solid #000; width: 200px; margin-top: 10px;"></div>
                <small>(Name & Signature)</small><br><br>
                <div style="border-bottom: 1px solid #000; width: 200px; margin-top: 10px;"></div>
                <small>(Date & Time)</small>
            </td>
            <td style="width: 50%;">
                <strong>Checked By:</strong><br>
                <div style="border-bottom: 1px solid #000; width: 200px; margin-top: 10px;"></div>
                <small>(Name & Signature)</small><br><br>
                <div style="border-bottom: 1px solid #000; width: 200px; margin-top: 10px;"></div>
                <small>(Date & Time)</small>
            </td>
        </tr>
    </table>
</div>

<?php include AH_HO_INVOICING_PLUGIN_DIR . 'templates/shared/footer.php'; ?>

</body>
</html>
