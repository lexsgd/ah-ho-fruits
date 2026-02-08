<?php
/**
 * Salesperson Settings Page
 *
 * Admin UI for configuring commission rates, approval workflow, and email notifications
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add settings page to admin menu
 */
add_action('admin_menu', 'ah_ho_add_settings_page');

function ah_ho_add_settings_page() {
    add_submenu_page(
        'woocommerce',                      // Parent slug
        __('Salesperson Settings', 'ah-ho-custom'),
        __('Salesperson Settings', 'ah-ho-custom'),
        'manage_options',
        'ah-ho-salesperson-settings',
        'ah_ho_render_settings_page'
    );
}

/**
 * Register settings
 */
add_action('admin_init', 'ah_ho_register_settings');

function ah_ho_register_settings() {
    // Commission Rate Configuration
    register_setting('ah_ho_salesperson_settings', 'ah_ho_default_commission_rate', array(
        'type'              => 'number',
        'default'           => 10.0,
        'sanitize_callback' => 'floatval'
    ));

    register_setting('ah_ho_salesperson_settings', 'ah_ho_default_per_carton_rate', array(
        'type'              => 'number',
        'default'           => 0,
        'sanitize_callback' => 'floatval'
    ));

    register_setting('ah_ho_salesperson_settings', 'ah_ho_enable_custom_rates', array(
        'type'              => 'boolean',
        'default'           => true,
        'sanitize_callback' => 'rest_sanitize_boolean'
    ));

    // Commission Approval Workflow
    register_setting('ah_ho_salesperson_settings', 'ah_ho_commission_approval_mode', array(
        'type'              => 'string',
        'default'           => 'auto',
        'sanitize_callback' => 'sanitize_text_field'
    ));

    // Email Notifications
    register_setting('ah_ho_salesperson_settings', 'ah_ho_commission_notification_emails', array(
        'type'              => 'string',
        'default'           => get_option('admin_email'),
        'sanitize_callback' => 'ah_ho_sanitize_email_list'
    ));

    register_setting('ah_ho_salesperson_settings', 'ah_ho_notify_on_approval', array(
        'type'              => 'boolean',
        'default'           => true,
        'sanitize_callback' => 'rest_sanitize_boolean'
    ));

    register_setting('ah_ho_salesperson_settings', 'ah_ho_monthly_summary_salesperson', array(
        'type'              => 'boolean',
        'default'           => true,
        'sanitize_callback' => 'rest_sanitize_boolean'
    ));

    register_setting('ah_ho_salesperson_settings', 'ah_ho_monthly_summary_admin', array(
        'type'              => 'boolean',
        'default'           => false,
        'sanitize_callback' => 'rest_sanitize_boolean'
    ));

    // B2B Payment Terms
    register_setting('ah_ho_salesperson_settings', 'ah_ho_payment_terms', array(
        'type'              => 'array',
        'default'           => array(),
        'sanitize_callback' => 'ah_ho_sanitize_payment_terms'
    ));

    // Wholesale Pricing Settings
    register_setting('ah_ho_salesperson_settings', 'ah_ho_default_wholesale_discount', array(
        'type'              => 'number',
        'default'           => 0,
        'sanitize_callback' => 'absint'
    ));

    register_setting('ah_ho_salesperson_settings', 'ah_ho_wholesale_fallback', array(
        'type'              => 'string',
        'default'           => 'retail',
        'sanitize_callback' => 'sanitize_text_field'
    ));
}

/**
 * Sanitize email list (comma-separated)
 */
function ah_ho_sanitize_email_list($input) {
    $emails = array_map('trim', explode(',', $input));
    $valid_emails = array_filter($emails, 'is_email');
    return implode(', ', $valid_emails);
}

/**
 * Sanitize payment terms array from settings form
 */
function ah_ho_sanitize_payment_terms($input) {
    if (!is_array($input)) {
        return array();
    }

    $sanitized = array();
    foreach ($input as $item) {
        if (empty($item['key']) || empty($item['label'])) {
            continue;
        }
        $key   = sanitize_key($item['key']);
        $label = sanitize_text_field($item['label']);
        $color = sanitize_hex_color($item['color']);
        if (empty($color)) {
            $color = '#666666';
        }
        $sanitized[$key] = array('label' => $label, 'color' => $color);
    }

    return $sanitized;
}

/**
 * Render settings page
 */
