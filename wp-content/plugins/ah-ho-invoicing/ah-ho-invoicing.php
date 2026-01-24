<?php
/**
 * Plugin Name: Ah Ho Fruits - Invoicing & Packing Lists
 * Plugin URI: https://heymag.app
 * Description: Custom PDF invoices, packing lists, and delivery orders for Ah Ho Fruits. Features: Sequential invoice numbering, consolidated packing lists sorted by postal code + delivery date, customer notes highlighting (allergies/gifts).
 * Version: 1.0.0
 * Author: Ah Ho Fruits
 * Author URI: https://heymag.app
 * Text Domain: ah-ho-invoicing
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * WC requires at least: 8.0
 * WC tested up to: 9.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('AH_HO_INVOICING_VERSION', '1.0.0');
define('AH_HO_INVOICING_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AH_HO_INVOICING_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AH_HO_INVOICING_CACHE_DIR', WP_CONTENT_DIR . '/pdf-cache/');

/**
 * Declare compatibility with WooCommerce features (HPOS)
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
function ah_ho_invoicing_check_woocommerce() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function() {
            echo '<div class="error"><p><strong>Ah Ho Fruits - Invoicing & Packing Lists</strong> requires WooCommerce to be installed and active.</p></div>';
        });
        return false;
    }
    return true;
}

/**
 * Initialize plugin
 */
function ah_ho_invoicing_init() {
    if (!ah_ho_invoicing_check_woocommerce()) {
        return;
    }

    // Load Composer autoloader (for Dompdf)
    $autoload_file = AH_HO_INVOICING_PLUGIN_DIR . 'vendor/autoload.php';
    if (file_exists($autoload_file)) {
        require_once $autoload_file;
    } else {
        add_action('admin_notices', function() {
            echo '<div class="error"><p><strong>Ah Ho Invoicing:</strong> Dompdf library not found. Please run <code>composer require dompdf/dompdf</code> in the plugin directory.</p></div>';
        });
        return;
    }

    // Load plugin classes
    require_once AH_HO_INVOICING_PLUGIN_DIR . 'includes/class-pdf-generator.php';
    require_once AH_HO_INVOICING_PLUGIN_DIR . 'includes/class-invoice.php';
    require_once AH_HO_INVOICING_PLUGIN_DIR . 'includes/class-cache-manager.php';
    require_once AH_HO_INVOICING_PLUGIN_DIR . 'includes/class-metabox.php';

    // Initialize classes
    AH_HO_PDF_Generator::init();
    AH_HO_Cache_Manager::init();
    AH_HO_Metabox::init();
}
add_action('plugins_loaded', 'ah_ho_invoicing_init');

/**
 * Activation hook
 */
function ah_ho_invoicing_activate() {
    // Create cache directory
    if (!file_exists(AH_HO_INVOICING_CACHE_DIR)) {
        wp_mkdir_p(AH_HO_INVOICING_CACHE_DIR);
    }

    // Add .htaccess to deny direct access to PDFs
    $htaccess = AH_HO_INVOICING_CACHE_DIR . '.htaccess';
    if (!file_exists($htaccess)) {
        file_put_contents($htaccess, "Deny from all\n");
    }

    // Schedule cleanup cron job (daily)
    if (!wp_next_scheduled('ah_ho_invoicing_cleanup_old_pdfs')) {
        wp_schedule_event(time(), 'daily', 'ah_ho_invoicing_cleanup_old_pdfs');
    }

    // Set default options
    if (get_option('ah_ho_company_name') === false) {
        update_option('ah_ho_company_name', 'Ah Ho Fruits Pte Ltd');
    }
    if (get_option('ah_ho_company_address') === false) {
        update_option('ah_ho_company_address', '123 Fruit Lane, Singapore 123456');
    }
    if (get_option('ah_ho_company_phone') === false) {
        update_option('ah_ho_company_phone', '+65 1234 5678');
    }
    if (get_option('ah_ho_company_email') === false) {
        update_option('ah_ho_company_email', 'hello@ahhofruits.com');
    }
    if (get_option('ah_ho_company_uen') === false) {
        update_option('ah_ho_company_uen', '201234567A');
    }
    if (get_option('ah_ho_company_gst') === false) {
        update_option('ah_ho_company_gst', 'M12345678X');
    }
    if (get_option('ah_ho_bank_name') === false) {
        update_option('ah_ho_bank_name', 'DBS Bank');
    }
    if (get_option('ah_ho_bank_account') === false) {
        update_option('ah_ho_bank_account', '123-456-789-0');
    }

    // Flush rewrite rules
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'ah_ho_invoicing_activate');

/**
 * Deactivation hook
 */
function ah_ho_invoicing_deactivate() {
    // Clear scheduled cron
    wp_clear_scheduled_hook('ah_ho_invoicing_cleanup_old_pdfs');

    // Flush rewrite rules
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'ah_ho_invoicing_deactivate');

/**
 * Cleanup old PDFs (runs daily via WP-Cron)
 */
add_action('ah_ho_invoicing_cleanup_old_pdfs', 'ah_ho_invoicing_cleanup_old_pdfs');
function ah_ho_invoicing_cleanup_old_pdfs() {
    $cache_dir = AH_HO_INVOICING_CACHE_DIR;
    $files = glob($cache_dir . '*.pdf');

    // Delete PDFs older than 30 days
    $cutoff_time = time() - (30 * DAY_IN_SECONDS);

    $deleted_count = 0;
    foreach ($files as $file) {
        if (filemtime($file) < $cutoff_time) {
            unlink($file);
            $deleted_count++;
        }
    }

    // Log cleanup
    if ($deleted_count > 0) {
        error_log("Ah Ho Invoicing: Cleaned up {$deleted_count} old PDFs. Remaining: " . count(glob($cache_dir . '*.pdf')));
    }
}
