<?php
/**
 * Customer Ready for Delivery Email Template (Plain Text)
 *
 * @var WC_Order $order
 * @var string $email_heading
 * @var string $additional_content
 * @var bool $sent_to_admin
 * @var bool $plain_text
 * @var WC_Email $email
 */

if (!defined('ABSPATH')) {
    exit;
}

echo "= " . $email_heading . " =\n\n";

echo sprintf(esc_html__('Hi %s,', 'ah-ho-custom'), esc_html($order->get_billing_first_name())) . "\n\n";

echo esc_html__('Good news! Your order has been packed and is ready for delivery.', 'ah-ho-custom') . "\n\n";

echo esc_html__('Our team has carefully prepared your fresh fruits and they are now waiting to be assigned to a delivery driver. You will receive another notification as soon as your order is out for delivery.', 'ah-ho-custom') . "\n\n";

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

do_action('woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email);

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

do_action('woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email);

do_action('woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email);

if ($additional_content) {
    echo "\n" . wp_strip_all_tags(wpautop(wptexturize($additional_content))) . "\n\n";
}

echo apply_filters('woocommerce_email_footer_text', get_option('woocommerce_email_footer_text'));
