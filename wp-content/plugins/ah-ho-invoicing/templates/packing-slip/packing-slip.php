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
                $delivery_date = get_post_meta($order->get_id(), '_delivery_date', true);
                echo esc_html($delivery_date ? date('d M Y', strtotime($delivery_date)) : 'Not specified');
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
            <th style="padding: 10px; text-align: center; width: 60px; border: 1px solid #ddd;">✓</th>
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

            $item_weight = $product ? ($product->get_weight() * $quantity) : 0;
            $total_weight += $item_weight;
        ?>
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px; vertical-align: top; border: 1px solid #ddd;">
                    <strong><?php echo esc_html($item->get_name()); ?></strong>
                    <?php
                    // Display item meta (variations, custom options)
                    $item_data = $item->get_formatted_meta_data();
                    if (!empty($item_data)):
                    ?>
                        <br><small style="color: #666;">
                            <?php foreach ($item_data as $meta): ?>
                                <?php echo esc_html($meta->display_key); ?>: <?php echo wp_kses_post($meta->display_value); ?><br>
                            <?php endforeach; ?>
                        </small>
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
    <h4 style="margin-top: 0; color: #2c3e50;">📦 Packing Instructions:</h4>
    <ul style="margin: 5px 0; padding-left: 20px; line-height: 1.6;">
        <li>Check all items against this packing slip</li>
        <li>Verify quantities and SKUs match order</li>
        <li><strong>Pay special attention to customer notes above</strong></li>
        <li>Ensure fragile items are properly protected</li>
        <li>Include this packing slip in the delivery package</li>
        <li>Tick the checkbox (✓) column as you pack each item</li>
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
