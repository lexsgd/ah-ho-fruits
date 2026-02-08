<?php
/**
 * HeyMag Widget Injection
 *
 * @package HeyMag_Chat
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Widget injection class
 */
class HeyMag_Widget {

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
        // Inject widget in footer
        add_action('wp_footer', array($this, 'inject_widget'), 99);

        // Add preconnect hints
        add_action('wp_head', array($this, 'add_preconnect'), 5);
    }

    /**
     * Check if widget should be shown
     *
     * @return bool
     */
    public function should_show_widget() {
        $settings = HeyMag_Chat::get_settings();

        // Check if enabled
        if (empty($settings['widget_enabled'])) {
            return false;
        }

        // Check token exists
        if (empty($settings['widget_token'])) {
            return false;
        }

        // Don't show in admin
        if (is_admin()) {
            return false;
        }

        // Don't show on login page
        if (in_array($GLOBALS['pagenow'], array('wp-login.php', 'wp-register.php'))) {
            return false;
        }

        // Check excluded pages
        $excluded = isset($settings['excluded_pages']) ? $settings['excluded_pages'] : array();
        if (!empty($excluded)) {
            $current_url = $_SERVER['REQUEST_URI'];

            foreach ($excluded as $pattern) {
                $pattern = trim($pattern);
                if (empty($pattern)) {
                    continue;
                }

                // Support wildcards
                if (fnmatch($pattern, $current_url)) {
                    return false;
                }

                // Exact match
                if ($current_url === $pattern) {
                    return false;
                }
            }
        }

        // Check mobile
        if (empty($settings['show_on_mobile']) && wp_is_mobile()) {
            return false;
        }

        return true;
    }

    /**
     * Inject widget script
     */
    public function inject_widget() {
        if (!$this->should_show_widget()) {
            return;
        }

        $settings = HeyMag_Chat::get_settings();

        $config = array(
            'token' => sanitize_text_field($settings['widget_token']),
            'position' => sanitize_text_field($settings['position'] ?? 'bottom-right'),
            'primaryColor' => $this->sanitize_hex_color($settings['primary_color'] ?? '#2563EB'),
            'buttonText' => sanitize_text_field($settings['button_text'] ?? 'Chat with us'),
            'welcomeMessage' => sanitize_textarea_field($settings['welcome_message'] ?? 'Hi! How can I help you today?'),
            'showOnMobile' => (bool) ($settings['show_on_mobile'] ?? true),
            'autoOpen' => (bool) ($settings['auto_open'] ?? false),
            'autoOpenDelay' => absint($settings['auto_open_delay'] ?? 5),
        );

        // Add WooCommerce context if on product page
        if (function_exists('is_product') && is_product()) {
            global $product;
            if ($product instanceof WC_Product) {
                $config['pageContext'] = array(
                    'type' => 'product',
                    'productId' => $product->get_id(),
                    'productName' => $product->get_name(),
                    'productPrice' => $product->get_price(),
                    'productUrl' => $product->get_permalink(),
                    'productImage' => wp_get_attachment_url($product->get_image_id()),
                );
            }
        }

        // Add cart context if WooCommerce
        if (function_exists('WC') && WC()->cart) {
            $config['cartContext'] = array(
                'itemCount' => WC()->cart->get_cart_contents_count(),
                'total' => WC()->cart->get_cart_contents_total(),
            );
        }

        ?>
        <script>
        (function(w, d, s, o, f) {
            w['HeyMagWidget'] = o;
            w[o] = w[o] || function() {
                (w[o].q = w[o].q || []).push(arguments);
            };
            var js = d.createElement(s);
            var fjs = d.getElementsByTagName(s)[0];
            js.id = o;
            js.src = f;
            js.async = true;
            if (fjs && fjs.parentNode) {
                fjs.parentNode.insertBefore(js, fjs);
            }
        })(window, document, 'script', 'heymag-widget', 'https://heymag.app/widget.js');

        window['heymag-widget']('init', <?php echo wp_json_encode($config); ?>);
        </script>
        <?php
    }

    /**
     * Add preconnect hints for faster loading
     */
    public function add_preconnect() {
        if (!$this->should_show_widget()) {
            return;
        }
        ?>
        <link rel="preconnect" href="https://heymag.app" crossorigin>
        <link rel="dns-prefetch" href="https://heymag.app">
        <?php
    }

    /**
     * Sanitize hex color
     *
     * @param string $color Color to sanitize
     * @return string Sanitized color
     */
    private function sanitize_hex_color($color) {
        if (preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
            return $color;
        }
        return '#2563EB'; // Default blue
    }
}
