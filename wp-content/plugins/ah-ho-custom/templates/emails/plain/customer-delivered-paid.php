<?php
/**
 * Customer Delivered - Paid Email Template (Plain Text)
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

echo esc_html__('Your order has been successfully delivered!', 'ah-ho-custom') . "\n\n";

echo esc_html__('We hope you enjoy your fresh fruits. Thank you for choosing Ah Ho Fruits!', 'ah-ho-custom') . "\n\n";

echo esc_html__('If you have any questions or concerns about your order, please don\'t hesitate to contact us.', 'ah-ho-custom') . "\n\n";

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

do_action('woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email);

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

do_action('woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email);

do_action('woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email);

if ($additional_content) {
    echo "\n" . wp_strip_all_tags(wpautop(wptexturize($additional_content))) . "\n\n";
}

echo apply_filters('woocommerce_email_footer_text', get_option('woocommerce_email_footer_text'));
