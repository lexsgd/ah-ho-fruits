<?php
/**
 * Customer "Out for Delivery" Email Template (Plain Text)
 *
 * @package AhHoInvoicing
 * @since 1.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html(wp_strip_all_tags($email_heading));
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/* translators: %s: Customer billing first name */
printf(__('Hi %s,', 'ah-ho-invoicing'), esc_html($order->get_billing_first_name()));
echo "\n\n";

/* translators: %s: Order number */
printf(__('Great news! Your order #%s is now out for delivery and on its way to you.', 'ah-ho-invoicing'), esc_html($order->get_order_number()));
echo "\n\n";

echo wp_kses_post(wptexturize(__('DELIVERY DETAILS', 'ah-ho-invoicing'))) . "\n";
echo "-----------------------------------\n\n";

echo __('Delivery Address:', 'ah-ho-invoicing') . "\n";
echo wp_kses_post($order->get_formatted_shipping_address());
echo "\n\n";

echo __('Contact Number:', 'ah-ho-invoicing') . "\n";
echo esc_html($order->get_billing_phone());
echo "\n\n";

$delivery_date = get_post_meta($order->get_id(), '_delivery_date', true);
if (!empty($delivery_date)) {
    echo __('Expected Delivery:', 'ah-ho-invoicing') . "\n";
    echo esc_html(date('l, d F Y', strtotime($delivery_date)));
    echo "\n\n";
}

echo "** " . __('IMPORTANT:', 'ah-ho-invoicing') . " **\n";
echo __('Our delivery driver may contact you if they need assistance locating your address. Please keep your phone nearby.', 'ah-ho-invoicing');
echo "\n\n";

echo "-----------------------------------\n\n";

/*
 * @hooked WC_Emails::order_details() Shows the order details table.
 */
do_action('woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email);

echo "\n";

/*
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action('woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email);

/*
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */
do_action('woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email);

echo "\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ($additional_content) {
    echo esc_html(wp_strip_all_tags(wptexturize($additional_content)));
    echo "\n\n";
}

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo apply_filters('woocommerce_email_footer_text', get_option('woocommerce_email_footer_text'));
