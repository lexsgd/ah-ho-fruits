<?php
/**
 * HeyMag Core Functionality
 *
 * @package HeyMag_Chat
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Core functionality class
 */
class HeyMag_Core {

    /**
     * Instance
     */
    private static $instance = null;

    /**
     * Get instance
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
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // REST API endpoints
        add_action('rest_api_init', array($this, 'register_rest_routes'));

        // Scheduled events
        add_action('heymag_sync_products', array($this, 'scheduled_product_sync'));

        // Plugin links
        add_filter('plugin_action_links_' . plugin_basename(HEYMAG_PLUGIN_FILE), array($this, 'plugin_action_links'));
    }

    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        // Status endpoint for HeyMag to ping
        register_rest_route('heymag/v1', '/status', array(
            'methods' => 'GET',
            'callback' => array($this, 'rest_status'),
            'permission_callback' => '__return_true',
        ));

        // Validate token endpoint
        register_rest_route('heymag/v1', '/validate', array(
            'methods' => 'POST',
            'callback' => array($this, 'rest_validate_token'),
            'permission_callback' => '__return_true',
        ));
    }

    /**
     * REST API: Status endpoint
     */
    public function rest_status($request) {
        $settings = HeyMag_Chat::get_settings();

        return rest_ensure_response(array(
            'status' => 'ok',
            'plugin_version' => HEYMAG_VERSION,
            'wordpress_version' => get_bloginfo('version'),
            'woocommerce_active' => class_exists('WooCommerce'),
            'woocommerce_version' => class_exists('WooCommerce') ? WC()->version : null,
            'widget_enabled' => !empty($settings['widget_enabled']),
            'is_configured' => HeyMag_Chat::is_configured(),
            'products_count' => class_exists('WooCommerce') ? $this->get_products_count() : 0,
            'site_url' => get_site_url(),
            'site_name' => get_bloginfo('name'),
        ));
    }

    /**
     * REST API: Validate token
     */
    public function rest_validate_token($request) {
        $token = $request->get_param('token');

        if (empty($token)) {
            return new WP_Error('missing_token', 'Token is required', array('status' => 400));
        }

        // Validate token format
        if (!preg_match('/^wgt_[a-z0-9]+$/', $token)) {
            return new WP_Error('invalid_format', 'Invalid token format', array('status' => 400));
        }

        // Validate with HeyMag API
        $api = new HeyMag_API();
        $result = $api->validate_token($token);

        if (is_wp_error($result)) {
            return $result;
        }

        return rest_ensure_response(array(
            'valid' => true,
            'business_id' => $result['business_id'] ?? null,
            'business_name' => $result['business_name'] ?? null,
        ));
    }

    /**
     * Get WooCommerce products count
     */
    private function get_products_count() {
        if (!class_exists('WooCommerce')) {
            return 0;
        }

        $count = wp_count_posts('product');
        return isset($count->publish) ? (int) $count->publish : 0;
    }

    /**
     * Scheduled product sync
     */
    public function scheduled_product_sync() {
        if (!class_exists('HeyMag_WooCommerce')) {
            return;
        }

        $woo = HeyMag_WooCommerce::instance();
        $woo->sync_all_products();
    }

    /**
     * Add plugin action links
     */
    public function plugin_action_links($links) {
        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            admin_url('options-general.php?page=heymag-settings'),
            __('Settings', 'heymag-chat')
        );

        array_unshift($links, $settings_link);

        return $links;
    }

    /**
     * Log message (for debugging)
     */
    public static function log($message, $level = 'info') {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[HeyMag] [' . strtoupper($level) . '] ' . $message);
        }
    }

    /**
     * Get site info for API calls
     */
    public static function get_site_info() {
        return array(
            'site_url' => get_site_url(),
            'site_name' => get_bloginfo('name'),
            'plugin_version' => HEYMAG_VERSION,
            'wordpress_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'woocommerce_version' => class_exists('WooCommerce') ? WC()->version : null,
            'timezone' => wp_timezone_string(),
            'locale' => get_locale(),
        );
    }
}
