<?php
/**
 * Consolidated Packing Slip Template (Multiple Orders)
 *
 * Internal document for storeman/warehouse staff.
 * Optimized for paper efficiency and readability.
 * Sorted by delivery date FIRST, then postal code.
 *
 * @package AhHoInvoicing
 * @since 1.1.0
 * @modified 2026-02-08 - compact layout, removed unnecessary fields
 */

if (!defined('ABSPATH')) {
    exit;
}

// Calculate totals
$total_orders = count($orders_data);
?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        @page { size: A4; margin: 5mm 5mm 5mm 5mm; }
        * { padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
            line-height: 1.3;
            color: #000;
            margin: 0;
        }
        h1, h2, h3, h4 { margin: 0; }
        table { border-collapse: collapse; width: 100%; }
        strong { font-weight: bold; }
        .page-break { page-break-after: always; }
        .no-break { page-break-inside: avoid; }
    </style>
</head>
<body>

<!-- ===== HEADER ===== -->
<table style="width: 100%; border-bottom: 2px solid #000; margin-bottom: 6px; padding-bottom: 4px;">
    <tr>
        <td style="vertical-align: bottom;">
            <div style="font-size: 14px; font-weight: bold; letter-spacing: 0.5px; line-height: 1.1;">AH HO FRUIT TRADING CO</div>
            <div style="font-size: 12px; font-weight: bold; margin-top: 2px;">CONSOLIDATED PACKING SLIP</div>
        </td>
        <td style="text-align: right; vertical-align: bottom;">
            <div style="font-size: 11px;"><strong>Date:</strong> <?php echo esc_html(date('d/m/Y')); ?></div>
            <div style="font-size: 10px; color: #333;">Sorted by Date, then Postal Code</div>
        </td>
    </tr>
</table>