function ah_ho_render_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    // Show success message if settings saved
    if (isset($_GET['settings-updated'])) {
        add_settings_error(
            'ah_ho_salesperson_messages',
            'ah_ho_salesperson_message',
            __('Settings Saved', 'ah-ho-custom'),
            'updated'
        );
    }

    settings_errors('ah_ho_salesperson_messages');
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

        <form action="options.php" method="post">
            <?php settings_fields('ah_ho_salesperson_settings'); ?>

            <!-- Commission Rate Configuration -->
            <h2><?php _e('Commission Rate Configuration', 'ah-ho-custom'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Default Commission Rate (%)', 'ah-ho-custom'); ?></th>
                    <td>
                        <input type="number"
                               name="ah_ho_default_commission_rate"
                               value="<?php echo esc_attr(get_option('ah_ho_default_commission_rate', 10)); ?>"
                               step="0.01"
                               min="0"
                               max="100"
                               class="regular-text" />
                        <p class="description"><?php _e('Default commission percentage for all salespersons', 'ah-ho-custom'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Default Per-Carton Rate ($)', 'ah-ho-custom'); ?></th>
                    <td>
                        <input type="number"
                               name="ah_ho_default_per_carton_rate"
                               value="<?php echo esc_attr(get_option('ah_ho_default_per_carton_rate', 0)); ?>"
                               step="0.01"
                               min="0"
                               class="regular-text" />
                        <p class="description"><?php _e('Default fixed dollar amount per carton for all salespersons. Set to 0 to disable per-carton commission by default.', 'ah-ho-custom'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Custom Rates', 'ah-ho-custom'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox"
                                   name="ah_ho_enable_custom_rates"
                                   value="1"
                                   <?php checked(get_option('ah_ho_enable_custom_rates', true)); ?> />
                            <?php _e('Enable Custom Rates Per Salesperson', 'ah-ho-custom'); ?>
                        </label>
                        <p class="description"><?php _e('When enabled, admins can set individual percentage and per-carton commission rates in each salesperson\'s user profile.', 'ah-ho-custom'); ?></p>
                    </td>
                </tr>
            </table>

            <!-- Commission Approval Workflow -->
            <h2><?php _e('Commission Approval Workflow', 'ah-ho-custom'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Approval Mode', 'ah-ho-custom'); ?></th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="radio"
                                       name="ah_ho_commission_approval_mode"
                                       value="auto"
                                       <?php checked(get_option('ah_ho_commission_approval_mode', 'auto'), 'auto'); ?> />
                                <strong><?php _e('Auto-Approve', 'ah-ho-custom'); ?></strong> <?php _e('(Recommended)', 'ah-ho-custom'); ?>
                            </label>
                            <p class="description" style="margin-left: 25px;">
                                <?php _e('Commissions automatically approved when order is completed. Admin marks as "paid" when processed.', 'ah-ho-custom'); ?>
                            </p>

                            <label>
                                <input type="radio"
                                       name="ah_ho_commission_approval_mode"
                                       value="manual"
                                       <?php checked(get_option('ah_ho_commission_approval_mode', 'auto'), 'manual'); ?> />
                                <strong><?php _e('Manual Approval', 'ah-ho-custom'); ?></strong>
                            </label>
                            <p class="description" style="margin-left: 25px;">
                                <?php _e('Admin must manually approve each commission before it can be marked as paid.', 'ah-ho-custom'); ?>
                            </p>
                        </fieldset>
                    </td>
                </tr>
            </table>

            <!-- Email Notifications -->
            <h2><?php _e('Email Notifications', 'ah-ho-custom'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Notification Recipients', 'ah-ho-custom'); ?></th>
                    <td>
                        <input type="text"
                               name="ah_ho_commission_notification_emails"
                               value="<?php echo esc_attr(get_option('ah_ho_commission_notification_emails', get_option('admin_email'))); ?>"
                               class="large-text"
                               placeholder="admin@example.com, accounts@example.com" />
                        <p class="description"><?php _e('Comma-separated list of email addresses to receive commission notifications', 'ah-ho-custom'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Notification Events', 'ah-ho-custom'); ?></th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="checkbox"
                                       name="ah_ho_notify_on_approval"
                                       value="1"
                                       <?php checked(get_option('ah_ho_notify_on_approval', true)); ?> />
                                <?php _e('Notify when commission is approved', 'ah-ho-custom'); ?>
                            </label>
                            <br />
                            <label>
                                <input type="checkbox"
                                       name="ah_ho_monthly_summary_salesperson"
                                       value="1"
                                       <?php checked(get_option('ah_ho_monthly_summary_salesperson', true)); ?> />
                                <?php _e('Send monthly summary to salespersons', 'ah-ho-custom'); ?>
                            </label>
                            <br />
                            <label>
                                <input type="checkbox"
                                       name="ah_ho_monthly_summary_admin"
                                       value="1"
                                       <?php checked(get_option('ah_ho_monthly_summary_admin', false)); ?> />
                                <?php _e('Send monthly summary to admin', 'ah-ho-custom'); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>
            </table>

            <!-- Wholesale Pricing -->
            <h2><?php _e('Wholesale Pricing (B2B)', 'ah-ho-custom'); ?></h2>
            <p class="description"><?php _e('Configure how wholesale prices work for salesperson orders. Set individual wholesale prices on each product\'s edit page.', 'ah-ho-custom'); ?></p>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Default Wholesale Discount (%)', 'ah-ho-custom'); ?></th>
                    <td>
                        <input type="number"
                               name="ah_ho_default_wholesale_discount"
                               value="<?php echo esc_attr(get_option('ah_ho_default_wholesale_discount', 0)); ?>"
                               step="1"
                               min="0"
                               max="100"
                               class="regular-text" />
                        <p class="description"><?php _e('Default discount off retail for products without a wholesale price set. Set to 0 to require explicit wholesale prices.', 'ah-ho-custom'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Fallback Behavior', 'ah-ho-custom'); ?></th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="radio"
                                       name="ah_ho_wholesale_fallback"
                                       value="retail"
                                       <?php checked(get_option('ah_ho_wholesale_fallback', 'retail'), 'retail'); ?> />
                                <strong><?php _e('Use Retail Price', 'ah-ho-custom'); ?></strong>
                            </label>
                            <p class="description" style="margin-left: 25px;">
                                <?php _e('If no wholesale price is set, use the regular retail price.', 'ah-ho-custom'); ?>
                            </p>

                            <label>
                                <input type="radio"
                                       name="ah_ho_wholesale_fallback"
                                       value="discount"
                                       <?php checked(get_option('ah_ho_wholesale_fallback', 'retail'), 'discount'); ?> />
                                <strong><?php _e('Apply Default Discount', 'ah-ho-custom'); ?></strong>
                            </label>
                            <p class="description" style="margin-left: 25px;">
                                <?php _e('Apply the default wholesale discount percentage to the retail price.', 'ah-ho-custom'); ?>
                            </p>

                            <label>
                                <input type="radio"
                                       name="ah_ho_wholesale_fallback"
                                       value="block"
                                       <?php checked(get_option('ah_ho_wholesale_fallback', 'retail'), 'block'); ?> />
                                <strong><?php _e('Block Product', 'ah-ho-custom'); ?></strong>
                            </label>
                            <p class="description" style="margin-left: 25px;">
                                <?php _e('Prevent adding products without a wholesale price to salesperson orders.', 'ah-ho-custom'); ?>
                            </p>
                        </fieldset>
                    </td>
                </tr>
            </table>

            <!-- B2B Payment Terms -->
            <h2><?php _e('B2B Payment Terms', 'ah-ho-custom'); ?></h2>
            <p class="description"><?php _e('Configure the payment terms available when editing customer profiles. Changes apply to all dropdowns, badges, invoices, and delivery orders.', 'ah-ho-custom'); ?></p>
            <table class="widefat" id="ah-ho-payment-terms-table" style="max-width: 650px;">
                <thead>
                    <tr>
                        <th style="width: 20%;"><?php _e('Key', 'ah-ho-custom'); ?></th>
                        <th style="width: 35%;"><?php _e('Label', 'ah-ho-custom'); ?></th>
                        <th style="width: 20%;"><?php _e('Color', 'ah-ho-custom'); ?></th>
                        <th style="width: 15%;"><?php _e('Preview', 'ah-ho-custom'); ?></th>
                        <th style="width: 10%;"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $payment_terms = ah_ho_get_payment_terms();
                    $idx = 0;
                    foreach ($payment_terms as $key => $term):
                    ?>
                    <tr>
                        <td><input type="text" name="ah_ho_payment_terms[<?php echo $idx; ?>][key]" value="<?php echo esc_attr($key); ?>" class="regular-text" style="width:100%;" pattern="[a-z0-9_]+" title="<?php esc_attr_e('Lowercase letters, numbers, underscores only', 'ah-ho-custom'); ?>" /></td>
                        <td><input type="text" name="ah_ho_payment_terms[<?php echo $idx; ?>][label]" value="<?php echo esc_attr($term['label']); ?>" class="regular-text" style="width:100%;" /></td>
                        <td><input type="color" name="ah_ho_payment_terms[<?php echo $idx; ?>][color]" value="<?php echo esc_attr($term['color']); ?>" style="width:50px;height:30px;padding:0;border:1px solid #ccc;cursor:pointer;" /></td>
                        <td><span style="display:inline-block;background:<?php echo esc_attr($term['color']); ?>;color:#fff;padding:2px 8px;border-radius:3px;font-size:11px;" class="ah-ho-term-preview"><?php echo esc_html($term['label']); ?></span></td>
                        <td><button type="button" class="button ah-ho-remove-term" title="<?php esc_attr_e('Remove', 'ah-ho-custom'); ?>">&times;</button></td>
                    </tr>
                    <?php $idx++; endforeach; ?>
                </tbody>
            </table>
            <p><button type="button" class="button" id="ah-ho-add-term"><?php _e('+ Add Term', 'ah-ho-custom'); ?></button></p>

            <script type="text/javascript">
            jQuery(function($) {
                var idx = <?php echo $idx; ?>;
                var $tbody = $('#ah-ho-payment-terms-table tbody');

                $('#ah-ho-add-term').on('click', function() {
                    var row = '<tr>' +
                        '<td><input type="text" name="ah_ho_payment_terms[' + idx + '][key]" value="" class="regular-text" style="width:100%;" pattern="[a-z0-9_]+" title="Lowercase letters, numbers, underscores only" placeholder="e.g. credit_90" /></td>' +
                        '<td><input type="text" name="ah_ho_payment_terms[' + idx + '][label]" value="" class="regular-text" style="width:100%;" placeholder="e.g. Credit - 90 Days" /></td>' +
                        '<td><input type="color" name="ah_ho_payment_terms[' + idx + '][color]" value="#666666" style="width:50px;height:30px;padding:0;border:1px solid #ccc;cursor:pointer;" /></td>' +
                        '<td><span style="display:inline-block;background:#666666;color:#fff;padding:2px 8px;border-radius:3px;font-size:11px;" class="ah-ho-term-preview"></span></td>' +
                        '<td><button type="button" class="button ah-ho-remove-term" title="Remove">&times;</button></td>' +
                        '</tr>';
                    $tbody.append(row);
                    idx++;
                });

                $tbody.on('click', '.ah-ho-remove-term', function() {
                    $(this).closest('tr').remove();
                });

                // Live preview update
                $tbody.on('input change', 'input', function() {
                    var $row = $(this).closest('tr');
                    var label = $row.find('input[name*="[label]"]').val();
                    var color = $row.find('input[name*="[color]"]').val();
                    $row.find('.ah-ho-term-preview').text(label).css('background', color);
                });
            });
            </script>

            <?php submit_button(__('Save Settings', 'ah-ho-custom')); ?>
        </form>

        <!-- Quick Stats -->
        <hr>
        <h2><?php _e('Salesperson Overview', 'ah-ho-custom'); ?></h2>
        <?php ah_ho_render_salesperson_overview(); ?>
    </div>

    <style>
        .ah-ho-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        .ah-ho-stat-card {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 15px;
        }
        .ah-ho-stat-card h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #646970;
            font-weight: 600;
        }
        .ah-ho-stat-card .stat-value {
            font-size: 28px;
            font-weight: 600;
            color: #1d2327;
        }
    </style>
    <?php
}

/**
 * Render salesperson overview stats
 */
function ah_ho_render_salesperson_overview() {
    // Count salespersons and storemen
    $salespersons = get_users(array('role__in' => array('ah_ho_salesperson', 'ah_ho_storeman')));
    $total_salespersons = count($salespersons);

    // Count orders with commission
    $orders_with_commission = new WP_Query(array(
        'post_type'      => 'shop_order',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_query'     => array(
            array(
                'key'     => '_commission_amount',
                'compare' => 'EXISTS'
            )
        )
    ));

    // Calculate total commission
    global $wpdb;
    $total_commission = $wpdb->get_var(
        "SELECT SUM(meta_value) FROM {$wpdb->postmeta} WHERE meta_key = '_commission_amount'"
    );

    // Count pending commissions
    $pending_commission = $wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_commission_status' AND meta_value = 'pending'"
    );

    ?>
    <div class="ah-ho-stats-grid">
        <div class="ah-ho-stat-card">
            <h3><?php _e('Total Salespersons', 'ah-ho-custom'); ?></h3>
            <div class="stat-value"><?php echo esc_html($total_salespersons); ?></div>
        </div>
        <div class="ah-ho-stat-card">
            <h3><?php _e('Orders with Commission', 'ah-ho-custom'); ?></h3>
            <div class="stat-value"><?php echo esc_html($orders_with_commission->found_posts); ?></div>
        </div>
        <div class="ah-ho-stat-card">
            <h3><?php _e('Total Commission', 'ah-ho-custom'); ?></h3>
            <div class="stat-value">$<?php echo number_format($total_commission ?: 0, 2); ?></div>
        </div>
        <div class="ah-ho-stat-card">
            <h3><?php _e('Pending Approvals', 'ah-ho-custom'); ?></h3>
            <div class="stat-value"><?php echo esc_html($pending_commission); ?></div>
        </div>
    </div>
    <?php
}
