<?php
/**
 * Plugin Name: Ah Ho Checkout CSS Fix
 * Description: Fixes Order Summary text size on WooCommerce checkout page
 * Version: 1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_head', function() {
    if (is_checkout()) {
        echo '<style>
/* Fix Order Summary H3 text size on checkout */
.wc-block-components-product-metadata__description h3,
.wc-block-components-product-metadata__description h3 b {
    font-size: 14px !important;
    line-height: 1.4 !important;
    margin: 0 !important;
    font-weight: 600 !important;
}
</style>';
    }
}, 999);
