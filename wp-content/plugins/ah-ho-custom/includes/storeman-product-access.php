<?php
/**
 * Storeman Product Access Control
 *
 * Restricts storeman product editing to inventory/stock fields only.
 * Hides pricing, description, and other product tabs.
 *
 * @package AhHoCustom
 * @since 1.6.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Check if current user is a storeman
 */
function ah_ho_is_current_user_storeman() {
    $current_user = wp_get_current_user();
    if (!$current_user || !$current_user->ID) {
        return false;
    }
    return in_array('ah_ho_storeman', (array) $current_user->roles);
}

/**
 * Remove product data tabs that storeman doesn't need
 * Keep only: Inventory tab
 */
add_filter('woocommerce_product_data_tabs', 'ah_ho_storeman_product_tabs', 99);

function ah_ho_storeman_product_tabs($tabs) {
    if (!ah_ho_is_current_user_storeman()) {
        return $tabs;
    }

    // Only keep inventory tab
    $allowed_tabs = array('inventory');

    foreach ($tabs as $key => $tab) {
        if (!in_array($key, $allowed_tabs)) {
            unset($tabs[$key]);
        }
    }

    return $tabs;
}

/**
 * Hide product fields storeman doesn't need via CSS
 * This hides pricing, categories, tags, etc. on the product edit screen
 */
add_action('admin_head', 'ah_ho_storeman_hide_product_fields');

function ah_ho_storeman_hide_product_fields() {
    if (!ah_ho_is_current_user_storeman()) {
        return;
    }

    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 'product') {
        return;
    }

    ?>
    <style>
        /* Hide pricing fields */
        .pricing,
        ._regular_price_field,
        ._sale_price_field,
        ._wholesale_price_field {
            display: none !important;
        }
        /* Hide product type selector */
        #product-type {
            pointer-events: none;
            opacity: 0.5;
        }
        /* Hide publish/visibility controls (storeman should not unpublish) */
        #misc-publishing-actions .misc-pub-visibility,
        #misc-publishing-actions .misc-pub-post-status {
            display: none !important;
        }
        /* Hide description editor - storeman only manages stock */
        #postdivrich,
        #woocommerce-product-data .wc-tabs-back {
            /* Keep visible but read-only appearance */
        }
        /* Hide product short description */
        #postexcerpt {
            display: none !important;
        }
    </style>
    <?php
}

/**
 * Prevent storeman from modifying product price fields
 * Even if they bypass CSS, this server-side check prevents saving price changes
 */
add_action('woocommerce_process_product_meta', 'ah_ho_storeman_protect_price_fields', 1, 2);

function ah_ho_storeman_protect_price_fields($post_id, $post) {
    if (!ah_ho_is_current_user_storeman()) {
        return;
    }

    // Get existing prices before WooCommerce saves new ones
    $product = wc_get_product($post_id);
    if (!$product) {
        return;
    }

    $original_regular_price = $product->get_regular_price();
    $original_sale_price = $product->get_sale_price();
    $original_wholesale_price = $product->get_meta('_wholesale_price');

    // After WooCommerce saves, restore original prices
    add_action('woocommerce_process_product_meta', function($id) use ($post_id, $original_regular_price, $original_sale_price, $original_wholesale_price) {
        if ($id !== $post_id) {
            return;
        }
        $prod = wc_get_product($id);
        if ($prod) {
            $prod->set_regular_price($original_regular_price);
            $prod->set_sale_price($original_sale_price);
            $prod->update_meta_data('_wholesale_price', $original_wholesale_price);
            $prod->save();
        }
    }, 999);
}
