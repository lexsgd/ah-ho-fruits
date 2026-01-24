<?php
/**
 * Customer Out for Delivery Email Template (HTML)
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

<p><?php esc_html_e('Your order is on the way! 🚚', 'ah-ho-custom'); ?></p>

<p><?php esc_html_e('Our delivery driver has your fresh fruits and is currently en route to your location. Please ensure someone is available to receive the delivery.', 'ah-ho-custom'); ?></p>

<p><strong><?php esc_html_e('Delivery Address:', 'ah-ho-custom'); ?></strong><br>
<?php echo wp_kses_post($order->get_formatted_shipping_address()); ?></p>

<?php
do_action('woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email);

do_action('woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email);

do_action('woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email);

if ($additional_content) {
    echo '<p>' . wp_kses_post(wpautop(wptexturize($additional_content))) . '</p>';
}

do_action('woocommerce_email_footer', $email);
