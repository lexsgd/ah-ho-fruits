<?php
/**
 * Payment Gateway Settings
 *
 * Custom payment gateway configurations for Ah Ho Fruits
 *
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Set PayNow as the default payment method
 */
add_filter('woocommerce_default_gateway', 'ah_ho_set_default_payment_gateway');

function ah_ho_set_default_payment_gateway($default_gateway) {
    return 'stripe_paynow';
}

/**
 * Reorder available payment gateways at checkout (frontend)
 * This filter fires AFTER gateways are filtered for availability
 */
add_filter('woocommerce_available_payment_gateways', 'ah_ho_reorder_available_gateways', 100);

function ah_ho_reorder_available_gateways($gateways) {
    if (empty($gateways) || !isset($gateways['stripe_paynow'])) {
        return $gateways;
    }

    // Move PayNow to the beginning
    $paynow = $gateways['stripe_paynow'];
    unset($gateways['stripe_paynow']);

    // Rebuild array with PayNow first
    return array_merge(['stripe_paynow' => $paynow], $gateways);
}

/**
 * Set session default for new customers (before checkout loads)
 */
add_action('woocommerce_before_checkout_form', 'ah_ho_set_session_default_payment', 5);

function ah_ho_set_session_default_payment() {
    if (!WC()->session) {
        return;
    }

    // Force PayNow as default for new sessions
    $chosen = WC()->session->get('chosen_payment_method');
    if (empty($chosen) || $chosen === 'stripe') {
        WC()->session->set('chosen_payment_method', 'stripe_paynow');
    }
}

/**
 * Also handle WooCommerce Blocks checkout
 */
add_action('wp_enqueue_scripts', 'ah_ho_blocks_default_payment_js');

function ah_ho_blocks_default_payment_js() {
    if (!function_exists('is_checkout') || !is_checkout()) {
        return;
    }

    // Inline script to set PayNow as default in Blocks checkout
    wp_add_inline_script('wc-blocks-checkout', '
        document.addEventListener("DOMContentLoaded", function() {
            if (typeof wp !== "undefined" && wp.data) {
                setTimeout(function() {
                    const paymentStore = wp.data.select("wc/store/payment");
                    const currentMethod = paymentStore ? paymentStore.getActivePaymentMethod() : null;
                    if (!currentMethod || currentMethod === "stripe") {
                        const dispatch = wp.data.dispatch("wc/store/payment");
                        if (dispatch && dispatch.setActivePaymentMethod) {
                            dispatch.setActivePaymentMethod("stripe_paynow");
                        }
                    }
                }, 500);
            }
        });
    ', 'after');
}
