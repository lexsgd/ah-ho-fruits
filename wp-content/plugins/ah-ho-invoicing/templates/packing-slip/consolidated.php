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
    ℹ️ Orders sorted by: <strong>Delivery Date</strong> (primary) → <strong>Postal Code</strong> (secondary)
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
                📅 Delivery Date: <?php echo esc_html(date('l, d F Y', strtotime($delivery_date))); ?>
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
                    <th style="padding: 5px; text-align: center; width: 30px; border: 1px solid #ddd;">✓</th>
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
                    $item_weight = $product ? ($product->get_weight() * $quantity) : 0;
                    $order_weight += $item_weight;
                ?>
                    <tr style="font-size: 11px;">
                        <td style="padding: 5px; border: 1px solid #ddd;">
                            <strong><?php echo esc_html($item->get_name()); ?></strong>
                            <?php
                            // Display item meta (variations)
                            $item_data = $item->get_formatted_meta_data();
                            if (!empty($item_data)):
                            ?>
                                <br><small style="color: #666;">
                                    <?php foreach ($item_data as $meta): ?>
                                        <?php echo esc_html($meta->display_key); ?>: <?php echo wp_kses_post($meta->display_value); ?>
                                    <?php endforeach; ?>
                                </small>
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
    <h3 style="color: #2c3e50;">📊 Packing Summary</h3>
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
