<?php
/**
 * Invoice Template
 *
 * Generates branded PDF invoice with sequential numbering
 *
 * Available variables:
 * - $invoice_number (string) - Sequential invoice number
 * - $order (WC_Order) - WooCommerce order object
 * - $order_id (int) - Order ID
 * - $date (string) - Invoice date
 * - $due_date (string) - Payment due date
 * - $company_* (string) - Company information
 * - $bank_* (string) - Bank details
 */

if (!defined('ABSPATH')) exit;

// Load CSS
ob_start();
include AH_HO_INVOICING_PLUGIN_DIR . 'templates/invoice/style.css';
$css = ob_get_clean();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Invoice <?php echo esc_html($invoice_number); ?></title>
    <style><?php echo $css; ?></style>
</head>
<body>
    <div class="invoice-container">
        <?php
        // Set document title for header
        $document_title = 'INVOICE';
        $document_subtitle = '';
        $show_bank_details = true;
        include AH_HO_INVOICING_PLUGIN_DIR . 'templates/shared/header.php';
        ?>

        <!-- Invoice Info -->
        <table style="width: 100%; margin: 20px 0;">
            <tr>
                <td style="width: 50%;">
                    <strong style="font-size: 14px;">Invoice Number:</strong><br>
                    <span style="font-size: 18px; color: #27ae60; font-weight: bold;"><?php echo esc_html($invoice_number); ?></span>
                </td>
                <td style="width: 50%; text-align: right;">
                    <strong>Invoice Date:</strong> <?php echo esc_html($date); ?><br>
                    <strong>Due Date:</strong> <?php echo esc_html($due_date); ?>
                </td>
            </tr>
        </table>

        <!-- Bill To / Ship To -->
        <table style="width: 100%; margin: 20px 0; border: 1px solid #ddd;">
            <tr>
                <td style="width: 50%; padding: 15px; vertical-align: top; border-right: 1px solid #ddd;">
                    <strong style="color: #2c3e50; font-size: 12px;">BILL TO:</strong><br>
                    <div style="margin-top: 8px; line-height: 1.6;">
                        <strong><?php echo esc_html($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()); ?></strong><br>
                        <?php if ($order->get_billing_company()): ?>
                            <?php echo esc_html($order->get_billing_company()); ?><br>
                        <?php endif; ?>
                        <?php echo esc_html($order->get_billing_address_1()); ?><br>
                        <?php if ($order->get_billing_address_2()): ?>
                            <?php echo esc_html($order->get_billing_address_2()); ?><br>
                        <?php endif; ?>
                        <?php echo esc_html($order->get_billing_city() . ' ' . $order->get_billing_postcode()); ?><br>
                        <?php echo esc_html($order->get_billing_country()); ?><br>
                        <br>
                        <strong>Email:</strong> <?php echo esc_html($order->get_billing_email()); ?><br>
                        <strong>Phone:</strong> <?php echo esc_html($order->get_billing_phone()); ?>
                    </div>
                </td>
                <td style="width: 50%; padding: 15px; vertical-align: top;">
                    <strong style="color: #2c3e50; font-size: 12px;">SHIP TO:</strong><br>
                    <div style="margin-top: 8px; line-height: 1.6;">
                        <strong><?php echo esc_html($order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name()); ?></strong><br>
                        <?php if ($order->get_shipping_company()): ?>
                            <?php echo esc_html($order->get_shipping_company()); ?><br>
                        <?php endif; ?>
                        <?php echo esc_html($order->get_shipping_address_1()); ?><br>
                        <?php if ($order->get_shipping_address_2()): ?>
                            <?php echo esc_html($order->get_shipping_address_2()); ?><br>
                        <?php endif; ?>
                        <?php echo esc_html($order->get_shipping_city() . ' ' . $order->get_shipping_postcode()); ?><br>
                        <?php echo esc_html($order->get_shipping_country()); ?>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Order Items -->
        <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
            <thead>
                <tr style="background-color: #2c3e50; color: white;">
                    <th style="padding: 10px; text-align: left; border: 1px solid #2c3e50;">Item</th>
                    <th style="padding: 10px; text-align: center; border: 1px solid #2c3e50; width: 80px;">Qty</th>
                    <th style="padding: 10px; text-align: right; border: 1px solid #2c3e50; width: 100px;">Unit Price</th>
                    <th style="padding: 10px; text-align: right; border: 1px solid #2c3e50; width: 100px;">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($order->get_items() as $item_id => $item): ?>
                    <?php
                    $product = $item->get_product();
                    $item_total = $order->get_line_total($item, true); // Including tax
                    ?>
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd;">
                            <strong><?php echo esc_html($item->get_name()); ?></strong>
                            <?php if ($product && $product->get_sku()): ?>
                                <br><small style="color: #7f8c8d;">SKU: <?php echo esc_html($product->get_sku()); ?></small>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 10px; text-align: center; border: 1px solid #ddd;">
                            <?php echo esc_html($item->get_quantity()); ?>
                        </td>
                        <td style="padding: 10px; text-align: right; border: 1px solid #ddd;">
                            <?php echo wc_price($order->get_item_subtotal($item, true)); ?>
                        </td>
                        <td style="padding: 10px; text-align: right; border: 1px solid #ddd; font-weight: bold;">
                            <?php echo wc_price($item_total); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Totals -->
        <table style="width: 100%; margin: 20px 0;">
            <tr>
                <td style="width: 60%;"></td>
                <td style="width: 40%;">
                    <table style="width: 100%;">
                        <tr>
                            <td style="padding: 5px; text-align: right;"><strong>Subtotal:</strong></td>
                            <td style="padding: 5px; text-align: right; width: 100px;"><?php echo wc_price($order->get_subtotal()); ?></td>
                        </tr>

                        <?php if ($order->get_total_shipping() > 0): ?>
                        <tr>
                            <td style="padding: 5px; text-align: right;"><strong>Shipping:</strong></td>
                            <td style="padding: 5px; text-align: right;"><?php echo wc_price($order->get_total_shipping()); ?></td>
                        </tr>
                        <?php endif; ?>

                        <?php if ($order->get_total_discount() > 0): ?>
                        <tr>
                            <td style="padding: 5px; text-align: right; color: #e74c3c;"><strong>Discount:</strong></td>
                            <td style="padding: 5px; text-align: right; color: #e74c3c;">-<?php echo wc_price($order->get_total_discount()); ?></td>
                        </tr>
                        <?php endif; ?>

                        <?php if ($order->get_total_tax() > 0): ?>
                        <tr>
                            <td style="padding: 5px; text-align: right;"><strong>GST (9%):</strong></td>
                            <td style="padding: 5px; text-align: right;"><?php echo wc_price($order->get_total_tax()); ?></td>
                        </tr>
                        <?php endif; ?>

                        <tr style="border-top: 2px solid #2c3e50;">
                            <td style="padding: 10px; text-align: right; font-size: 16px;"><strong>TOTAL:</strong></td>
                            <td style="padding: 10px; text-align: right; font-size: 18px; font-weight: bold; color: #27ae60;">
                                <?php echo wc_price($order->get_total()); ?>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- Payment Info -->
        <table style="width: 100%; margin: 20px 0; background-color: #ecf0f1; padding: 15px; border-left: 4px solid #3498db;">
            <tr>
                <td style="width: 50%; vertical-align: top; padding: 10px;">
                    <strong>Payment Method:</strong><br>
                    <?php echo esc_html($order->get_payment_method_title()); ?>
                </td>
                <td style="width: 50%; text-align: right; vertical-align: top; padding: 10px;">
                    <strong>Payment Status:</strong><br>
                    <?php
                    $status = $order->get_status();
                    $status_color = ($status === 'completed' || $order->is_paid()) ? '#27ae60' : '#e74c3c';
                    $status_text = $order->is_paid() ? 'PAID' : strtoupper($status);
                    ?>
                    <span style="color: <?php echo $status_color; ?>; font-weight: bold; font-size: 14px;">
                        <?php echo esc_html($status_text); ?>
                    </span>
                </td>
            </tr>
        </table>

        <!-- Customer Notes (if any) -->
        <?php if ($order->get_customer_note()): ?>
        <table style="width: 100%; margin: 20px 0; background-color: #fff9e6; border-left: 4px solid #f39c12; padding: 10px;">
            <tr>
                <td>
                    <strong>📝 Customer Note:</strong><br>
                    <?php echo nl2br(esc_html($order->get_customer_note())); ?>
                </td>
            </tr>
        </table>
        <?php endif; ?>

        <!-- Terms & Conditions -->
        <table style="width: 100%; margin: 30px 0; font-size: 10px; color: #7f8c8d;">
            <tr>
                <td>
                    <strong>Payment Terms:</strong> Payment is due within 30 days from invoice date. Late payments may incur interest charges.<br>
                    <strong>Returns Policy:</strong> Fresh produce must be inspected upon delivery. Claims for damaged goods must be made within 24 hours.
                </td>
            </tr>
        </table>

        <?php include AH_HO_INVOICING_PLUGIN_DIR . 'templates/shared/footer.php'; ?>
    </div>
</body>
</html>
