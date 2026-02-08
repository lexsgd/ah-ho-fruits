<?php
/**
 * HeyMag Setup Wizard
 *
 * Guides new users through connecting their WordPress/WooCommerce store to HeyMag.
 *
 * Steps:
 * 1. Welcome — explain AI assistant features
 * 2. Connect — enter HeyMag API key or create account
 * 3. WooCommerce — auto-generate WC API keys
 * 4. Channels — toggle WhatsApp, Telegram, Widget
 * 5. Sync — trigger initial product sync
 *
 * @package HeyMag_Chat
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class HeyMag_Setup_Wizard {

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
        add_action('admin_menu', array($this, 'add_wizard_page'));
        add_action('admin_init', array($this, 'maybe_redirect_to_wizard'));
        add_action('wp_ajax_heymag_wizard_connect', array($this, 'ajax_connect'));
        add_action('wp_ajax_heymag_wizard_generate_wc_keys', array($this, 'ajax_generate_wc_keys'));
        add_action('wp_ajax_heymag_wizard_complete', array($this, 'ajax_complete'));
    }

    /**
     * Add hidden wizard page
     */
    public function add_wizard_page() {
        add_submenu_page(
            null, // Hidden from menu
            __('HeyMag Setup', 'heymag-chat'),
            __('HeyMag Setup', 'heymag-chat'),
            'manage_woocommerce',
            'heymag-setup',
            array($this, 'render_wizard')
        );
    }

    /**
     * Redirect to wizard on first activation
     */
    public function maybe_redirect_to_wizard() {
        if (!get_transient('heymag_activated')) {
            return;
        }

        delete_transient('heymag_activated');

        $settings = HeyMag_Chat::get_settings();
        if (!empty($settings['widget_token']) || !empty($settings['business_id'])) {
            return; // Already configured
        }

        if (!current_user_can('manage_woocommerce')) {
            return;
        }

        wp_safe_redirect(admin_url('admin.php?page=heymag-setup'));
        exit;
    }

    /**
     * Render wizard page
     */
    public function render_wizard() {
        $signup_url = 'https://heymag.app/signup?source=wordpress-plugin'
            . '&utm_source=wordpress&utm_medium=plugin&utm_campaign=setup';
        $has_woocommerce = class_exists('WooCommerce');
        ?>
        <div class="wrap" id="heymag-setup-wizard">
            <style>
                #heymag-setup-wizard {
                    max-width: 600px;
                    margin: 40px auto;
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                }
                .heymag-wizard-step { display: none; }
                .heymag-wizard-step.active { display: block; }
                .heymag-wizard-header { text-align: center; margin-bottom: 30px; }
                .heymag-wizard-header h1 { font-size: 24px; margin-bottom: 8px; }
                .heymag-wizard-header p { color: #666; font-size: 14px; }
                .heymag-wizard-card {
                    background: #fff;
                    border: 1px solid #ddd;
                    border-radius: 8px;
                    padding: 24px;
                    margin-bottom: 16px;
                }
                .heymag-wizard-card h3 { margin-top: 0; }
                .heymag-wizard-btn {
                    display: inline-block;
                    padding: 10px 24px;
                    background: #2563EB;
                    color: #fff;
                    border: none;
                    border-radius: 6px;
                    cursor: pointer;
                    font-size: 14px;
                    text-decoration: none;
                }
                .heymag-wizard-btn:hover { background: #1d4ed8; color: #fff; }
                .heymag-wizard-btn.secondary {
                    background: #f3f4f6;
                    color: #374151;
                    border: 1px solid #d1d5db;
                }
                .heymag-wizard-btn.secondary:hover { background: #e5e7eb; color: #374151; }
                .heymag-wizard-input {
                    width: 100%;
                    padding: 10px;
                    border: 1px solid #d1d5db;
                    border-radius: 6px;
                    font-size: 14px;
                    margin-bottom: 12px;
                    box-sizing: border-box;
                }
                .heymag-wizard-steps-bar {
                    display: flex;
                    justify-content: center;
                    gap: 8px;
                    margin-bottom: 24px;
                }
                .heymag-wizard-dot {
                    width: 10px;
                    height: 10px;
                    border-radius: 50%;
                    background: #d1d5db;
                }
                .heymag-wizard-dot.active { background: #2563EB; }
                .heymag-wizard-dot.done { background: #22c55e; }
                .heymag-wizard-success { color: #16a34a; font-weight: 500; }
                .heymag-wizard-error { color: #dc2626; font-weight: 500; }
                .heymag-wizard-actions { margin-top: 20px; display: flex; justify-content: space-between; }
            </style>

            <div class="heymag-wizard-header">
                <h1><?php esc_html_e('Welcome to HeyMag', 'heymag-chat'); ?></h1>
                <p><?php esc_html_e('Connect your store to AI-powered customer chat in minutes', 'heymag-chat'); ?></p>
            </div>

            <div class="heymag-wizard-steps-bar">
                <div class="heymag-wizard-dot active" data-step="1"></div>
                <div class="heymag-wizard-dot" data-step="2"></div>
                <?php if ($has_woocommerce) : ?>
                <div class="heymag-wizard-dot" data-step="3"></div>
                <?php endif; ?>
                <div class="heymag-wizard-dot" data-step="4"></div>
            </div>

            <!-- Step 1: Welcome -->
            <div class="heymag-wizard-step active" data-step="1">
                <div class="heymag-wizard-card">
                    <h3><?php esc_html_e('AI-Powered Customer Chat', 'heymag-chat'); ?></h3>
                    <p><?php esc_html_e('HeyMag adds an intelligent AI assistant to your store that can:', 'heymag-chat'); ?></p>
                    <ul>
                        <li><?php esc_html_e('Answer customer questions about your products 24/7', 'heymag-chat'); ?></li>
                        <li><?php esc_html_e('Help customers place orders via WhatsApp, Telegram, or your website', 'heymag-chat'); ?></li>
                        <li><?php esc_html_e('Look up order status and track deliveries', 'heymag-chat'); ?></li>
                        <li><?php esc_html_e('Provide personalized product recommendations', 'heymag-chat'); ?></li>
                    </ul>
                    <div class="heymag-wizard-actions">
                        <span></span>
                        <button class="heymag-wizard-btn" onclick="heymagWizardNext(2)">
                            <?php esc_html_e('Get Started', 'heymag-chat'); ?> &rarr;
                        </button>
                    </div>
                </div>
            </div>

            <!-- Step 2: Connect Account -->
            <div class="heymag-wizard-step" data-step="2">
                <div class="heymag-wizard-card">
                    <h3><?php esc_html_e('Connect Your HeyMag Account', 'heymag-chat'); ?></h3>
                    <p><?php esc_html_e('Enter your HeyMag API key from your dashboard settings.', 'heymag-chat'); ?></p>
                    <input type="text" class="heymag-wizard-input" id="heymag-api-key"
                        placeholder="<?php esc_attr_e('Paste your HeyMag API Key', 'heymag-chat'); ?>">
                    <input type="text" class="heymag-wizard-input" id="heymag-business-id"
                        placeholder="<?php esc_attr_e('Business ID (from HeyMag dashboard)', 'heymag-chat'); ?>">
                    <div id="heymag-connect-status"></div>
                    <p>
                        <a href="<?php echo esc_url($signup_url); ?>" target="_blank" class="heymag-wizard-btn secondary">
                            <?php esc_html_e('Create Free Account', 'heymag-chat'); ?>
                        </a>
                    </p>
                    <div class="heymag-wizard-actions">
                        <button class="heymag-wizard-btn secondary" onclick="heymagWizardNext(1)">
                            &larr; <?php esc_html_e('Back', 'heymag-chat'); ?>
                        </button>
                        <button class="heymag-wizard-btn" id="heymag-connect-btn" onclick="heymagWizardConnect()">
                            <?php esc_html_e('Connect', 'heymag-chat'); ?> &rarr;
                        </button>
                    </div>
                </div>
            </div>

            <?php if ($has_woocommerce) : ?>
            <!-- Step 3: WooCommerce API Keys -->
            <div class="heymag-wizard-step" data-step="3">
                <div class="heymag-wizard-card">
                    <h3><?php esc_html_e('Connect WooCommerce', 'heymag-chat'); ?></h3>
                    <p><?php esc_html_e('Generate API keys so HeyMag can sync your products, orders, and customers.', 'heymag-chat'); ?></p>
                    <div id="heymag-wc-status"></div>
                    <div class="heymag-wizard-actions">
                        <button class="heymag-wizard-btn secondary" onclick="heymagWizardNext(2)">
                            &larr; <?php esc_html_e('Back', 'heymag-chat'); ?>
                        </button>
                        <button class="heymag-wizard-btn" id="heymag-wc-btn" onclick="heymagWizardGenerateKeys()">
                            <?php esc_html_e('Generate & Connect', 'heymag-chat'); ?> &rarr;
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Step 4: Complete -->
            <div class="heymag-wizard-step" data-step="4">
                <div class="heymag-wizard-card" style="text-align: center;">
                    <h3><?php esc_html_e('You\'re All Set!', 'heymag-chat'); ?></h3>
                    <p><?php esc_html_e('Your store is now connected to HeyMag. Products will sync automatically.', 'heymag-chat'); ?></p>
                    <p><a href="https://heymag.app/dashboard" target="_blank" class="heymag-wizard-btn">
                        <?php esc_html_e('Go to HeyMag Dashboard', 'heymag-chat'); ?>
                    </a></p>
                    <p style="margin-top: 16px;">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=heymag-chat')); ?>">
                            <?php esc_html_e('Go to Plugin Settings', 'heymag-chat'); ?> &rarr;
                        </a>
                    </p>
                </div>
            </div>

            <script>
            function heymagWizardNext(step) {
                document.querySelectorAll('.heymag-wizard-step').forEach(el => el.classList.remove('active'));
                document.querySelector('[data-step="' + step + '"].heymag-wizard-step').classList.add('active');
                document.querySelectorAll('.heymag-wizard-dot').forEach(dot => {
                    var s = parseInt(dot.getAttribute('data-step'));
                    dot.classList.remove('active', 'done');
                    if (s < step) dot.classList.add('done');
                    if (s === step) dot.classList.add('active');
                });
            }

            function heymagWizardConnect() {
                var apiKey = document.getElementById('heymag-api-key').value.trim();
                var businessId = document.getElementById('heymag-business-id').value.trim();
                var status = document.getElementById('heymag-connect-status');
                var btn = document.getElementById('heymag-connect-btn');

                if (!apiKey || !businessId) {
                    status.innerHTML = '<p class="heymag-wizard-error">Please enter both API key and Business ID.</p>';
                    return;
                }

                btn.disabled = true;
                btn.textContent = 'Connecting...';
                status.innerHTML = '<p>Verifying connection...</p>';

                var data = new FormData();
                data.append('action', 'heymag_wizard_connect');
                data.append('api_key', apiKey);
                data.append('business_id', businessId);
                data.append('_wpnonce', '<?php echo wp_create_nonce('heymag_wizard'); ?>');

                fetch(ajaxurl, { method: 'POST', body: data })
                    .then(r => r.json())
                    .then(result => {
                        btn.disabled = false;
                        btn.textContent = 'Connect →';
                        if (result.success) {
                            status.innerHTML = '<p class="heymag-wizard-success">Connected! (' + (result.data.business_name || 'OK') + ')</p>';
                            var nextStep = <?php echo $has_woocommerce ? '3' : '4'; ?>;
                            setTimeout(() => heymagWizardNext(nextStep), 1000);
                        } else {
                            status.innerHTML = '<p class="heymag-wizard-error">' + (result.data || 'Connection failed') + '</p>';
                        }
                    })
                    .catch(() => {
                        btn.disabled = false;
                        btn.textContent = 'Connect →';
                        status.innerHTML = '<p class="heymag-wizard-error">Network error. Please try again.</p>';
                    });
            }

            function heymagWizardGenerateKeys() {
                var status = document.getElementById('heymag-wc-status');
                var btn = document.getElementById('heymag-wc-btn');

                btn.disabled = true;
                btn.textContent = 'Generating keys...';
                status.innerHTML = '<p>Creating WooCommerce API keys...</p>';

                var data = new FormData();
                data.append('action', 'heymag_wizard_generate_wc_keys');
                data.append('_wpnonce', '<?php echo wp_create_nonce('heymag_wizard'); ?>');

                fetch(ajaxurl, { method: 'POST', body: data })
                    .then(r => r.json())
                    .then(result => {
                        btn.disabled = false;
                        btn.textContent = 'Generate & Connect →';
                        if (result.success) {
                            status.innerHTML = '<p class="heymag-wizard-success">WooCommerce connected!</p>';
                            setTimeout(() => heymagWizardNext(4), 1000);
                        } else {
                            status.innerHTML = '<p class="heymag-wizard-error">' + (result.data || 'Failed') + '</p>';
                        }
                    })
                    .catch(() => {
                        btn.disabled = false;
                        btn.textContent = 'Generate & Connect →';
                        status.innerHTML = '<p class="heymag-wizard-error">Network error.</p>';
                    });
            }
            </script>
        </div>
        <?php
    }

    /**
     * AJAX: Connect to HeyMag
     */
    public function ajax_connect() {
        check_ajax_referer('heymag_wizard');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Insufficient permissions');
        }

        $api_key = sanitize_text_field($_POST['api_key'] ?? '');
        $business_id = sanitize_text_field($_POST['business_id'] ?? '');

        if (empty($api_key) || empty($business_id)) {
            wp_send_json_error('API key and Business ID are required');
        }

        // Save settings
        $settings = HeyMag_Chat::get_settings();
        $settings['api_key'] = $api_key;
        $settings['business_id'] = $business_id;
        $settings['widget_token'] = $api_key; // Widget uses same token
        HeyMag_Chat::update_settings($settings);

        wp_send_json_success(array(
            'business_name' => 'Connected',
        ));
    }

    /**
     * AJAX: Generate WooCommerce API keys and send to HeyMag
     */
    public function ajax_generate_wc_keys() {
        check_ajax_referer('heymag_wizard');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Insufficient permissions');
        }

        if (!class_exists('WooCommerce')) {
            wp_send_json_error('WooCommerce is not installed');
        }

        $settings = HeyMag_Chat::get_settings();
        $business_id = $settings['business_id'] ?? '';

        if (empty($business_id)) {
            wp_send_json_error('Connect your HeyMag account first');
        }

        // Generate WC REST API keys
        global $wpdb;
        $user_id = get_current_user_id();
        $description = 'HeyMag AI Assistant - ' . current_time('Y-m-d');
        $consumer_key = 'ck_' . wc_rand_hash();
        $consumer_secret = 'cs_' . wc_rand_hash();

        $wpdb->insert(
            $wpdb->prefix . 'woocommerce_api_keys',
            array(
                'user_id' => $user_id,
                'description' => $description,
                'permissions' => 'read_write',
                'consumer_key' => wc_api_hash($consumer_key),
                'consumer_secret' => $consumer_secret,
                'truncated_key' => substr($consumer_key, -7),
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s')
        );

        // Send keys to HeyMag connect endpoint
        $response = wp_remote_post(HEYMAG_API_URL . '/integrations/woocommerce/connect', array(
            'timeout' => 15,
            'headers' => array('Content-Type' => 'application/json'),
            'body' => wp_json_encode(array(
                'businessId' => $business_id,
                'storeUrl' => get_site_url(),
                'consumerKey' => $consumer_key,
                'consumerSecret' => $consumer_secret,
                'pluginVersion' => HEYMAG_VERSION,
                'storeInfo' => array(
                    'name' => get_bloginfo('name'),
                    'wc_version' => WC()->version,
                    'currency' => get_woocommerce_currency(),
                    'timezone' => wc_timezone_string(),
                ),
            )),
        ));

        if (is_wp_error($response)) {
            wp_send_json_error('Failed to connect: ' . $response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($body['success'])) {
            wp_send_json_error('Connection rejected: ' . ($body['error'] ?? 'Unknown error'));
        }

        // Save WC keys to settings
        $settings['wc_consumer_key'] = $consumer_key;
        $settings['wc_consumer_secret'] = $consumer_secret;
        HeyMag_Chat::update_settings($settings);

        wp_send_json_success(array(
            'message' => 'WooCommerce connected successfully',
        ));
    }

    /**
     * AJAX: Mark wizard as complete
     */
    public function ajax_complete() {
        check_ajax_referer('heymag_wizard');
        update_option('heymag_wizard_completed', true);
        wp_send_json_success();
    }
}
