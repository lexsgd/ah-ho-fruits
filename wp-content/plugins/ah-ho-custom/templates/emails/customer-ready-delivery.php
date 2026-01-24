<?php
/**
 * Customer Ready for Delivery Email Template (HTML)
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

<p><?php esc_html_e('Good news! Your order has been packed and is ready for delivery.', 'ah-ho-custom'); ?></p>

<p><?php esc_html_e('Our team has carefully prepared your fresh fruits and they are now waiting to be assigned to a delivery driver. You will receive another notification as soon as your order is out for delivery.', 'ah-ho-custom'); ?></p>

<?php
do_action('woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email);

do_action('woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email);

do_action('woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email);

if ($additional_content) {
    echo '<p>' . wp_kses_post(wpautop(wptexturize($additional_content))) . '</p>';
}

do_action('woocommerce_email_footer', $email);
