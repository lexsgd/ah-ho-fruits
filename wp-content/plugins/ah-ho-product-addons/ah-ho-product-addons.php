<?php
/**
 * Plugin Name: Ah Ho Fruit - Product Add-ons
 * Description: Gift messages and product notes/remarks for customer customization
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * WC requires at least: 8.0
 * WC tested up to: 9.0
 * Author: Ah Ho Fruit
 * Text Domain: ah-ho-fruits
 */

defined( 'ABSPATH' ) || exit;

// Define plugin constants
define( 'AH_HO_ADDONS_VERSION', '1.0.0' );
define( 'AH_HO_ADDONS_PATH', plugin_dir_path( __FILE__ ) );
define( 'AH_HO_ADDONS_URL', plugin_dir_url( __FILE__ ) );

/**
 * Declare compatibility with WooCommerce features
 */
add_action( 'before_woocommerce_init', function() {
    if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
} );

/**
 * Check WooCommerce dependency and initialize plugin
 */
add_action( 'plugins_loaded', 'ah_ho_addons_init' );

function ah_ho_addons_init() {
    // Check if WooCommerce is active
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', 'ah_ho_addons_wc_missing_notice' );
        return;
    }

    // Load plugin classes
    require_once AH_HO_ADDONS_PATH . 'includes/class-admin-settings.php';
    require_once AH_HO_ADDONS_PATH . 'includes/class-frontend-display.php';
    require_once AH_HO_ADDONS_PATH . 'includes/class-cart-handler.php';
    require_once AH_HO_ADDONS_PATH . 'includes/class-order-handler.php';

    // Initialize
    new AH_Ho_Addons_Admin_Settings();
    new AH_Ho_Addons_Frontend_Display();
    new AH_Ho_Addons_Cart_Handler();
    new AH_Ho_Addons_Order_Handler();
}

/**
 * Display admin notice if WooCommerce is not active
 */
function ah_ho_addons_wc_missing_notice() {
    echo '<div class="error"><p>';
    echo '<strong>Ah Ho Product Add-ons</strong> requires WooCommerce to be installed and active.';
    echo '</p></div>';
}

/**
 * Enqueue frontend assets
 */
add_action( 'wp_enqueue_scripts', 'ah_ho_addons_enqueue_assets' );

function ah_ho_addons_enqueue_assets() {
    if ( is_product() ) {
        wp_enqueue_style(
            'ah-ho-addons-css',
            AH_HO_ADDONS_URL . 'assets/css/product-addons.css',
            [],
            AH_HO_ADDONS_VERSION
        );

        wp_enqueue_script(
            'ah-ho-addons-js',
            AH_HO_ADDONS_URL . 'assets/js/product-addons.js',
            ['jquery'],
            AH_HO_ADDONS_VERSION,
            true
        );
    }
}

/**
 * Add settings link on plugins page
 */
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'ah_ho_addons_settings_link' );

function ah_ho_addons_settings_link( $links ) {
    $settings_link = '<a href="' . admin_url( 'edit.php?post_type=product' ) . '">'
        . __( 'Configure Products', 'ah-ho-fruits' )
        . '</a>';
    array_unshift( $links, $settings_link );
    return $links;
}
