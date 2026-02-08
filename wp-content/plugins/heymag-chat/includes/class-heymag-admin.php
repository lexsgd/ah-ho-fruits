<?php
/**
 * HeyMag Admin Settings
 *
 * @package HeyMag_Chat
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin settings class
 */
class HeyMag_Admin {

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
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));

        // Register settings
        add_action('admin_init', array($this, 'register_settings'));

        // Enqueue admin scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));

        // Admin notices
        add_action('admin_notices', array($this, 'admin_notices'));

        // AJAX handlers
        add_action('wp_ajax_heymag_test_connection', array($this, 'ajax_test_connection'));
        add_action('wp_ajax_heymag_sync_products', array($this, 'ajax_sync_products'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            __('HeyMag Chat Settings', 'heymag-chat'),
            __('HeyMag Chat', 'heymag-chat'),
            'manage_options',
            'heymag-settings',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('heymag_settings', 'heymag_settings', array(
            'sanitize_callback' => array($this, 'sanitize_settings'),
        ));

        // Connection Section
        add_settings_section(
            'heymag_connection',
            __('Connection', 'heymag-chat'),
            array($this, 'render_connection_section'),
            'heymag-settings'
        );

        add_settings_field(
            'widget_token',
            __('Widget Token', 'heymag-chat'),
            array($this, 'render_widget_token_field'),
            'heymag-settings',
            'heymag_connection'
        );

        // Widget Section
        add_settings_section(
            'heymag_widget',
            __('Widget Settings', 'heymag-chat'),
            null,
            'heymag-settings'
        );

        add_settings_field(
            'widget_enabled',
            __('Enable Widget', 'heymag-chat'),
            array($this, 'render_checkbox_field'),
            'heymag-settings',
            'heymag_widget',
            array('field' => 'widget_enabled', 'label' => __('Show chat widget on your site', 'heymag-chat'))
        );

        add_settings_field(
            'position',
            __('Position', 'heymag-chat'),
            array($this, 'render_position_field'),
            'heymag-settings',
            'heymag_widget'
        );

        add_settings_field(
            'primary_color',
            __('Primary Color', 'heymag-chat'),
            array($this, 'render_color_field'),
            'heymag-settings',
            'heymag_widget'
        );

        add_settings_field(
            'button_text',
            __('Button Text', 'heymag-chat'),
            array($this, 'render_text_field'),
            'heymag-settings',
            'heymag_widget',
            array('field' => 'button_text', 'placeholder' => 'Chat with us')
        );

        add_settings_field(
            'welcome_message',
            __('Welcome Message', 'heymag-chat'),
            array($this, 'render_textarea_field'),
            'heymag-settings',
            'heymag_widget',
            array('field' => 'welcome_message', 'placeholder' => 'Hi! How can I help you today?')
        );

        add_settings_field(
            'show_on_mobile',
            __('Mobile Display', 'heymag-chat'),
            array($this, 'render_checkbox_field'),
            'heymag-settings',
            'heymag_widget',
            array('field' => 'show_on_mobile', 'label' => __('Show widget on mobile devices', 'heymag-chat'))
        );

        // WooCommerce Section (only if active)
        if (class_exists('WooCommerce')) {
            add_settings_section(
                'heymag_woocommerce',
                __('WooCommerce Sync', 'heymag-chat'),
                array($this, 'render_woocommerce_section'),
                'heymag-settings'
            );

            add_settings_field(
                'woocommerce_sync_enabled',
                __('Enable Sync', 'heymag-chat'),
                array($this, 'render_checkbox_field'),
                'heymag-settings',
                'heymag_woocommerce',
                array('field' => 'woocommerce_sync_enabled', 'label' => __('Automatically sync products to HeyMag', 'heymag-chat'))
            );
        }
    }

    /**
     * Sanitize settings
     */
    public function sanitize_settings($input) {
        $sanitized = array();

        // Widget token
        if (isset($input['widget_token'])) {
            $sanitized['widget_token'] = sanitize_text_field($input['widget_token']);
        }

        // Business ID
        if (isset($input['business_id'])) {
            $sanitized['business_id'] = sanitize_text_field($input['business_id']);
        }

        // Boolean fields
        $bool_fields = array(
            'widget_enabled',
            'show_on_mobile',
            'auto_open',
            'woocommerce_sync_enabled',
            'woocommerce_sync_descriptions',
            'woocommerce_sync_images',
            'woocommerce_sync_inventory',
            'woocommerce_sync_drafts',
        );

        foreach ($bool_fields as $field) {
            $sanitized[$field] = !empty($input[$field]);
        }

        // Position
        if (isset($input['position'])) {
            $positions = array('bottom-right', 'bottom-left', 'top-right', 'top-left');
            $sanitized['position'] = in_array($input['position'], $positions) ? $input['position'] : 'bottom-right';
        }

        // Color
        if (isset($input['primary_color'])) {
            $sanitized['primary_color'] = sanitize_hex_color($input['primary_color']) ?: '#2563EB';
        }

        // Text fields
        if (isset($input['button_text'])) {
            $sanitized['button_text'] = sanitize_text_field($input['button_text']);
        }

        if (isset($input['welcome_message'])) {
            $sanitized['welcome_message'] = sanitize_textarea_field($input['welcome_message']);
        }

        // Excluded pages
        if (isset($input['excluded_pages'])) {
            if (is_array($input['excluded_pages'])) {
                $sanitized['excluded_pages'] = array_map('sanitize_text_field', $input['excluded_pages']);
            } else {
                $sanitized['excluded_pages'] = array_filter(array_map('trim', explode("\n", $input['excluded_pages'])));
            }
        }

        // Auto open delay
        if (isset($input['auto_open_delay'])) {
            $sanitized['auto_open_delay'] = absint($input['auto_open_delay']);
        }

        return $sanitized;
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_scripts($hook) {
        if ('settings_page_heymag-settings' !== $hook) {
            return;
        }

        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');

        wp_enqueue_style(
            'heymag-admin',
            HEYMAG_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            HEYMAG_VERSION
        );

        wp_enqueue_script(
            'heymag-admin',
            HEYMAG_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'wp-color-picker'),
            HEYMAG_VERSION,
            true
        );

        wp_localize_script('heymag-admin', 'heymag_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('heymag_admin'),
            'strings' => array(
                'testing' => __('Testing connection...', 'heymag-chat'),
                'connected' => __('Connected successfully!', 'heymag-chat'),
                'error' => __('Connection failed', 'heymag-chat'),
                'syncing' => __('Syncing products...', 'heymag-chat'),
                'sync_complete' => __('Sync complete!', 'heymag-chat'),
            ),
        ));
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <form action="options.php" method="post">
                <?php
                settings_fields('heymag_settings');
                do_settings_sections('heymag-settings');
                submit_button(__('Save Settings', 'heymag-chat'));
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render connection section
     */
    public function render_connection_section() {
        $settings = HeyMag_Chat::get_settings();
        $is_configured = HeyMag_Chat::is_configured();
        ?>
        <div class="heymag-connection-status">
            <?php if ($is_configured): ?>
                <span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span>
                <?php _e('Connected to HeyMag', 'heymag-chat'); ?>
            <?php else: ?>
                <span class="dashicons dashicons-warning" style="color: #dba617;"></span>
                <?php _e('Not connected. Enter your widget token below.', 'heymag-chat'); ?>
            <?php endif; ?>

            <button type="button" id="heymag-test-connection" class="button button-secondary">
                <?php _e('Test Connection', 'heymag-chat'); ?>
            </button>
        </div>
        <p class="description">
            <?php printf(
                __('Get your widget token from your %sHeyMag dashboard%s under Channels > Web Widget.', 'heymag-chat'),
                '<a href="https://heymag.app/channels" target="_blank">',
                '</a>'
            ); ?>
        </p>
        <?php
    }

    /**
     * Render WooCommerce section
     */
    public function render_woocommerce_section() {
        $products_count = wp_count_posts('product')->publish ?? 0;
        $settings = HeyMag_Chat::get_settings();
        ?>
        <div class="heymag-woocommerce-status">
            <p>
                <strong><?php _e('Products:', 'heymag-chat'); ?></strong>
                <?php echo esc_html($products_count); ?> <?php _e('published products', 'heymag-chat'); ?>
            </p>

            <?php if (!empty($settings['business_id'])): ?>
                <button type="button" id="heymag-sync-products" class="button button-secondary">
                    <?php _e('Sync All Products Now', 'heymag-chat'); ?>
                </button>
                <span id="heymag-sync-status"></span>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render widget token field
     */
    public function render_widget_token_field() {
        $settings = HeyMag_Chat::get_settings();
        $value = isset($settings['widget_token']) ? $settings['widget_token'] : '';
        ?>
        <input type="text"
               name="heymag_settings[widget_token]"
               id="widget_token"
               value="<?php echo esc_attr($value); ?>"
               class="regular-text"
               placeholder="wgt_xxxxxxxxxxxxxxxx"
               pattern="wgt_[a-z0-9]+"
        >
        <?php
    }

    /**
     * Render checkbox field
     */
    public function render_checkbox_field($args) {
        $settings = HeyMag_Chat::get_settings();
        $field = $args['field'];
        $label = isset($args['label']) ? $args['label'] : '';
        $checked = !empty($settings[$field]);
        ?>
        <label>
            <input type="checkbox"
                   name="heymag_settings[<?php echo esc_attr($field); ?>]"
                   value="1"
                   <?php checked($checked); ?>
            >
            <?php echo esc_html($label); ?>
        </label>
        <?php
    }

    /**
     * Render position field
     */
    public function render_position_field() {
        $settings = HeyMag_Chat::get_settings();
        $value = isset($settings['position']) ? $settings['position'] : 'bottom-right';
        $positions = array(
            'bottom-right' => __('Bottom Right', 'heymag-chat'),
            'bottom-left' => __('Bottom Left', 'heymag-chat'),
            'top-right' => __('Top Right', 'heymag-chat'),
            'top-left' => __('Top Left', 'heymag-chat'),
        );
        ?>
        <select name="heymag_settings[position]" id="position">
            <?php foreach ($positions as $key => $label): ?>
                <option value="<?php echo esc_attr($key); ?>" <?php selected($value, $key); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    /**
     * Render color field
     */
    public function render_color_field() {
        $settings = HeyMag_Chat::get_settings();
        $value = isset($settings['primary_color']) ? $settings['primary_color'] : '#2563EB';
        ?>
        <input type="text"
               name="heymag_settings[primary_color]"
               id="primary_color"
               value="<?php echo esc_attr($value); ?>"
               class="heymag-color-picker"
               data-default-color="#2563EB"
        >
        <?php
    }

    /**
     * Render text field
     */
    public function render_text_field($args) {
        $settings = HeyMag_Chat::get_settings();
        $field = $args['field'];
        $placeholder = isset($args['placeholder']) ? $args['placeholder'] : '';
        $value = isset($settings[$field]) ? $settings[$field] : '';
        ?>
        <input type="text"
               name="heymag_settings[<?php echo esc_attr($field); ?>]"
               id="<?php echo esc_attr($field); ?>"
               value="<?php echo esc_attr($value); ?>"
               class="regular-text"
               placeholder="<?php echo esc_attr($placeholder); ?>"
        >
        <?php
    }

    /**
     * Render textarea field
     */
    public function render_textarea_field($args) {
        $settings = HeyMag_Chat::get_settings();
        $field = $args['field'];
        $placeholder = isset($args['placeholder']) ? $args['placeholder'] : '';
        $value = isset($settings[$field]) ? $settings[$field] : '';
        ?>
        <textarea name="heymag_settings[<?php echo esc_attr($field); ?>]"
                  id="<?php echo esc_attr($field); ?>"
                  class="large-text"
                  rows="3"
                  placeholder="<?php echo esc_attr($placeholder); ?>"
        ><?php echo esc_textarea($value); ?></textarea>
        <?php
    }

    /**
     * Admin notices
     */
    public function admin_notices() {
        // Show activation notice
        if (get_transient('heymag_activated')) {
            delete_transient('heymag_activated');
            ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <?php printf(
                        __('HeyMag Chat activated! %sConfigure your settings%s to get started.', 'heymag-chat'),
                        '<a href="' . admin_url('options-general.php?page=heymag-settings') . '">',
                        '</a>'
                    ); ?>
                </p>
            </div>
            <?php
        }

        // Show configuration reminder
        if (current_user_can('manage_options') && !HeyMag_Chat::is_configured()) {
            $screen = get_current_screen();
            if ($screen && $screen->id !== 'settings_page_heymag-settings') {
                ?>
                <div class="notice notice-warning is-dismissible">
                    <p>
                        <?php printf(
                            __('HeyMag Chat is not configured. %sAdd your widget token%s to enable the chat widget.', 'heymag-chat'),
                            '<a href="' . admin_url('options-general.php?page=heymag-settings') . '">',
                            '</a>'
                        ); ?>
                    </p>
                </div>
                <?php
            }
        }
    }

    /**
     * AJAX: Test connection
     */
    public function ajax_test_connection() {
        check_ajax_referer('heymag_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'heymag-chat')));
        }

        $api = new HeyMag_API();
        $result = $api->test_connection();

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success($result);
    }

    /**
     * AJAX: Sync products
     */
    public function ajax_sync_products() {
        check_ajax_referer('heymag_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'heymag-chat')));
        }

        if (!class_exists('HeyMag_WooCommerce')) {
            wp_send_json_error(array('message' => __('WooCommerce is not active', 'heymag-chat')));
        }

        $woo = HeyMag_WooCommerce::instance();
        $result = $woo->sync_all_products();

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success($result);
    }
}