<!-- ===== SUMMARY ===== -->
<div style="background-color: #2c3e50; color: white; padding: 6px 10px; margin-bottom: 8px; font-size: 13px; font-weight: bold;">
    <?php echo esc_html($total_orders); ?> Orders
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
            echo '</div>';
        endif;
        $current_date = $delivery_date;
        ?>
        <div style="page-break-before: <?php echo $date_counter > 1 ? 'always' : 'avoid'; ?>;">
            <div style="background-color: #2c3e50; color: white; padding: 5px 8px; margin-bottom: 6px; font-size: 11px; font-weight: bold;">
                <?php echo esc_html(date('l, d M Y', strtotime($delivery_date))); ?>
            </div>
        <?php
        $date_counter++;
    endif;
    ?>

    <!-- Order Card -->
    <div style="border: 1.5px solid #000; margin-bottom: 6px; padding: 6px; page-break-inside: avoid;">
        <!-- Order Header -->
        <table style="width: 100%; margin-bottom: 4px;">
            <tr>
                <td style="width: 55%; vertical-align: top;">
                    <span style="font-size: 13px; font-weight: bold;">#<?php echo esc_html($order->get_order_number()); ?></span>
                    <span style="font-size: 12px; font-weight: bold; color: #c00; margin-left: 8px;"><?php echo esc_html($postal_code); ?></span>
                </td>
                <td style="width: 45%; text-align: right; vertical-align: top; font-size: 10px;">
                    <strong><?php echo esc_html(trim($order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name())); ?></strong>
                    <?php if ($order->get_shipping_company()): ?>
                        | <?php echo esc_html($order->get_shipping_company()); ?>
                    <?php endif; ?>
                    <br>
                    <?php echo esc_html($order->get_shipping_address_1()); ?>
                    <?php if ($order->get_shipping_address_2()): ?>
                        <?php echo esc_html($order->get_shipping_address_2()); ?>
                    <?php endif; ?>
                    S<?php echo esc_html($postal_code); ?>
                    <?php
                    $phone = $order->get_shipping_phone();
                    if (empty($phone)) { $phone = $order->get_billing_phone(); }
                    if (!empty($phone)):
                    ?>
                        &nbsp;|&nbsp;Tel: <?php echo esc_html($phone); ?>
                    <?php endif; ?>
                </td>
            </tr>
        </table>

        <?php
        // Customer notes (highlighted if critical keywords)
        echo AH_HO_Packing_Slip::format_customer_notes($order);
        ?>

        <!-- Items -->
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background-color: #555; color: white; font-size: 9px;">
                    <th style="padding: 3px 4px; text-align: left; border: 1px solid #999;">Item</th>
                    <th style="padding: 3px 4px; text-align: center; width: 35px; border: 1px solid #999;">Qty</th>
                    <th style="padding: 3px 4px; text-align: center; width: 28px; border: 1px solid #999;">OK</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $order_items = 0;
                foreach ($order->get_items() as $item_id => $item):
                    $product = $item->get_product();
                    $quantity = $item->get_quantity();
                    $order_items += $quantity;
                ?>
                    <tr style="font-size: 10px;">
                        <td style="padding: 3px 4px; border: 1px solid #ccc;">
                            <strong><?php echo esc_html($item->get_name()); ?></strong>
                            <?php
                            // Extract product notes and gift data
                            $special_request_keys = ['Special Requests', __('Special Requests', 'ah-ho-fruits'), 'special_requests', '_special_requests'];
                            $gift_keys = ['Gift', __('Gift', 'ah-ho-fruits'), 'gift', '_gift'];
                            $gift_message_keys = ['Gift Message', __('Gift Message', 'ah-ho-fruits'), 'gift_message', '_gift_message'];

                            $product_notes = '';
                            $is_gift = '';
                            $gift_message = '';

                            foreach ($special_request_keys as $key) {
                                $value = wc_get_order_item_meta($item_id, $key, true);
                                if (!empty($value)) { $product_notes = $value; break; }
                            }
                            foreach ($gift_keys as $key) {
                                $value = wc_get_order_item_meta($item_id, $key, true);
                                if (!empty($value)) { $is_gift = $value; break; }
                            }
                            foreach ($gift_message_keys as $key) {
                                $value = wc_get_order_item_meta($item_id, $key, true);
                                if (!empty($value)) { $gift_message = $value; break; }
                            }

                            $skip_keys = array_merge($special_request_keys, $gift_keys, $gift_message_keys);

                            // Display item meta (variations)
                            $item_data = $item->get_formatted_meta_data();
                            $meta_parts = array();
                            if (!empty($item_data)):
                                foreach ($item_data as $meta):
                                    $should_skip = false;
                                    foreach ($skip_keys as $skip_key) {
                                        if (strcasecmp($meta->display_key, $skip_key) === 0) { $should_skip = true; break; }
                                    }
                                    if ($should_skip) continue;

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

                                    $meta_parts[] = $meta->display_key . ': ' . strip_tags($meta->display_value);
                                endforeach;
                            endif;
                            if (!empty($meta_parts)):
                            ?>
                                <br><small style="color: #555;"><?php echo esc_html(implode(', ', $meta_parts)); ?></small>
                            <?php endif; ?>

                            <?php if (!empty($product_notes)): ?>
                                <div style="margin-top: 3px; padding: 4px 6px; background-color: #e8f5e9; border: 1.5px solid #2E7D32; font-size: 9px;">
                                    <strong style="color: #2E7D32;">** REQUESTS:</strong>
                                    <span style="font-weight: bold;"><?php echo nl2br(esc_html($product_notes)); ?></span>
                                </div>
                            <?php endif; ?>

                            <?php
                            $is_gift_order = (strtolower($is_gift) === 'yes' || $is_gift === '1' || $is_gift === 'true' || !empty($gift_message));
                            if ($is_gift_order):
                            ?>
                                <div style="margin-top: 3px; padding: 4px 6px; background-color: #fff8e1; border: 1.5px solid #FF6F00; font-size: 9px;">
                                    <strong style="color: #FF6F00;">*** GIFT<?php if (!empty($gift_message)): ?> — "<?php echo esc_html($gift_message); ?>"<?php endif; ?></strong>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 3px 4px; text-align: center; border: 1px solid #ccc; font-weight: bold; font-size: 12px;">
                            <?php echo esc_html($quantity); ?>
                        </td>
                        <td style="padding: 3px 4px; text-align: center; border: 1px solid #ccc; background-color: #f5f5f5;">
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background-color: #eee; font-size: 10px; font-weight: bold;">
                    <td style="padding: 3px 4px; text-align: right; border: 1px solid #ccc;">Total:</td>
                    <td style="padding: 3px 4px; text-align: center; border: 1px solid #ccc;"><?php echo esc_html($order_items); ?></td>
                    <td style="padding: 3px 4px; border: 1px solid #ccc;"></td>
                </tr>
            </tfoot>
        </table>
    </div>

<?php
endforeach;

// Close last date group
echo '</div>';
?>

</body>
</html>
