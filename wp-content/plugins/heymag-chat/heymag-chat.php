<?php
/**
 * Plugin Name: HeyMag - AI Chat Widget & WooCommerce Sync
 * Plugin URI: https://heymag.app/wordpress
 * Description: Add AI-powered customer chat to your WordPress site. Automatically sync WooCommerce products for smart product recommendations.
 * Version: 1.1.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: HeyMag
 * Author URI: https://heymag.app
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: heymag-chat
 * Domain Path: /languages
 *
 * WC requires at least: 6.0
 * WC tested up to: 10.4
 *
 * @package HeyMag_Chat
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin version
define('HEYMAG_VERSION', '1.1.0');
define('HEYMAG_PLUGIN_FILE', __FILE__);
define('HEYMAG_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('HEYMAG_PLUGIN_URL', plugin_dir_url(__FILE__));
define('HEYMAG_API_URL', 'https://heymag.app/api');

/**
 * Main plugin class
 */
final class HeyMag_Chat {

    /**
     * Plugin instance
     */
    private static $instance = null;

    /**
     * Get plugin instance
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Include required files
     */
    private function includes() {
        require_once HEYMAG_PLUGIN_DIR . 'includes/class-heymag-core.php';
        require_once HEYMAG_PLUGIN_DIR . 'includes/class-heymag-admin.php';
        require_once HEYMAG_PLUGIN_DIR . 'includes/class-heymag-api.php';
        require_once HEYMAG_PLUGIN_DIR . 'includes/class-heymag-widget.php';
        require_once HEYMAG_PLUGIN_DIR . 'includes/class-heymag-rest-api.php';
        require_once HEYMAG_PLUGIN_DIR . 'includes/class-heymag-setup-wizard.php';

        // WooCommerce integration (only if WooCommerce is active)
        if (class_exists('WooCommerce')) {
            require_once HEYMAG_PLUGIN_DIR . 'includes/class-heymag-woocommerce.php';
        }
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Activation/Deactivation
        register_activation_hook(HEYMAG_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(HEYMAG_PLUGIN_FILE, array($this, 'deactivate'));

        // Initialize components
        add_action('plugins_loaded', array($this, 'init'));

        // Load translations
        add_action('init', array($this, 'load_textdomain'));
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Set default options
        $default_settings = array(
            'widget_token' => '',
            'business_id' => '',
            'widget_enabled' => true,
            'show_on_mobile' => true,
            'auto_open' => false,
            'auto_open_delay' => 5,
            'position' => 'bottom-right',
            'button_text' => 'Chat with us',
            'primary_color' => '#2563EB',
            'welcome_message' => 'Hi! How can I help you today?',
            'excluded_pages' => array(),
            'woocommerce_sync_enabled' => true,
            'woocommerce_sync_descriptions' => true,
            'woocommerce_sync_images' => true,
            'woocommerce_sync_inventory' => true,
            'woocommerce_sync_drafts' => false,
            'woocommerce_sync_categories' => 'all',
        );

        if (!get_option('heymag_settings')) {
            add_option('heymag_settings', $default_settings);
        }

        // Set activation flag for admin notice
        set_transient('heymag_activated', true, 30);

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clean up scheduled events
        wp_clear_scheduled_hook('heymag_sync_products');

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Initialize plugin components
     */
    public function init() {
        // Initialize core
        HeyMag_Core::instance();

        // Initialize admin
        if (is_admin()) {
            HeyMag_Admin::instance();
            HeyMag_Setup_Wizard::instance();
        }

        // Initialize widget
        HeyMag_Widget::instance();

        // Initialize REST API endpoints
        HeyMag_REST_API::instance();

        // Initialize WooCommerce integration
        if (class_exists('WooCommerce') && class_exists('HeyMag_WooCommerce')) {
            HeyMag_WooCommerce::instance();
        }
    }

    /**
     * Load plugin text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'heymag-chat',
            false,
            dirname(plugin_basename(HEYMAG_PLUGIN_FILE)) . '/languages/'
        );
    }

    /**
     * Get plugin settings
     */
    public static function get_settings() {
        return get_option('heymag_settings', array());
    }

    /**
     * Update plugin settings
     */
    public static function update_settings($settings) {
        return update_option('heymag_settings', $settings);
    }

    /**
     * Check if widget is configured
     */
    public static function is_configured() {
        $settings = self::get_settings();
        return !empty($settings['widget_token']);
    }
}

/**
 * Declare WooCommerce feature compatibility (HPOS, Blocks, etc.)
 */
add_action('before_woocommerce_init', function () {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'custom_order_tables',
            __FILE__,
            true
        );
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'cart_checkout_blocks',
            __FILE__,
            true
        );
    }
});

/**
 * Get plugin instance
 */
function heymag_chat() {
    return HeyMag_Chat::instance();
}

// Initialize plugin
heymag_chat();
