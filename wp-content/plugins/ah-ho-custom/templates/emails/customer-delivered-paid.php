<?php
/**
 * Customer Delivered - Paid Email Template (HTML)
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

do_action('woocommerce_email_header', $email_heading, $email); ?>

<p><?php printf(esc_html__('Hi %s,', 'ah-ho-custom'), esc_html($order->get_billing_first_name())); ?></p>

<p><?php esc_html_e('Your order has been successfully delivered! 🎉', 'ah-ho-custom'); ?></p>

<p><?php esc_html_e('We hope you enjoy your fresh fruits. Thank you for choosing Ah Ho Fruit!', 'ah-ho-custom'); ?></p>

<p><?php esc_html_e('If you have any questions or concerns about your order, please don\'t hesitate to contact us.', 'ah-ho-custom'); ?></p>

<?php
do_action('woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email);

do_action('woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email);

do_action('woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email);

if ($additional_content) {
    echo '<p>' . wp_kses_post(wpautop(wptexturize($additional_content))) . '</p>';
}

do_action('woocommerce_email_footer', $email);
