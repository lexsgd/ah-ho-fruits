<?php
/**
 * Payment Gateway Settings
 *
 * Custom payment gateway configurations for Ah Ho Fruit
 * Sets PayNow as the default payment method
 *
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Set PayNow as the default payment method (classic checkout)
 */
add_filter('woocommerce_default_gateway', 'ah_ho_set_default_payment_gateway');

function ah_ho_set_default_payment_gateway($default_gateway) {
    return 'stripe_paynow';
}

/**
 * Reorder available payment gateways - PayNow first
 * Works for both classic checkout and affects Store API
 */
add_filter('woocommerce_available_payment_gateways', 'ah_ho_reorder_available_gateways', 9999);

function ah_ho_reorder_available_gateways($gateways) {
    if (empty($gateways) || !isset($gateways['stripe_paynow'])) {
        return $gateways;
    }

    // Move PayNow to the beginning
    $paynow = $gateways['stripe_paynow'];
    unset($gateways['stripe_paynow']);

    return array_merge(['stripe_paynow' => $paynow], $gateways);
}

/**
 * Force session payment method to PayNow
 */
add_action('woocommerce_before_checkout_form', 'ah_ho_set_session_default_payment', 1);
add_action('wp', 'ah_ho_set_session_default_payment_early', 1);

function ah_ho_set_session_default_payment() {
    ah_ho_force_paynow_session();
}

function ah_ho_set_session_default_payment_early() {
    if (function_exists('is_checkout') && is_checkout()) {
        ah_ho_force_paynow_session();
    }
}

function ah_ho_force_paynow_session() {
    if (!function_exists('WC') || !WC()->session) {
        return;
    }

    $chosen = WC()->session->get('chosen_payment_method');
    // Force PayNow unless user explicitly chose something else
    if (empty($chosen) || $chosen === 'stripe') {
        WC()->session->set('chosen_payment_method', 'stripe_paynow');
    }
}

/**
 * WooCommerce Blocks: Add inline JS to set PayNow as default
 * Uses polling to wait for payment methods to load
 */
add_action('wp_footer', 'ah_ho_blocks_default_payment_footer_js', 100);

function ah_ho_blocks_default_payment_footer_js() {
    if (!function_exists('is_checkout') || !is_checkout()) {
        return;
    }
    ?>
    <script type="text/javascript">
    (function() {
        var attempts = 0;
        var maxAttempts = 60; // Try for 30 seconds (500ms intervals)

        function setPayNowDefault() {
            attempts++;

            if (typeof wp === 'undefined' || !wp.data) {
                if (attempts < maxAttempts) {
                    setTimeout(setPayNowDefault, 500);
                }
                return;
            }

            var paymentStore = wp.data.select('wc/store/payment');
            if (!paymentStore) {
                if (attempts < maxAttempts) {
                    setTimeout(setPayNowDefault, 500);
                }
                return;
            }

            // Check if payment methods are available
            var methods = paymentStore.getAvailablePaymentMethods ?
                          paymentStore.getAvailablePaymentMethods() : null;

            if (!methods || Object.keys(methods).length === 0) {
                if (attempts < maxAttempts) {
                    setTimeout(setPayNowDefault, 500);
                }
                return;
            }

            // Check if stripe_paynow exists
            if (!methods['stripe_paynow']) {
                if (attempts < maxAttempts) {
                    setTimeout(setPayNowDefault, 500);
                }
                return;
            }

            var currentMethod = paymentStore.getActivePaymentMethod();

            // Only change if current is stripe (credit card) or empty
            if (!currentMethod || currentMethod === 'stripe') {
                var dispatch = wp.data.dispatch('wc/store/payment');
                if (dispatch && dispatch.setActivePaymentMethod) {
                    dispatch.setActivePaymentMethod('stripe_paynow');
                    console.log('PayNow set as default payment method');
                }
            }
        }

        // Start checking after DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(setPayNowDefault, 1000);
            });
        } else {
            setTimeout(setPayNowDefault, 1000);
        }
    })();
    </script>
    <?php
}
