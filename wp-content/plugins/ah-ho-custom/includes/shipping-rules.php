<?php
/**
 * Shipping Rules
 *
 * Automatically applies free shipping when cart subtotal >= $60.
 * Express shipping and self-pickup remain available as alternatives.
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
 * - Express shipping and self-pickup remain available
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
        $other_rates   = array();

        foreach ($rates as $rate_id => $rate) {
            if ($rate->method_id === 'free_shipping') {
                // Keep free shipping (will be placed first as default)
                $free_rates[$rate_id] = $rate;
            } elseif (
                stripos($rate->label, 'express') !== false ||
                $rate->method_id === 'local_pickup'
            ) {
                // Keep express shipping and self-pickup
                $other_rates[$rate_id] = $rate;
            }
            // Only flat rate / standard delivery is dropped
        }

        // Free shipping first (becomes default selected), then express + pickup
        if (!empty($free_rates)) {
            $rates = $free_rates + $other_rates;
        }
        // If no free shipping method is configured, don't remove anything
    }

    return $rates;
}
