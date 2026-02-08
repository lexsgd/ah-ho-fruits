<?php
/**
 * HeyMag API Communication
 *
 * @package HeyMag_Chat
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * API communication class
 */
class HeyMag_API {

    /**
     * API base URL
     */
    private $api_base;

    /**
     * Constructor
     */
    public function __construct() {
        $this->api_base = HEYMAG_API_URL;
    }

    /**
     * Validate widget token with HeyMag
     *
     * @param string $token Widget token to validate
     * @return array|WP_Error Validation result or error
     */
    public function validate_token($token) {
        $url = $this->api_base . '/widget/validate';

        $response = wp_remote_get($url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'X-Widget-Token' => $token,
            ),
            'timeout' => 15,
        ));

        if (is_wp_error($response)) {
            HeyMag_Core::log('Token validation failed: ' . $response->get_error_message(), 'error');
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($code !== 200) {
            return new WP_Error(
                'validation_failed',
                isset($data['error']) ? $data['error'] : 'Token validation failed',
                array('status' => $code)
            );
        }

        return $data;
    }

    /**
     * Send webhook to HeyMag
     *
     * @param string $topic Webhook topic (e.g., 'product.updated')
     * @param array $data Webhook payload data
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public function send_webhook($topic, $data) {
        $settings = HeyMag_Chat::get_settings();

        if (empty($settings['business_id'])) {
            return new WP_Error('no_business_id', 'Business ID not configured');
        }

        $url = $this->api_base . '/knowledge/sync/webhook/wordpress';

        $action = $this->get_action_from_topic($topic);

        $payload = array(
            'topic' => $topic,
            'action' => $action,
            'source' => 'wordpress',
            'businessId' => $settings['business_id'],
            'timestamp' => gmdate('c'),
            'product' => $data,
        );

        $response = wp_remote_post($url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'X-Business-ID' => $settings['business_id'],
                'X-HeyMag-Source' => 'wordpress-plugin',
                'X-HeyMag-Version' => HEYMAG_VERSION,
            ),
            'body' => wp_json_encode($payload),
            'timeout' => 30,
            'sslverify' => true,
        ));

        if (is_wp_error($response)) {
            HeyMag_Core::log('Webhook failed: ' . $response->get_error_message(), 'error');
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);

        if ($code < 200 || $code >= 300) {
            $body = wp_remote_retrieve_body($response);
            HeyMag_Core::log('Webhook returned ' . $code . ': ' . $body, 'error');
            return new WP_Error('webhook_failed', 'Webhook returned status ' . $code);
        }

        HeyMag_Core::log('Webhook sent: ' . $topic, 'info');
        return true;
    }

    /**
     * Test connection with HeyMag
     *
     * @return array|WP_Error Connection test result
     */
    public function test_connection() {
        $settings = HeyMag_Chat::get_settings();

        if (empty($settings['widget_token'])) {
            return new WP_Error('no_token', 'Widget token not configured');
        }

        $validation = $this->validate_token($settings['widget_token']);

        if (is_wp_error($validation)) {
            return $validation;
        }

        return array(
            'connected' => true,
            'business_id' => $validation['business_id'] ?? null,
            'business_name' => $validation['business_name'] ?? null,
            'timestamp' => current_time('mysql'),
        );
    }

    /**
     * Register site with HeyMag
     *
     * @return array|WP_Error Registration result
     */
    public function register_site() {
        $settings = HeyMag_Chat::get_settings();

        if (empty($settings['widget_token'])) {
            return new WP_Error('no_token', 'Widget token not configured');
        }

        $url = $this->api_base . '/marketplace/wordpress/register';

        $site_info = HeyMag_Core::get_site_info();
        $site_info['widget_token'] = $settings['widget_token'];
        $site_info['has_woocommerce'] = class_exists('WooCommerce');

        if (class_exists('WooCommerce')) {
            $site_info['products_count'] = wp_count_posts('product')->publish ?? 0;
        }

        $response = wp_remote_post($url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'X-Widget-Token' => $settings['widget_token'],
            ),
            'body' => wp_json_encode($site_info),
            'timeout' => 30,
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($code < 200 || $code >= 300) {
            return new WP_Error(
                'registration_failed',
                isset($data['error']) ? $data['error'] : 'Registration failed'
            );
        }

        // Store business ID if returned
        if (!empty($data['business_id'])) {
            $settings['business_id'] = $data['business_id'];
            HeyMag_Chat::update_settings($settings);
        }

        return $data;
    }

    /**
     * Get action from topic
     *
     * @param string $topic Webhook topic
     * @return string Action type
     */
    private function get_action_from_topic($topic) {
        $map = array(
            'product.created' => 'created',
            'product.updated' => 'updated',
            'product.deleted' => 'deleted',
            'product.restored' => 'restored',
        );

        return isset($map[$topic]) ? $map[$topic] : 'updated';
    }
}
