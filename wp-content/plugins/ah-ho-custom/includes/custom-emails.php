<?php
/**
 * Custom Email Notifications
 *
 * Register custom emails with WooCommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register custom email classes
 */
function ah_ho_register_custom_emails($emails) {
    require_once AH_HO_CUSTOM_PLUGIN_DIR . 'includes/emails/class-ah-ho-ready-delivery-email.php';
    require_once AH_HO_CUSTOM_PLUGIN_DIR . 'includes/emails/class-ah-ho-out-delivery-email.php';
    require_once AH_HO_CUSTOM_PLUGIN_DIR . 'includes/emails/class-ah-ho-delivered-paid-email.php';

    $emails['AH_HO_Ready_Delivery_Email'] = new AH_HO_Ready_Delivery_Email();
    $emails['AH_HO_Out_Delivery_Email'] = new AH_HO_Out_Delivery_Email();
    $emails['AH_HO_Delivered_Paid_Email'] = new AH_HO_Delivered_Paid_Email();

    return $emails;
}
add_filter('woocommerce_email_classes', 'ah_ho_register_custom_emails');

/**
 * Trigger custom emails when order status changes
 */
function ah_ho_trigger_status_emails($order_id, $old_status, $new_status, $order) {
    // Don't send if status didn't actually change
    if ($old_status === $new_status) {
        return;
    }

    // Trigger appropriate email based on new status
    switch ($new_status) {
        case 'ready-delivery':
            do_action('woocommerce_order_status_ready-delivery_notification', $order_id, $order);
            break;

        case 'out-delivery':
            do_action('woocommerce_order_status_out-delivery_notification', $order_id, $order);
            break;

        case 'delivered-paid':
            do_action('woocommerce_order_status_delivered-paid_notification', $order_id, $order);
            break;

        case 'delivered-awaiting':
            // B2B email - send invoice reminder
            do_action('woocommerce_order_status_delivered-awaiting_notification', $order_id, $order);
            break;

        case 'payment-received':
            // B2B payment confirmation
            do_action('woocommerce_order_status_payment-received_notification', $order_id, $order);
            break;
    }
}
add_action('woocommerce_order_status_changed', 'ah_ho_trigger_status_emails', 10, 4);

/**
 * Add custom email actions to WooCommerce email actions list
 */
function ah_ho_add_email_actions($actions) {
    $actions[] = 'woocommerce_order_status_ready-delivery_notification';
    $actions[] = 'woocommerce_order_status_out-delivery_notification';
    $actions[] = 'woocommerce_order_status_delivered-paid_notification';
    $actions[] = 'woocommerce_order_status_delivered-awaiting_notification';
    $actions[] = 'woocommerce_order_status_payment-received_notification';

    return $actions;
}
add_filter('woocommerce_email_actions', 'ah_ho_add_email_actions');

/**
 * Locate custom email templates
 */
function ah_ho_locate_email_templates($template, $template_name, $template_path) {
    $plugin_path = AH_HO_CUSTOM_PLUGIN_DIR . 'templates/';

    // Check if template exists in plugin
    if (file_exists($plugin_path . $template_name)) {
        $template = $plugin_path . $template_name;
    }

    return $template;
}
add_filter('woocommerce_locate_template', 'ah_ho_locate_email_templates', 10, 3);
