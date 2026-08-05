<?php
/**
 * Shipping Rules
 *
 * Free delivery on this site comes from TWO independent places. Only one of
 * them is this file:
 *
 * 1. Cart subtotal >= $60 — WooCommerce's own Free Shipping method, gated by
 *    the "minimum order amount" set on the method in the shipping zone. This
 *    file does not create that rate; it only promotes it to the default and
 *    drops paid Standard Delivery once WooCommerce has offered it.
 *
 * 2. Omakase boxes — NOT handled here. The "omakase" shipping class has a $0
 *    flat-rate cost configured against Standard Delivery in the WooCommerce
 *    shipping zone, so an Omakase box ships free on its own at any price.
 *    Change that in WooCommerce -> Settings -> Shipping, not in this file.
 *
 * Consequence, verified live 2026-08-05 and intentional: an Omakase box ALONE
 * ships free ($50 box -> $0.00), but an Omakase box plus any non-omakase item
 * under $60 pays normal delivery ($56.50 cart -> $10.00), because flat rate
 * charges per shipping class and only the omakase class is zeroed.
 *
 * An earlier version of this file claimed "cart contains any omakase product
 * -> free shipping" and looped over the cart to detect it. That check could
 * never change the outcome: below $60 no free_shipping rate exists to promote,
 * and at or above $60 the subtotal condition already matched. It has been
 * removed rather than left looking load-bearing.
 *
 * @package AhHoCustom
 * @since 1.6.4
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Put free shipping first and drop paid standard delivery.
 *
 * Express and self-pickup are kept as alternatives. If the zone offers no
 * free_shipping rate, rates are returned untouched — never leave a customer
 * with nothing that can deliver to them.
 *
 * Shared with includes/hidden-link-free-shipping.php so the two callers cannot
 * drift apart. Defined here because this file loads first and is the more
 * foundational of the two.
 *
 * @param array $rates Shipping rates for the package.
 * @return array Rates, free-first, or unchanged.
 */
function ah_ho_prefer_free_rates($rates) {
    $free_rates  = array();
    $other_rates = array();

    foreach ($rates as $rate_id => $rate) {
        if ($rate->method_id === 'free_shipping') {
            $free_rates[$rate_id] = $rate;
        } elseif (
            stripos($rate->label, 'express') !== false ||
            $rate->method_id === 'local_pickup'
        ) {
            $other_rates[$rate_id] = $rate;
        }
        // Standard / flat-rate delivery is dropped.
    }

    return empty($free_rates) ? $rates : $free_rates + $other_rates;
}

/**
 * Make free shipping the default once WooCommerce has offered it (>= $60).
 *
 * @param array $rates Shipping rates for the package.
 * @param array $package Package data.
 * @return array Modified shipping rates.
 */
add_filter('woocommerce_package_rates', 'ah_ho_auto_free_shipping', 10, 2);

function ah_ho_auto_free_shipping($rates, $package) {
    if (!WC()->cart) {
        return $rates;
    }

    if (WC()->cart->get_subtotal() >= 60) {   // SGD, matches the method's minimum
        $rates = ah_ho_prefer_free_rates($rates);
    }

    return $rates;
}
