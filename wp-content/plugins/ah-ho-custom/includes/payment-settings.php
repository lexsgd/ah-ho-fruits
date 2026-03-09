<?php
/**
 * Payment Gateway Settings
 *
 * Custom payment gateway configurations for Ah Ho Fruit.
 * Sets Stripe (Credit Card) as default so the 3.5% processing fee
 * is visible from the start. Customers who select PayNow pay $0 fees.
 *
 * @since 1.5.0
 * @modified 2026-03-10 - switch default from PayNow to Stripe credit card
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Set Stripe (Credit Card) as the default payment method
 */
add_filter('woocommerce_default_gateway', 'ah_ho_set_default_payment_gateway');

function ah_ho_set_default_payment_gateway($default_gateway) {
    return 'stripe';
}

/**
 * Reorder available payment gateways - Stripe first, PayNow second
 */
add_filter('woocommerce_available_payment_gateways', 'ah_ho_reorder_available_gateways', 9999);

function ah_ho_reorder_available_gateways($gateways) {
    if (empty($gateways)) {
        return $gateways;
    }

    $ordered = [];

    // Stripe (credit card) first
    if (isset($gateways['stripe'])) {
        $ordered['stripe'] = $gateways['stripe'];
        unset($gateways['stripe']);
    }

    // PayNow second
    if (isset($gateways['stripe_paynow'])) {
        $ordered['stripe_paynow'] = $gateways['stripe_paynow'];
        unset($gateways['stripe_paynow']);
    }

    // Remaining gateways
    return array_merge($ordered, $gateways);
}

/**
 * Set Stripe as session default so the fee calculates immediately
 * on both cart and checkout pages.
 */
add_action('woocommerce_before_checkout_form', 'ah_ho_set_session_default_payment', 1);
add_action('wp', 'ah_ho_set_session_default_payment_early', 1);

function ah_ho_set_session_default_payment() {
    ah_ho_force_stripe_session();
}

function ah_ho_set_session_default_payment_early() {
    if (function_exists('is_checkout') && is_checkout()) {
        ah_ho_force_stripe_session();
    }
    // Also set on cart page so the fee shows in cart totals
    if (function_exists('is_cart') && is_cart()) {
        ah_ho_force_stripe_session();
    }
}

function ah_ho_force_stripe_session() {
    if (!function_exists('WC') || !WC()->session) {
        return;
    }

    $chosen = WC()->session->get('chosen_payment_method');
    // Set Stripe as default if no method chosen yet
    if (empty($chosen)) {
        WC()->session->set('chosen_payment_method', 'stripe');
    }
}

/**
 * WooCommerce Blocks: Add inline JS to set Stripe as default
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
        var maxAttempts = 60;

        function setStripeDefault() {
            attempts++;

            if (typeof wp === 'undefined' || !wp.data) {
                if (attempts < maxAttempts) {
                    setTimeout(setStripeDefault, 500);
                }
                return;
            }

            var paymentStore = wp.data.select('wc/store/payment');
            if (!paymentStore) {
                if (attempts < maxAttempts) {
                    setTimeout(setStripeDefault, 500);
                }
                return;
            }

            var methods = paymentStore.getAvailablePaymentMethods ?
                          paymentStore.getAvailablePaymentMethods() : null;

            if (!methods || Object.keys(methods).length === 0) {
                if (attempts < maxAttempts) {
                    setTimeout(setStripeDefault, 500);
                }
                return;
            }

            if (!methods['stripe']) {
                if (attempts < maxAttempts) {
                    setTimeout(setStripeDefault, 500);
                }
                return;
            }

            var currentMethod = paymentStore.getActivePaymentMethod();

            // Only set Stripe if nothing is selected yet
            if (!currentMethod || currentMethod === 'stripe_paynow') {
                var dispatch = wp.data.dispatch('wc/store/payment');
                if (dispatch && dispatch.setActivePaymentMethod) {
                    dispatch.setActivePaymentMethod('stripe');
                }
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(setStripeDefault, 1000);
            });
        } else {
            setTimeout(setStripeDefault, 1000);
        }
    })();
    </script>
    <?php
}
