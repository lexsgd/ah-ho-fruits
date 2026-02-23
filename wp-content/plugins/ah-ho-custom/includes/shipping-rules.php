<?php
/**
 * Shipping Rules
 *
 * Automatically applies free shipping when cart subtotal >= $60.
 * Express shipping remains available as an alternative.
 *
 * @package AhHoCustom
 * @since 1.6.3
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Auto-apply free shipping when cart subtotal >= $60.
 *
 * When the threshold is met:
 * - Free shipping is the default selected option (appears first)
 * - Express shipping remains available
 * - Standard/flat-rate shipping is removed
 *
 * When below threshold: all regular shipping options show as normal.
 *
 * @param array $rates Shipping rates for the package.
 * @param array $package Package data.
 * @return array Modified shipping rates.
 */
add_filter('woocommerce_package_rates', 'ah_ho_auto_free_shipping', 10, 2);

function ah_ho_auto_free_shipping($rates, $package) {
    // Only apply when WooCommerce cart is available
    if (!WC()->cart) {
        return $rates;
    }

    $cart_subtotal = WC()->cart->get_subtotal();
    $free_shipping_threshold = 60; // SGD

    if ($cart_subtotal >= $free_shipping_threshold) {
        $free_rates    = array();
        $express_rates = array();

        foreach ($rates as $rate_id => $rate) {
            if ($rate->method_id === 'free_shipping') {
                // Keep free shipping
                $free_rates[$rate_id] = $rate;
            } elseif (stripos($rate->label, 'express') !== false) {
                // Keep express shipping
                $express_rates[$rate_id] = $rate;
            }
            // All other shipping methods (flat rate, local pickup, etc.) are dropped
        }

        // Free shipping first (becomes default selected), then express
        if (!empty($free_rates)) {
            $rates = $free_rates + $express_rates;
        }
        // If no free shipping method is configured, don't remove anything
    }

    return $rates;
}
