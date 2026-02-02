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

// Define plugin constants
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

    // Include payment gateway settings (default to PayNow)
    require_once AH_HO_CUSTOM_PLUGIN_DIR . 'includes/payment-settings.php';

    // Include WhatsApp catalog generator
    require_once AH_HO_CUSTOM_PLUGIN_DIR . 'includes/catalog-generator.php';
}
add_action('plugins_loaded', 'ah_ho_custom_init');

/**
 * Activation hook
 */
function ah_ho_custom_activate() {
    // Load salesperson roles file first (needed for role registration)
    require_once plugin_dir_path(__FILE__) . 'includes/salesperson-roles.php';

    // Register salesperson role directly
    ah_ho_register_salesperson_role();

    // Trigger other activation hooks
    do_action('ah_ho_custom_activate');

    // Flush rewrite rules
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'ah_ho_custom_activate');

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
