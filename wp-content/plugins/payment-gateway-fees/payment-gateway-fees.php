<?php
/**
 * Plugin Name: Payment Gateway Fees
 * Description: Add fees based on selected payment method at checkout
 * Version: 1.2.0
 * Author: Ah Ho Fruit
 * Requires Plugins: woocommerce
 */

if (!defined('ABSPATH')) exit;

class Payment_Gateway_Fees {

    private $option_name = 'pgf_gateway_fees';

    public function __construct() {
        // Frontend hooks - use later priority to ensure gateways are loaded
        add_action('woocommerce_cart_calculate_fees', [$this, 'add_payment_fee'], 20);
        add_action('woocommerce_after_checkout_form', [$this, 'refresh_checkout_js']);

        // Also support WooCommerce Blocks checkout
        add_action('wp_enqueue_scripts', [$this, 'blocks_checkout_js']);

        // Admin hooks
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'admin_scripts']);
    }

    /**
     * Get configured fees from database
     */
    private function get_fees() {
        return get_option($this->option_name, []);
    }

    /**
     * Add fee to cart based on selected payment method
     */
    public function add_payment_fee($cart) {
        if (is_admin() && !defined('DOING_AJAX')) return;

        // More compatible checkout detection - works with blocks too
        if (!$this->is_checkout_context()) return;

        $chosen_gateway = WC()->session->get('chosen_payment_method');
        $fees = $this->get_fees();

        if (empty($chosen_gateway) || !isset($fees[$chosen_gateway])) {
            return;
        }

        $fee_config = $fees[$chosen_gateway];

        // Skip if not enabled
        if (empty($fee_config['enabled'])) {
            return;
        }

        // Calculate total including shipping (subtotal + shipping)
        $cart_subtotal = $cart->get_subtotal();
        $shipping_total = $cart->get_shipping_total();
        $cart_total = $cart_subtotal + $shipping_total;

        $fee_amount = 0;
        $fee_type = $fee_config['type'] ?? 'percent';

        if ($fee_type === 'fixed') {
            $fee_amount = floatval($fee_config['fixed'] ?? 0);
        } elseif ($fee_type === 'percent') {
            $fee_amount = ($cart_total * floatval($fee_config['percent'] ?? 0)) / 100;
        } elseif ($fee_type === 'both') {
            $percent_fee = ($cart_total * floatval($fee_config['percent'] ?? 0)) / 100;
            $fixed_fee = floatval($fee_config['fixed'] ?? 0);
            $fee_amount = $percent_fee + $fixed_fee;
        }

        if ($fee_amount > 0) {
            $label = $fee_config['label'] ?? __('Processing Fee', 'pgf');
            $taxable = !empty($fee_config['taxable']);
            $cart->add_fee($label, $fee_amount, $taxable);
        }
    }

    /**
     * Check if we're in a checkout context (works with blocks and classic)
     */
    private function is_checkout_context() {
        // Classic checkout
        if (function_exists('is_checkout') && is_checkout()) {
            return true;
        }

        // AJAX checkout updates
        if (defined('DOING_AJAX') && DOING_AJAX) {
            return true;
        }

        // WooCommerce Blocks checkout via REST API
        if (defined('REST_REQUEST') && REST_REQUEST) {
            return true;
        }

        // Check if we're on a page with checkout block
        if (function_exists('has_block') && is_singular()) {
            global $post;
            if ($post && has_block('woocommerce/checkout', $post)) {
                return true;
            }
        }

        return false;
    }

    /**
     * JavaScript to refresh checkout when payment method changes (classic checkout)
     */
    public function refresh_checkout_js() {
        ?>
        <script type="text/javascript">
        jQuery(function($) {
            $('form.checkout').on('change', 'input[name="payment_method"]', function() {
                $('body').trigger('update_checkout');
            });
        });
        </script>
        <?php
    }

    /**
     * JavaScript for WooCommerce Blocks checkout
     */
    public function blocks_checkout_js() {
        if (!function_exists('is_checkout') || !is_checkout()) {
            return;
        }

        // This script will work with WooCommerce Blocks
        wp_add_inline_script('wc-blocks-checkout', '
            if (typeof wp !== "undefined" && wp.data && wp.data.subscribe) {
                const { subscribe, select } = wp.data;
                let lastPaymentMethod = null;

                subscribe(() => {
                    const currentMethod = select("wc/store/payment").getActivePaymentMethod();
                    if (currentMethod && currentMethod !== lastPaymentMethod) {
                        lastPaymentMethod = currentMethod;
                        // Trigger cart update when payment method changes
                        wp.data.dispatch("wc/store/cart").invalidateResolutionForStore();
                    }
                });
            }
        ', 'after');
    }

    /**
     * Add admin menu under WooCommerce
     */
    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            __('Payment Gateway Fees', 'pgf'),
            __('Gateway Fees', 'pgf'),
            'manage_woocommerce',
            'payment-gateway-fees',
            [$this, 'render_admin_page']
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting($this->option_name, $this->option_name, [
            'sanitize_callback' => [$this, 'sanitize_settings']
        ]);
    }

    /**
     * Sanitize settings before saving
     */
    public function sanitize_settings($input) {
        $sanitized = [];

        if (!is_array($input)) {
            return $sanitized;
        }

        foreach ($input as $gateway_id => $config) {
            $sanitized[sanitize_key($gateway_id)] = [
                'enabled'  => !empty($config['enabled']),
                'label'    => sanitize_text_field($config['label'] ?? ''),
                'type'     => in_array($config['type'], ['fixed', 'percent', 'both']) ? $config['type'] : 'percent',
                'percent'  => floatval($config['percent'] ?? 0),
                'fixed'    => floatval($config['fixed'] ?? 0),
                'taxable'  => !empty($config['taxable']),
            ];
        }

        return $sanitized;
    }

    /**
     * Admin page styles
     */
    public function admin_scripts($hook) {
        if ($hook !== 'woocommerce_page_payment-gateway-fees') return;

        wp_add_inline_style('woocommerce_admin_styles', '
            .pgf-settings { max-width: 900px; }
            .pgf-gateway-card {
                background: #fff;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                margin-bottom: 15px;
                padding: 0;
            }
            .pgf-gateway-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 15px;
                border-bottom: 1px solid #eee;
                background: #f9f9f9;
            }
            .pgf-gateway-title {
                font-weight: 600;
                font-size: 14px;
            }
            .pgf-gateway-id {
                color: #666;
                font-size: 12px;
                font-family: monospace;
            }
            .pgf-gateway-status {
                font-size: 11px;
                padding: 2px 6px;
                border-radius: 3px;
                margin-left: 8px;
            }
            .pgf-gateway-status.active {
                background: #d4edda;
                color: #155724;
            }
            .pgf-gateway-status.inactive {
                background: #f8d7da;
                color: #721c24;
            }
            .pgf-gateway-body {
                padding: 15px;
                display: none;
            }
            .pgf-gateway-card.active .pgf-gateway-body {
                display: block;
            }
            .pgf-field-row {
                display: flex;
                gap: 15px;
                margin-bottom: 12px;
                align-items: center;
            }
            .pgf-field-row label {
                min-width: 100px;
                font-weight: 500;
            }
            .pgf-field-row input[type="text"],
            .pgf-field-row input[type="number"],
            .pgf-field-row select {
                width: 200px;
            }
            .pgf-type-fields {
                background: #f5f5f5;
                padding: 12px;
                border-radius: 4px;
                margin-top: 10px;
            }
            .pgf-no-gateways {
                padding: 20px;
                background: #fff3cd;
                border: 1px solid #ffc107;
                border-radius: 4px;
            }
            .pgf-section-title {
                font-size: 16px;
                font-weight: 600;
                margin: 25px 0 15px 0;
                padding-bottom: 10px;
                border-bottom: 2px solid #0073aa;
            }
            .pgf-section-title:first-of-type {
                margin-top: 10px;
            }
        ');
    }

    /**
     * Get all available payment gateways (including inactive ones for Stripe)
     */
    private function get_available_gateways() {
        $gateways = [];
        $stripe_gateways = [];

        if (function_exists('WC')) {
            $available = WC()->payment_gateways()->payment_gateways();

            foreach ($available as $gateway) {
                $is_stripe = strpos($gateway->id, 'stripe') !== false;
                $is_enabled = $gateway->enabled === 'yes';

                $gateway_info = [
                    'title' => $gateway->get_title(),
                    'enabled' => $is_enabled,
                    'is_stripe' => $is_stripe,
                ];

                // Group Stripe gateways separately
                if ($is_stripe) {
                    $stripe_gateways[$gateway->id] = $gateway_info;
                } elseif ($is_enabled) {
                    $gateways[$gateway->id] = $gateway_info;
                }
            }
        }

        return [
            'other' => $gateways,
            'stripe' => $stripe_gateways,
        ];
    }

    /**
     * Render a single gateway card
     */
    private function render_gateway_card($gateway_id, $gateway_info, $fees) {
        $config = $fees[$gateway_id] ?? [];
        $is_enabled = !empty($config['enabled']);
        $gateway_enabled = $gateway_info['enabled'];
        ?>
        <div class="pgf-gateway-card <?php echo $is_enabled ? 'active' : ''; ?>">
            <div class="pgf-gateway-header">
                <div>
                    <span class="pgf-gateway-title"><?php echo esc_html($gateway_info['title']); ?></span>
                    <span class="pgf-gateway-id">(<?php echo esc_html($gateway_id); ?>)</span>
                    <span class="pgf-gateway-status <?php echo $gateway_enabled ? 'active' : 'inactive'; ?>">
                        <?php echo $gateway_enabled ? __('Active', 'pgf') : __('Inactive', 'pgf'); ?>
                    </span>
                </div>
                <label>
                    <input type="checkbox"
                           name="<?php echo esc_attr($this->option_name); ?>[<?php echo esc_attr($gateway_id); ?>][enabled]"
                           value="1"
                           <?php checked($is_enabled); ?>
                           onchange="this.closest('.pgf-gateway-card').classList.toggle('active', this.checked)">
                    <?php _e('Enable Fee', 'pgf'); ?>
                </label>
            </div>

            <div class="pgf-gateway-body">
                <div class="pgf-field-row">
                    <label><?php _e('Fee Label', 'pgf'); ?></label>
                    <input type="text"
                           name="<?php echo esc_attr($this->option_name); ?>[<?php echo esc_attr($gateway_id); ?>][label]"
                           value="<?php echo esc_attr($config['label'] ?? 'Processing Fee'); ?>"
                           placeholder="Processing Fee">
                </div>

                <div class="pgf-field-row">
                    <label><?php _e('Fee Type', 'pgf'); ?></label>
                    <select name="<?php echo esc_attr($this->option_name); ?>[<?php echo esc_attr($gateway_id); ?>][type]"
                            onchange="pgfToggleFields(this)">
                        <option value="percent" <?php selected($config['type'] ?? 'percent', 'percent'); ?>>
                            <?php _e('Percentage of order total (items + shipping)', 'pgf'); ?>
                        </option>
                        <option value="fixed" <?php selected($config['type'] ?? '', 'fixed'); ?>>
                            <?php _e('Fixed amount', 'pgf'); ?>
                        </option>
                        <option value="both" <?php selected($config['type'] ?? '', 'both'); ?>>
                            <?php _e('Fixed + Percentage', 'pgf'); ?>
                        </option>
                    </select>
                </div>

                <div class="pgf-type-fields">
                    <div class="pgf-field-row pgf-percent-field" style="<?php echo ($config['type'] ?? 'percent') === 'fixed' ? 'display:none' : ''; ?>">
                        <label><?php _e('Percentage', 'pgf'); ?></label>
                        <input type="number"
                               step="0.01"
                               min="0"
                               name="<?php echo esc_attr($this->option_name); ?>[<?php echo esc_attr($gateway_id); ?>][percent]"
                               value="<?php echo esc_attr($config['percent'] ?? ''); ?>"
                               placeholder="2.9">
                        <span>%</span>
                    </div>

                    <div class="pgf-field-row pgf-fixed-field" style="<?php echo ($config['type'] ?? 'percent') === 'percent' ? 'display:none' : ''; ?>">
                        <label><?php _e('Fixed Amount', 'pgf'); ?></label>
                        <input type="number"
                               step="0.01"
                               min="0"
                               name="<?php echo esc_attr($this->option_name); ?>[<?php echo esc_attr($gateway_id); ?>][fixed]"
                               value="<?php echo esc_attr($config['fixed'] ?? ''); ?>"
                               placeholder="0.30">
                        <span><?php echo get_woocommerce_currency_symbol(); ?></span>
                    </div>
                </div>

                <div class="pgf-field-row">
                    <label><?php _e('Taxable', 'pgf'); ?></label>
                    <label>
                        <input type="checkbox"
                               name="<?php echo esc_attr($this->option_name); ?>[<?php echo esc_attr($gateway_id); ?>][taxable]"
                               value="1"
                               <?php checked(!empty($config['taxable'])); ?>>
                        <?php _e('Apply tax to this fee', 'pgf'); ?>
                    </label>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render admin settings page
     */
    public function render_admin_page() {
        $gateway_groups = $this->get_available_gateways();
        $fees = $this->get_fees();
        $has_gateways = !empty($gateway_groups['other']) || !empty($gateway_groups['stripe']);
        ?>
        <div class="wrap pgf-settings">
            <h1><?php _e('Payment Gateway Fees', 'pgf'); ?></h1>
            <p><?php _e('Add processing fees based on the payment method selected at checkout.', 'pgf'); ?></p>

            <?php if (!$has_gateways): ?>
                <div class="pgf-no-gateways">
                    <strong><?php _e('No payment gateways found.', 'pgf'); ?></strong>
                    <p><?php _e('Please enable at least one payment gateway in WooCommerce settings.', 'pgf'); ?></p>
                </div>
            <?php else: ?>
                <form method="post" action="options.php">
                    <?php settings_fields($this->option_name); ?>

                    <?php if (!empty($gateway_groups['stripe'])): ?>
                        <h2 class="pgf-section-title"><?php _e('Stripe Payment Methods', 'pgf'); ?></h2>
                        <p class="description"><?php _e('Configure fees for Stripe payment methods. Note: Some methods may show as "Inactive" but still appear on checkout through Stripe\'s dynamic loading.', 'pgf'); ?></p>

                        <?php foreach ($gateway_groups['stripe'] as $gateway_id => $gateway_info): ?>
                            <?php $this->render_gateway_card($gateway_id, $gateway_info, $fees); ?>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <?php if (!empty($gateway_groups['other'])): ?>
                        <h2 class="pgf-section-title"><?php _e('Other Payment Methods', 'pgf'); ?></h2>

                        <?php foreach ($gateway_groups['other'] as $gateway_id => $gateway_info): ?>
                            <?php $this->render_gateway_card($gateway_id, $gateway_info, $fees); ?>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <?php submit_button(__('Save Changes', 'pgf')); ?>
                </form>

                <script>
                function pgfToggleFields(select) {
                    const card = select.closest('.pgf-gateway-card');
                    const percentField = card.querySelector('.pgf-percent-field');
                    const fixedField = card.querySelector('.pgf-fixed-field');

                    switch(select.value) {
                        case 'percent':
                            percentField.style.display = '';
                            fixedField.style.display = 'none';
                            break;
                        case 'fixed':
                            percentField.style.display = 'none';
                            fixedField.style.display = '';
                            break;
                        case 'both':
                            percentField.style.display = '';
                            fixedField.style.display = '';
                            break;
                    }
                }
                </script>
            <?php endif; ?>
        </div>
        <?php
    }
}

new Payment_Gateway_Fees();
