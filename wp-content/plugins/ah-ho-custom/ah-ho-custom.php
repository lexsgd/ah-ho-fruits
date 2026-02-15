<?php
/**
 * Plugin Name: Ah Ho Fruits Custom
 * Plugin URI: https://heymag.app
 * Description: Custom functionality for Ah Ho Fruits - WooCommerce custom order statuses
 * Version: 1.6.0
 * Author: Ah Ho Fruits
 * Author URI: https://heymag.app
 * Text Domain: ah-ho-custom
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * WC requires at least: 8.0
 * WC tested up to: 9.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants - v1.6.0 adds order fulfillment (partial deliveries & returns)
define('AH_HO_CUSTOM_VERSION', '1.6.0');
define('AH_HO_CUSTOM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AH_HO_CUSTOM_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Declare compatibility with WooCommerce features
 */
add_action('before_woocommerce_init', function() {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('orders_cache', __FILE__, true);
    }
});

/**
 * Check if WooCommerce is active
 */
function ah_ho_check_woocommerce() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function() {
            echo '<div class="error"><p><strong>Ah Ho Fruits Custom</strong> requires WooCommerce to be installed and active.</p></div>';
        });
        return false;
    }
    return true;
}

/**
 * Initialize plugin
 */
function ah_ho_custom_init() {
    if (!ah_ho_check_woocommerce()) {
        return;
    }

    // Include custom order statuses
    require_once AH_HO_CUSTOM_PLUGIN_DIR . 'includes/custom-order-statuses.php';

    // Include custom email notifications
    require_once AH_HO_CUSTOM_PLUGIN_DIR . 'includes/custom-emails.php';

    // Include salesperson functionality
    require_once AH_HO_CUSTOM_PLUGIN_DIR . 'includes/salesperson-roles.php';
    require_once AH_HO_CUSTOM_PLUGIN_DIR . 'includes/salesperson-settings.php';
    require_once AH_HO_CUSTOM_PLUGIN_DIR . 'includes/salesperson-attribution.php';
    require_once AH_HO_CUSTOM_PLUGIN_DIR . 'includes/salesperson-query-filters.php'; // HPOS compatible v1.4.0
    require_once AH_HO_CUSTOM_PLUGIN_DIR . 'includes/salesperson-dashboard.php';

    // Include wholesale pricing for B2B orders
    require_once AH_HO_CUSTOM_PLUGIN_DIR . 'includes/wholesale-pricing.php';

    // Include storeman product access restrictions
    require_once AH_HO_CUSTOM_PLUGIN_DIR . 'includes/storeman-product-access.php';

    // Include storeman quick stock update page
    require_once AH_HO_CUSTOM_PLUGIN_DIR . 'includes/storeman-inventory.php';

    // Include payment gateway settings (default to PayNow)
    require_once AH_HO_CUSTOM_PLUGIN_DIR . 'includes/payment-settings.php';

    // Include WhatsApp catalog generator
    require_once AH_HO_CUSTOM_PLUGIN_DIR . 'includes/catalog-generator.php';

    // Include delivery date field (checkout + admin)
    require_once AH_HO_CUSTOM_PLUGIN_DIR . 'includes/delivery-date-field.php';

    // Include order fulfillment (partial deliveries & item returns)
    require_once AH_HO_CUSTOM_PLUGIN_DIR . 'includes/order-fulfillment.php';

    // Include shop product ordering (pin Omakase Boxes first)
    require_once AH_HO_CUSTOM_PLUGIN_DIR . 'includes/shop-ordering.php';

}
add_action('plugins_loaded', 'ah_ho_custom_init');


/**
 * Activation hook
 */
function ah_ho_custom_activate() {
    // Load salesperson roles file first (needed for role registration)
    require_once plugin_dir_path(__FILE__) . 'includes/salesperson-roles.php';

    // Register salesperson and storeman roles directly
    ah_ho_register_salesperson_role();
    ah_ho_register_storeman_role();

    // Trigger other activation hooks
    do_action('ah_ho_custom_activate');

    // Flush rewrite rules
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'ah_ho_custom_activate');

/**
 * One-time migration: Make B2B (hidden) products visible in shop and search
 *
 * B2B products were previously set to catalog_visibility='hidden'. Now that
 * B2C customers can also buy carton products, make them visible everywhere.
 * Runs once on admin_init, then sets an option flag to prevent re-running.
 */
function ah_ho_migrate_b2b_visibility() {
    if (get_option('ah_ho_b2b_visibility_migrated')) {
        return;
    }

    if (!class_exists('WooCommerce')) {
        return;
    }

    $hidden_products = wc_get_products(array(
        'status'             => 'publish',
        'limit'              => -1,
        'catalog_visibility' => 'hidden',
    ));

    $count = 0;
    foreach ($hidden_products as $product) {
        $product->set_catalog_visibility('visible');
        $product->save();
        $count++;
    }

    update_option('ah_ho_b2b_visibility_migrated', current_time('mysql'));

    if ($count > 0) {
        error_log("Ah Ho Custom: Migrated {$count} hidden B2B products to visible.");
    }
}
add_action('admin_init', 'ah_ho_migrate_b2b_visibility');

/**
 * Deactivation hook
 */
function ah_ho_custom_deactivate() {
    // Trigger salesperson role cleanup
    do_action('ah_ho_custom_deactivate');

    // Flush rewrite rules
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'ah_ho_custom_deactivate');
