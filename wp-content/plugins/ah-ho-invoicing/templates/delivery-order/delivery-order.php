<?php
/**
 * Delivery Order Template
 *
 * Matches traditional commercial delivery order format
 * Layout: Logo + Company header, Bill/Deliver To, Invoice details, Items table, Signature
 *
 * @package AhHoInvoicing
 * @since 1.5.1
 * @modified 2026-02-07 - use body margin for Dompdf compatibility
 */

if (!defined('ABSPATH')) {
    exit;
}

// Load company settings
$company_name    = get_option('ah_ho_company_name', 'Ah Ho Fruits Pte Ltd');
$company_address = get_option('ah_ho_company_address', '123 Fruit Lane, Singapore 123456');
$company_phone   = get_option('ah_ho_company_phone', '+65 1234 5678');
$company_email   = get_option('ah_ho_company_email', 'hello@ahhofruits.com');
$company_uen     = get_option('ah_ho_company_uen', '201234567A');
$company_gst     = get_option('ah_ho_company_gst', 'M12345678X');
$bank_name       = get_option('ah_ho_bank_name', 'DBS Bank');
$bank_account    = get_option('ah_ho_bank_account', '123-456-789-0');

// Logo: prefer settings URL, fallback to local file
$logo_url  = get_option('ah_ho_company_logo_url', '');
$logo_file = AH_HO_INVOICING_PLUGIN_DIR . 'assets/images/ah-ho-logo.png';
if (empty($logo_url) && file_exists($logo_file) && filesize($logo_file) > 200) {
    $logo_url = $logo_file;
}

// Get delivery summary
$summary      = AH_HO_Delivery_Order::get_delivery_summary($order);
$instructions = AH_HO_Delivery_Order::get_delivery_instructions($order);

// Delivery date
$delivery_date = AH_HO_Delivery_Date_Helper::get_delivery_date($order, 'j/n/Y');
if (empty($delivery_date)) {
    $delivery_date = $order->get_date_created()->format('j/n/Y');
}

// Payment terms
$payment_method = $order->get_payment_method();
$terms = ($payment_method === 'cod') ? 'C.O.D.' : $order->get_payment_method_title();

// Billing info
$bill_name    = trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name());
$bill_company = $order->get_billing_company();
$bill_addr1   = $order->get_billing_address_1();
$bill_addr2   = $order->get_billing_address_2();
$bill_city    = $order->get_billing_city();
$bill_postal  = $order->get_billing_postcode();

// Shipping/Deliver To info
$ship_name    = trim($order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name());
$ship_company = $order->get_shipping_company();
$ship_addr1   = $order->get_shipping_address_1();
$ship_addr2   = $order->get_shipping_address_2();
$ship_city    = $order->get_shipping_city();
$ship_postal  = $order->get_shipping_postcode();

// If no shipping, use billing
if (empty($ship_name) || $ship_name === ' ') {
    $ship_name    = $bill_name;
    $ship_company = $bill_company;
    $ship_addr1   = $bill_addr1;
    $ship_addr2   = $bill_addr2;
    $ship_city    = $bill_city;
    $ship_postal  = $bill_postal;
}

// Remarks: combine delivery instructions
$remarks = '';
if (!empty($instructions)) {
    $remarks_parts = array();
    foreach ($instructions as $inst) {
        $remarks_parts[] = $inst['label'] . ': ' . $inst['value'];
    }
    $remarks = implode("\n", $remarks_parts);
}

// Customer note
$customer_note = $order->get_customer_note();
if (!empty($customer_note) && empty($remarks)) {
    $remarks = $customer_note;
} elseif (!empty($customer_note) && !empty($remarks)) {
    $remarks = $customer_note . "\n" . $remarks;
}
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
            margin: 15mm 15mm 15mm 15mm;
        }
        h1, h2, h3, h4 { margin: 0 0 5px 0; }
        table { border-collapse: collapse; width: 100%; }
        strong { font-weight: bold; }
        .page-break { page-break-after: always; }
        .no-break { page-break-inside: avoid; }
    </style>
</head>
<body>

