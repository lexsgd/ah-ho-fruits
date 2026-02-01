<?php
/**
 * Customer "Out for Delivery" Email Template (HTML)
 *
 * @package AhHoInvoicing
 * @since 1.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/*
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action('woocommerce_email_header', $email_heading, $email); ?>

<p><?php _e('Hi there,', 'ah-ho-invoicing'); ?></p>

<p><?php
    /* translators: %s: Order number */
    printf(__('Great news! Your order <strong>#%s</strong> is now out for delivery and on its way to you.', 'ah-ho-invoicing'), $order->get_order_number());
?></p>

<p><strong><?php _e('Delivery Details:', 'ah-ho-invoicing'); ?></strong></p>

<table cellspacing="0" cellpadding="6" style="width: 100%; border: 1px solid #eee; margin-bottom: 20px;" border="1" bordercolor="#eee">
    <tbody>
        <tr>
            <th scope="row" style="text-align:left; background-color: #f8f8f8;"><?php _e('Delivery Address', 'ah-ho-invoicing'); ?></th>
            <td style="text-align:left;">
                <?php echo wp_kses_post($order->get_formatted_shipping_address()); ?>
            </td>
        </tr>
        <tr>
            <th scope="row" style="text-align:left; background-color: #f8f8f8;"><?php _e('Contact Number', 'ah-ho-invoicing'); ?></th>
            <td style="text-align:left;">
                <?php echo esc_html($order->get_billing_phone()); ?>
            </td>
        </tr>
        <?php
        $delivery_date = AH_HO_Delivery_Date_Helper::get_delivery_date($order, 'l, d F Y');
        if (!empty($delivery_date)):
        ?>
        <tr>
            <th scope="row" style="text-align:left; background-color: #f8f8f8;"><?php _e('Expected Delivery', 'ah-ho-invoicing'); ?></th>
            <td style="text-align:left;">
                <strong><?php echo esc_html($delivery_date); ?></strong>
            </td>
        </tr>
        <?php endif; ?>
    </tbody>
</table>

<p style="background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 12px; margin: 20px 0;">
    <strong><?php _e('Please note:', 'ah-ho-invoicing'); ?></strong><br>
    <?php _e('Our delivery driver may contact you if they need assistance locating your address. Please keep your phone nearby.', 'ah-ho-invoicing'); ?>
</p>

<?php
/*
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Emails::order_schema_markup() Adds Schema.org markup.
 */
do_action('woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email);

/*
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action('woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email);

/*
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */
do_action('woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email);

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ($additional_content) {
    echo wp_kses_post(wpautop(wptexturize($additional_content)));
}

/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action('woocommerce_email_footer', $email);
