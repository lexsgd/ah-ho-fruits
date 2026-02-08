<?php
/**
 * Invoice Template
 *
 * Matches delivery order layout with added pricing columns,
 * totals breakdown, and payment status indicator.
 *
 * Available variables (from class-invoice.php extract):
 * - $invoice_number (string) - Sequential invoice number (AHF-YYYY-NNNNN)
 * - $order (WC_Order) - WooCommerce order object
 * - $order_id (int) - Order ID
 * - $date (string) - Invoice date
 * - $due_date (string) - Payment due date
 * - $company_name, $company_address, $company_phone, $company_email
 * - $company_uen, $company_gst
 * - $bank_name, $bank_account
 * - $company_logo (string) - Logo URL from class
 *
 * @package AhHoInvoicing
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Logo: prefer settings URL, fallback to local file (Dompdf needs file path)
$logo_url  = get_option('ah_ho_company_logo_url', '');
$logo_file = AH_HO_INVOICING_PLUGIN_DIR . 'assets/images/ah-ho-logo.png';
if (empty($logo_url) && file_exists($logo_file) && filesize($logo_file) > 200) {
    $logo_url = $logo_file;
}

// Invoice date formatted for the info table
$invoice_date = $order->get_date_created()->format('j/n/Y');

// Payment terms
$payment_method = $order->get_payment_method();
$terms = ($payment_method === 'cod') ? 'C.O.D.' : $order->get_payment_method_title();

// PO Number from order meta
$po_number = $order->get_meta('_po_number', true);
if (empty($po_number)) {
    $po_number = $order->get_meta('po_number', true);
}

// Billing info
$bill_name    = trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name());
$bill_company = $order->get_billing_company();
$bill_addr1   = $order->get_billing_address_1();
$bill_addr2   = $order->get_billing_address_2();
$bill_city    = $order->get_billing_city();
$bill_postal  = $order->get_billing_postcode();
$bill_phone   = $order->get_billing_phone();
$bill_email   = $order->get_billing_email();

// Payment status
$is_paid     = $order->is_paid();
$order_total = $order->get_total();
?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        @page {
            size: A4;
        }
        * { padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #000;
            margin: 2mm 3mm 2mm 3mm;
        }
        h1, h2, h3, h4 { margin: 0 0 1px 0; }
        table { border-collapse: collapse; width: 100%; }
        strong { font-weight: bold; }
        .page-break { page-break-after: always; }
        .no-break { page-break-inside: avoid; }
    </style>
</head>
<body>

<!-- ===== HEADER: Logo + Company Name + Registration ===== -->
<table class="header-table" style="width: 98%; border-bottom: 2px solid #000; margin-bottom: 3px; padding-bottom: 2px;">
    <tr>
        <td style="width: 15%; vertical-align: middle;">
            <?php if (!empty($logo_url)): ?>
                <img src="<?php echo esc_attr($logo_url); ?>" alt="<?php echo esc_attr($company_name); ?>" style="max-width: 60px; max-height: 60px;" />
            <?php endif; ?>
        </td>
        <td style="width: 52%; vertical-align: middle; text-align: left; padding-left: 5px;">
            <div style="font-size: 18px; font-weight: bold; letter-spacing: 0.5px; line-height: 1.1;">
                <?php echo esc_html(strtoupper($company_name)); ?>
            </div>
            <div style="font-size: 9px; color: #333; margin-top: 3px; line-height: 1.4;">
                <?php echo nl2br(esc_html($company_address)); ?>
                &nbsp;&nbsp;&nbsp;&nbsp;Phone: <?php echo esc_html($company_phone); ?>&nbsp;&nbsp;&nbsp;&nbsp;WhatsApp: 80138128
            </div>
        </td>
        <td style="width: 33%; vertical-align: middle; text-align: right; font-size: 9px; line-height: 1.6; padding-right: 2px;">
            <strong>UEN No.</strong> &nbsp; <?php echo esc_html($company_uen); ?><br>
            <strong>GST Reg No:</strong> &nbsp; <?php echo esc_html($company_gst); ?>
        </td>
    </tr>
</table>

<!-- ===== BILL TO / INVOICE INFO ===== -->
<table style="width: 100%; margin-bottom: 8px;" cellspacing="0" cellpadding="0">
    <tr>
        <!-- Bill To -->
        <td style="width: 55%; vertical-align: top; padding-right: 10px;">
            <div style="font-size: 10px; font-weight: bold; margin-bottom: 1px;">Bill To:</div>
            <div style="font-size: 10px; line-height: 1.5;">
                <?php echo esc_html($bill_name); ?><br>
                <?php if ($bill_company): ?>
                    <?php echo esc_html($bill_company); ?><br>
                <?php endif; ?>
                <?php if ($bill_addr1): ?>
                    <?php echo esc_html($bill_addr1); ?><br>
                <?php endif; ?>
                <?php if ($bill_addr2): ?>
                    <?php echo esc_html($bill_addr2); ?><br>
                <?php endif; ?>
                <?php if ($bill_city || $bill_postal): ?>
                    <?php echo esc_html(trim($bill_city . ' ' . $bill_postal)); ?><br>
                <?php endif; ?>
                <?php if (!empty($bill_phone)): ?>
                    Tel: <?php echo esc_html($bill_phone); ?><br>
                <?php endif; ?>
                <?php if (!empty($bill_email)): ?>
                    Email: <?php echo esc_html($bill_email); ?>
                <?php endif; ?>
            </div>
        </td>

        <!-- Invoice title + details -->
        <td style="width: 45%; vertical-align: top;">
            <div style="font-size: 16px; font-weight: bold; margin-bottom: 2px;">Invoice</div>
            <table style="width: 100%; border-collapse: collapse; font-size: 10px;">
                <tr>
                    <td style="border: 1px solid #000; padding: 2px 4px; font-weight: bold; width: 40%;">Invoice No</td>
                    <td style="border: 1px solid #000; padding: 2px 4px;"><?php echo esc_html($order->get_order_number()); ?></td>
                </tr>
                <tr>
                    <td style="border: 1px solid #000; padding: 2px 4px; font-weight: bold;">Date</td>
                    <td style="border: 1px solid #000; padding: 2px 4px;"><?php echo esc_html($invoice_date); ?></td>
                </tr>
                <tr>
                    <td style="border: 1px solid #000; padding: 2px 4px; font-weight: bold;">PO No</td>
                    <td style="border: 1px solid #000; padding: 2px 4px;"><?php echo !empty($po_number) ? esc_html($po_number) : '&nbsp;'; ?></td>
                </tr>
                <tr>
                    <td style="border: 1px solid #000; padding: 2px 4px; font-weight: bold;">Terms</td>
                    <td style="border: 1px solid #000; padding: 2px 4px;"><?php echo esc_html($terms); ?></td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<!-- ===== ITEMS TABLE ===== -->
<table style="width: 100%; border-collapse: collapse; margin-bottom: 8px;" cellspacing="0" cellpadding="0">
    <thead>
        <tr>
            <th style="border: 1px solid #000; padding: 2px 4px; text-align: center; width: 40px; font-size: 10px; font-weight: bold;">Qty</th>
            <th style="border: 1px solid #000; padding: 2px 4px; text-align: left; font-size: 10px; font-weight: bold;">Description</th>
            <th style="border: 1px solid #000; padding: 2px 4px; text-align: right; width: 75px; font-size: 10px; font-weight: bold;">Unit Price</th>
            <th style="border: 1px solid #000; padding: 2px 4px; text-align: right; width: 75px; font-size: 10px; font-weight: bold;">Amount</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $row_count = 0;
        foreach ($order->get_items() as $item_id => $item):
            $row_count++;

            // Build description with meta/variations
            $description = $item->get_name();

            // Add item meta (variations) inline
            $item_data = $item->get_formatted_meta_data();
            $meta_parts = array();
            if (!empty($item_data)) {
                foreach ($item_data as $meta) {
                    if (in_array($meta->display_key, [
                        __('Special Requests', 'ah-ho-fruits'),
                        __('Gift', 'ah-ho-fruits'),
                        __('Gift Message', 'ah-ho-fruits')
                    ])) {
                        continue;
                    }
                    $meta_parts[] = $meta->display_key . ': ' . strip_tags($meta->display_value);
                }
            }
            if (!empty($meta_parts)) {
                $description .= ' (' . implode(', ', $meta_parts) . ')';
            }

            // Special requests
            $product_notes = wc_get_order_item_meta($item_id, __('Special Requests', 'ah-ho-fruits'), true);
            $is_gift       = wc_get_order_item_meta($item_id, __('Gift', 'ah-ho-fruits'), true);
            $gift_message  = wc_get_order_item_meta($item_id, __('Gift Message', 'ah-ho-fruits'), true);

            // Pricing
            $unit_price = $order->get_item_subtotal($item, false, true); // excl tax, rounded
            $line_total = $order->get_line_total($item, false, true);     // excl tax, rounded
        ?>
            <tr>
                <td style="border: 1px solid #000; padding: 2px 4px; text-align: center; font-size: 10px; font-weight: bold;">
                    <?php echo esc_html($item->get_quantity()); ?>
                </td>
                <td style="border: 1px solid #000; padding: 2px 4px; font-size: 10px;">
                    <?php echo esc_html($description); ?>
                    <?php if (!empty($product_notes)): ?>
                        <br><span style="font-size: 10px; color: #333;">** <?php echo esc_html($product_notes); ?></span>
                    <?php endif; ?>
                    <?php if ($is_gift === __('Yes', 'ah-ho-fruits')): ?>
                        <br><span style="font-size: 10px; color: #333;">*** GIFT<?php if (!empty($gift_message)): ?> - "<?php echo esc_html($gift_message); ?>"<?php endif; ?></span>
                    <?php endif; ?>
                </td>
                <td style="border: 1px solid #000; padding: 2px 4px; text-align: right; font-size: 10px;">
                    $<?php echo esc_html(number_format($unit_price, 2)); ?>
                </td>
                <td style="border: 1px solid #000; padding: 2px 4px; text-align: right; font-size: 10px;">
                    $<?php echo esc_html(number_format($line_total, 2)); ?>
                </td>
            </tr>
        <?php endforeach; ?>

        <?php
        // Add empty rows to fill out the table
        $min_rows = 10;
        for ($i = $row_count; $i < $min_rows; $i++):
        ?>
            <tr>
                <td style="border: 1px solid #000; padding: 1px 4px; height: 14px;">&nbsp;</td>
                <td style="border: 1px solid #000; padding: 1px 4px; height: 14px;">&nbsp;</td>
                <td style="border: 1px solid #000; padding: 1px 4px; height: 14px;">&nbsp;</td>
                <td style="border: 1px solid #000; padding: 1px 4px; height: 14px;">&nbsp;</td>
            </tr>
        <?php endfor; ?>
    </tbody>
</table>

<!-- ===== TOTALS ===== -->
<table style="width: 100%; margin-bottom: 8px;" cellspacing="0" cellpadding="0">
    <tr>
        <td style="width: 60%;"></td>
        <td style="width: 40%;">
            <table style="width: 100%; border-collapse: collapse; font-size: 10px;">
                <tr>
                    <td style="border: 1px solid #000; padding: 2px 6px; text-align: right; font-weight: bold;">Subtotal</td>
                    <td style="border: 1px solid #000; padding: 2px 6px; text-align: right; width: 90px;">$<?php echo esc_html(number_format($order->get_subtotal(), 2)); ?></td>
                </tr>

                <?php if ($order->get_total_shipping() > 0): ?>
                <tr>
                    <td style="border: 1px solid #000; padding: 2px 6px; text-align: right; font-weight: bold;">Shipping</td>
                    <td style="border: 1px solid #000; padding: 2px 6px; text-align: right;">$<?php echo esc_html(number_format($order->get_total_shipping(), 2)); ?></td>
                </tr>
                <?php endif; ?>

                <?php if ($order->get_total_discount() > 0): ?>
                <tr>
                    <td style="border: 1px solid #000; padding: 2px 6px; text-align: right; font-weight: bold;">Discount</td>
                    <td style="border: 1px solid #000; padding: 2px 6px; text-align: right;">-$<?php echo esc_html(number_format($order->get_total_discount(), 2)); ?></td>
                </tr>
                <?php endif; ?>

                <?php
                // Fee lines (e.g., payment gateway fees, surcharges)
                foreach ($order->get_fees() as $fee_item):
                    $fee_total = (float) $fee_item->get_total();
                    if ($fee_total == 0) continue;
                ?>
                <tr>
                    <td style="border: 1px solid #000; padding: 2px 6px; text-align: right; font-weight: bold;"><?php echo esc_html($fee_item->get_name()); ?></td>
                    <td style="border: 1px solid #000; padding: 2px 6px; text-align: right;">
                        <?php echo ($fee_total < 0) ? '-' : ''; ?>$<?php echo esc_html(number_format(abs($fee_total), 2)); ?>
                    </td>
                </tr>
                <?php endforeach; ?>

                <?php if ($order->get_total_tax() > 0): ?>
                <tr>
                    <td style="border: 1px solid #000; padding: 2px 6px; text-align: right; font-weight: bold;">GST (9%)</td>
                    <td style="border: 1px solid #000; padding: 2px 6px; text-align: right;">$<?php echo esc_html(number_format($order->get_total_tax(), 2)); ?></td>
                </tr>
                <?php endif; ?>

                <tr>
                    <td style="border: 1px solid #000; padding: 3px 6px; text-align: right; font-weight: bold; font-size: 11px;">TOTAL</td>
                    <td style="border: 1px solid #000; padding: 3px 6px; text-align: right; font-weight: bold; font-size: 11px;">$<?php echo esc_html(number_format($order_total, 2)); ?></td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<!-- ===== PAYMENT STATUS ===== -->
<?php if ($is_paid): ?>
<div style="margin-top: 8px; padding: 3px 6px; border: 2px solid #000; font-size: 10px; text-align: center;">
    <strong>PAID</strong> - <?php echo esc_html($order->get_payment_method_title()); ?>
</div>
<?php else: ?>
<div style="margin-top: 8px; padding: 3px 6px; border: 2px solid #000; font-size: 10px; text-align: center;">
    <strong>PAYMENT REQUIRED: $<?php echo esc_html(number_format($order_total, 2)); ?></strong>
    <?php if ($payment_method === 'cod'): ?>
        - C.O.D.
    <?php else: ?>
        - Due by <?php echo esc_html($due_date); ?>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- ===== THANK YOU + EMAIL ===== -->
<div style="margin-top: 8px; margin-bottom: 3px;">
    <div style="font-size: 10px; font-weight: bold; color: #c00;">Thank you for your support!</div>
    <div style="font-size: 10px; font-weight: bold; margin-top: 1px;">
        Email: <?php echo esc_html($company_email); ?>
    </div>
</div>

<!-- ===== FOOTER NOTICES ===== -->
<div style="margin-top: 3px; font-size: 8px; line-height: 1.4; border-top: 1px solid #999; padding-top: 2px;">
    <strong>Payment Terms:</strong> Payment is due within 30 days from invoice date. Late payments may incur interest charges.<br>
    <strong>Returns Policy:</strong> Fresh produce must be inspected upon delivery. Claims for damaged goods must be made within 24 hours.<br>
    <br>
    Cheques should be crossed and made payable to: <strong><?php echo esc_html(strtoupper($company_name)); ?></strong><br>
    Bank Transfer to <?php echo esc_html(strtoupper($bank_name)); ?> CURRENT ACCOUNT <?php echo esc_html($bank_account); ?>
</div>

</body>
</html>