<!-- ===== HEADER: Logo + Company Name + Registration ===== -->
<table class="header-table" style="width: 98%; border-bottom: 2px solid #000; margin-bottom: 10px; padding-bottom: 8px;">
    <tr>
        <td style="width: 15%; vertical-align: middle;">
            <?php if (!empty($logo_url)): ?>
                <img src="<?php echo esc_attr($logo_url); ?>" alt="<?php echo esc_attr($company_name); ?>" style="max-width: 80px; max-height: 80px;" />
            <?php endif; ?>
        </td>
        <td style="width: 52%; vertical-align: middle; text-align: left; padding-left: 5px;">
            <div style="font-size: 22px; font-weight: bold; letter-spacing: 0.5px; line-height: 1.2;">
                <?php echo esc_html(strtoupper($company_name)); ?>
            </div>
            <div style="font-size: 9px; color: #333; margin-top: 3px; line-height: 1.4;">
                <?php echo nl2br(esc_html($company_address)); ?>
                &nbsp;&nbsp;&nbsp;&nbsp;Phone: <?php echo esc_html($company_phone); ?>
            </div>
        </td>
        <td style="width: 33%; vertical-align: middle; text-align: right; font-size: 9px; line-height: 1.6; padding-right: 2px;">
            <strong>UEN No.</strong> &nbsp; <?php echo esc_html($company_uen); ?><br>
            <strong>GST Reg No:</strong> &nbsp; <?php echo esc_html($company_gst); ?>
        </td>
    </tr>
</table>

<!-- ===== BILL TO / DELIVER TO / DELIVERY ORDER INFO ===== -->
<table style="width: 100%; margin-bottom: 8px;" cellspacing="0" cellpadding="0">
    <tr>
        <!-- Bill To -->
        <td style="width: 30%; vertical-align: top; padding-right: 10px;">
            <div style="font-size: 10px; font-weight: bold; margin-bottom: 4px;">Bill To:</div>
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
                    <?php echo esc_html(trim($bill_city . ' ' . $bill_postal)); ?>
                <?php endif; ?>
            </div>
        </td>

        <!-- Deliver To -->
        <td style="width: 30%; vertical-align: top; padding-right: 10px;">
            <div style="font-size: 10px; font-weight: bold; margin-bottom: 4px;">Deliver To:</div>
            <div style="font-size: 10px; line-height: 1.5;">
                <?php echo esc_html($ship_name); ?><br>
                <?php if ($ship_company): ?>
                    <?php echo esc_html($ship_company); ?><br>
                <?php endif; ?>
                <?php if ($ship_addr1): ?>
                    <?php echo esc_html($ship_addr1); ?><br>
                <?php endif; ?>
                <?php if ($ship_addr2): ?>
                    <?php echo esc_html($ship_addr2); ?><br>
                <?php endif; ?>
                <?php if ($ship_city || $ship_postal): ?>
                    <?php echo esc_html(trim($ship_city . ' ' . $ship_postal)); ?>
                <?php endif; ?>
            </div>
        </td>

        <!-- Delivery Order title + Invoice details -->
        <td style="width: 40%; vertical-align: top;">
            <div style="font-size: 20px; font-weight: bold; margin-bottom: 8px;">Delivery Order</div>
            <table style="width: 100%; border-collapse: collapse; font-size: 10px;">
                <tr>
                    <td style="border: 1px solid #000; padding: 4px 6px; font-weight: bold; width: 40%;">Invoice No</td>
                    <td style="border: 1px solid #000; padding: 4px 6px;"><?php echo esc_html($order->get_order_number()); ?></td>
                </tr>
                <tr>
                    <td style="border: 1px solid #000; padding: 4px 6px; font-weight: bold;">Date</td>
                    <td style="border: 1px solid #000; padding: 4px 6px;"><?php echo esc_html($delivery_date); ?></td>
                </tr>
                <tr>
                    <td style="border: 1px solid #000; padding: 4px 6px; font-weight: bold;">PO No</td>
                    <td style="border: 1px solid #000; padding: 4px 6px;">&nbsp;</td>
                </tr>
                <tr>
                    <td style="border: 1px solid #000; padding: 4px 6px; font-weight: bold;">Terms</td>
                    <td style="border: 1px solid #000; padding: 4px 6px;"><?php echo esc_html($terms); ?></td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<!-- ===== REMARKS ===== -->
<table style="width: 100%; margin-bottom: 8px;" cellspacing="0" cellpadding="0">
    <tr>
        <td style="font-size: 10px; font-weight: bold; padding-bottom: 2px;">Remarks:</td>
    </tr>
    <?php if (!empty($remarks)): ?>
    <tr>
        <td style="font-size: 10px; line-height: 1.5; padding-bottom: 6px;">
            <?php echo nl2br(esc_html($remarks)); ?>
        </td>
    </tr>
    <?php endif; ?>
