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
 *
 * Common PayNow gateway IDs:
 * - stripe_paynow (Payment Plugins for Stripe WooCommerce)
 * - stripe_local_payment_paynow (alternative)
 */
add_filter('woocommerce_default_gateway', 'ah_ho_set_default_payment_gateway');

function ah_ho_set_default_payment_gateway($default_gateway) {
    // PayNow gateway ID from Payment Plugins for Stripe
    return 'stripe_paynow';
}

/**
 * Reorder payment gateways to show PayNow first
 */
add_filter('woocommerce_payment_gateways', 'ah_ho_reorder_payment_gateways', 100);

function ah_ho_reorder_payment_gateways($gateways) {
    // Define preferred order (PayNow first)
    $preferred_order = [
        'stripe_paynow',      // PayNow (Payment Plugins for Stripe)
        'stripe_cc',          // Credit Card
        'stripe_googlepay',   // Google Pay
        'stripe_applepay',    // Apple Pay
        'stripe_link',        // Link by Stripe
    ];

    $ordered = [];
    $remaining = [];

    foreach ($gateways as $gateway) {
        $gateway_id = is_object($gateway) ? $gateway->id : $gateway;
        $position = array_search($gateway_id, $preferred_order);

        if ($position !== false) {
            $ordered[$position] = $gateway;
        } else {
            $remaining[] = $gateway;
        }
    }

    // Sort by preferred position
    ksort($ordered);

    // Merge: preferred order first, then remaining
    return array_merge(array_values($ordered), $remaining);
}

/**
 * Also set default for WooCommerce Blocks checkout
 */
add_filter('woocommerce_store_api_cart_payment_method', 'ah_ho_blocks_default_payment', 10, 2);

function ah_ho_blocks_default_payment($payment_method, $cart) {
    // If no payment method set, default to PayNow
    if (empty($payment_method)) {
        return 'stripe_paynow';
    }
    return $payment_method;
}

/**
 * Set session default for new customers
 */
add_action('woocommerce_before_checkout_form', 'ah_ho_set_session_default_payment');

function ah_ho_set_session_default_payment() {
    if (!WC()->session) {
        return;
    }

    // Only set if no payment method chosen yet
    $chosen = WC()->session->get('chosen_payment_method');
    if (empty($chosen)) {
        WC()->session->set('chosen_payment_method', 'stripe_paynow');
    }
}
