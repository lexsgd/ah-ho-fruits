<?php
/**
 * HeyMag Custom REST API Endpoints
 *
 * Provides endpoints for:
 * - Customer phone/email search (for AI chat identity matching)
 * - Store info (currency, timezone, WC version)
 * - Health check
 *
 * @package HeyMag_Chat
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class HeyMag_REST_API {

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
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    /**
     * Register REST API routes
     */
    public function register_routes() {
        $namespace = 'heymag/v1';

        // Customer search
        register_rest_route($namespace, '/customers/search', array(
            'methods' => 'GET',
            'callback' => array($this, 'search_customers'),
            'permission_callback' => array($this, 'verify_api_key'),
            'args' => array(
                'phone' => array(
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'email' => array(
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_email',
                ),
            ),
        ));

        // Store info
        register_rest_route($namespace, '/store/info', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_store_info'),
            'permission_callback' => array($this, 'verify_api_key'),
        ));

        // Health check
        register_rest_route($namespace, '/health', array(
            'methods' => 'GET',
            'callback' => array($this, 'health_check'),
            'permission_callback' => '__return_true',
        ));
    }

    /**
     * Verify API key from request header
     *
     * @param WP_REST_Request $request
     * @return bool
     */
    public function verify_api_key($request) {
        $api_key = $request->get_header('X-HeyMag-API-Key');
        if (empty($api_key)) {
            return false;
        }

        $settings = HeyMag_Chat::get_settings();
        $stored_key = isset($settings['api_key']) ? $settings['api_key'] : '';

        // Also accept the WC consumer key as fallback
        $wc_key = isset($settings['wc_consumer_key']) ? $settings['wc_consumer_key'] : '';

        return !empty($stored_key) && hash_equals($stored_key, $api_key)
            || !empty($wc_key) && hash_equals($wc_key, $api_key);
    }

    /**
     * Search customers by phone or email
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function search_customers($request) {
        $phone = $request->get_param('phone');
        $email = $request->get_param('email');

        if (empty($phone) && empty($email)) {
            return new WP_REST_Response(
                array('error' => 'Provide phone or email parameter'),
                400
            );
        }

        if (!class_exists('WooCommerce')) {
            return new WP_REST_Response(
                array('error' => 'WooCommerce not active'),
                400
            );
        }

        // Search by phone
        if (!empty($phone)) {
            $phone_clean = preg_replace('/[^0-9+]/', '', $phone);

            $users = get_users(array(
                'meta_query' => array(
                    array(
                        'key' => 'billing_phone',
                        'value' => $phone_clean,
                        'compare' => 'LIKE',
                    ),
                ),
                'number' => 1,
            ));

            if (!empty($users)) {
                return new WP_REST_Response(array(
                    'customer' => $this->format_customer($users[0]),
                ));
            }

            // Try without country code prefix
            if (strlen($phone_clean) > 8) {
                $phone_suffix = substr($phone_clean, -8);
                $users = get_users(array(
                    'meta_query' => array(
                        array(
                            'key' => 'billing_phone',
                            'value' => $phone_suffix,
                            'compare' => 'LIKE',
                        ),
                    ),
                    'number' => 1,
                ));

                if (!empty($users)) {
                    return new WP_REST_Response(array(
                        'customer' => $this->format_customer($users[0]),
                    ));
                }
            }
        }

        // Search by email
        if (!empty($email)) {
            $user = get_user_by('email', $email);
            if ($user) {
                return new WP_REST_Response(array(
                    'customer' => $this->format_customer($user),
                ));
            }
        }

        return new WP_REST_Response(array(
            'customer' => null,
            'message' => 'No customer found',
        ));
    }

    /**
     * Get store info
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_store_info($request) {
        $info = array(
            'name' => get_bloginfo('name'),
            'url' => get_site_url(),
            'wordpress_version' => get_bloginfo('version'),
            'plugin_version' => HEYMAG_VERSION,
        );

        if (class_exists('WooCommerce')) {
            $info['wc_version'] = WC()->version;
            $info['currency'] = get_woocommerce_currency();
            $info['currency_symbol'] = get_woocommerce_currency_symbol();
            $info['timezone'] = wc_timezone_string();
            $info['weight_unit'] = get_option('woocommerce_weight_unit');
            $info['dimension_unit'] = get_option('woocommerce_dimension_unit');

            // Product counts
            $counts = wp_count_posts('product');
            $info['products_count'] = isset($counts->publish) ? absint($counts->publish) : 0;

            // Order count (recent 30 days)
            $info['recent_orders_count'] = absint(wc_orders_count('processing') + wc_orders_count('completed'));
        }

        return new WP_REST_Response($info);
    }

    /**
     * Health check endpoint
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function health_check($request) {
        $settings = HeyMag_Chat::get_settings();

        return new WP_REST_Response(array(
            'status' => 'ok',
            'plugin_version' => HEYMAG_VERSION,
            'woocommerce_active' => class_exists('WooCommerce'),
            'configured' => !empty($settings['widget_token']) || !empty($settings['business_id']),
            'timestamp' => current_time('c'),
        ));
    }

    /**
     * Format customer data for response
     *
     * @param WP_User $user
     * @return array
     */
    private function format_customer($user) {
        $customer_id = $user->ID;

        // Get WC customer data
        $orders_count = 0;
        $total_spent = '0';
        $last_order_date = null;

        if (class_exists('WC_Customer')) {
            try {
                $wc_customer = new WC_Customer($customer_id);
                $orders_count = $wc_customer->get_order_count();
                $total_spent = $wc_customer->get_total_spent();
                $last_order = $wc_customer->get_last_order();
                if ($last_order) {
                    $last_order_date = $last_order->get_date_created()
                        ? $last_order->get_date_created()->date('c')
                        : null;
                }
            } catch (Exception $e) {
                // Silently continue with defaults
            }
        }

        return array(
            'id' => $customer_id,
            'name' => trim(
                get_user_meta($customer_id, 'billing_first_name', true) . ' ' .
                get_user_meta($customer_id, 'billing_last_name', true)
            ),
            'email' => $user->user_email,
            'phone' => get_user_meta($customer_id, 'billing_phone', true),
            'orders_count' => absint($orders_count),
            'total_spent' => $total_spent,
            'last_order_date' => $last_order_date,
        );
    }
}