</table>

<!-- ===== ITEMS TABLE ===== -->
<table style="width: 100%; border-collapse: collapse; margin-bottom: 10px;" cellspacing="0" cellpadding="0">
    <thead>
        <tr>
            <th style="border: 1px solid #000; padding: 6px 8px; text-align: center; width: 60px; font-size: 11px; font-weight: bold;">Qty</th>
            <th style="border: 1px solid #000; padding: 6px 8px; text-align: left; font-size: 11px; font-weight: bold;">Description</th>
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
        ?>
            <tr>
                <td style="border: 1px solid #000; padding: 5px 8px; text-align: center; font-size: 11px; font-weight: bold;">
                    <?php echo esc_html($item->get_quantity()); ?>
                </td>
                <td style="border: 1px solid #000; padding: 5px 8px; font-size: 11px;">
                    <?php echo esc_html($description); ?>
                    <?php if (!empty($product_notes)): ?>
                        <br><span style="font-size: 10px; color: #333;">** <?php echo esc_html($product_notes); ?></span>
                    <?php endif; ?>
                    <?php if ($is_gift === __('Yes', 'ah-ho-fruits')): ?>
                        <br><span style="font-size: 10px; color: #333;">*** GIFT<?php if (!empty($gift_message)): ?> - "<?php echo esc_html($gift_message); ?>"<?php endif; ?></span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>

        <?php
        // Add empty rows to fill out the table (minimum ~15 rows like the physical form)
        $min_rows = 15;
        for ($i = $row_count; $i < $min_rows; $i++):
        ?>
            <tr>
                <td style="border: 1px solid #000; padding: 5px 8px; height: 20px;">&nbsp;</td>
                <td style="border: 1px solid #000; padding: 5px 8px; height: 20px;">&nbsp;</td>
            </tr>
        <?php endfor; ?>
    </tbody>
</table>

<!-- ===== THANK YOU + EMAIL ===== -->
<div style="margin-top: 15px; margin-bottom: 15px;">
    <div style="font-size: 11px; font-weight: bold; color: #c00;">Thank you for your support!</div>
    <div style="font-size: 11px; font-weight: bold; margin-top: 3px;">
        Email: <?php echo esc_html($company_email); ?>
    </div>
</div>

<!-- ===== SIGNATURE SECTION ===== -->
<table style="width: 100%; margin-top: 20px; margin-bottom: 15px;" cellspacing="0" cellpadding="0">
    <tr>
        <td style="width: 50%; vertical-align: bottom; padding-right: 20px;">
            <!-- Signature space -->
            <div style="height: 60px;"></div>
            <div style="border-top: 1px solid #000; padding-top: 5px; font-size: 10px; line-height: 1.5;">
                <!-- Company authorized signatory name can go here -->
                &nbsp;
            </div>
        </td>
        <td style="width: 50%; vertical-align: bottom; padding-left: 20px;">
            <div style="height: 60px;"></div>
            <div style="border-top: 1px solid #000; padding-top: 5px; font-size: 10px; text-align: center;">
                Customer's Stamp and Signature
            </div>
        </td>
    </tr>
</table>

<?php if ($summary['amount_to_collect'] > 0): ?>
<!-- ===== COD PAYMENT COLLECTION ===== -->
<div style="margin-top: 10px; padding: 8px; border: 2px solid #000; font-size: 11px;">
    <strong>COLLECT PAYMENT: $<?php echo esc_html(number_format($summary['amount_to_collect'], 2)); ?></strong>
</div>
<?php endif; ?>

<!-- ===== FOOTER NOTICES ===== -->
<div style="margin-top: 15px; font-size: 9px; line-height: 1.6; border-top: 1px solid #999; padding-top: 8px;">
    Please ensure that goods are in good order and condition.<br>
    Goods sold cannot be <strong>EXCHANGED</strong> or <strong>RETURNED</strong>.<br>
    <br>
    Cheques should be crossed and made payable to: <strong><?php echo esc_html(strtoupper($company_name)); ?></strong><br>
    Bank Transfer to <?php echo esc_html(strtoupper($bank_name)); ?> CURRENT ACCOUNT <?php echo esc_html($bank_account); ?>
</div>

</body>
</html>
