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
                    <th scope="row"><?php _e('Custom Rates', 'ah-ho-custom'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox"
                                   name="ah_ho_enable_custom_rates"
                                   value="1"
                                   <?php checked(get_option('ah_ho_enable_custom_rates', true)); ?> />
                            <?php _e('Enable Custom Rates Per Salesperson', 'ah-ho-custom'); ?>
                        </label>
                        <p class="description"><?php _e('When enabled, admins can set individual commission rates in each salesperson\'s user profile.', 'ah-ho-custom'); ?></p>
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
    // Count salespersons
    $salespersons = get_users(array('role' => 'ah_ho_salesperson'));
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
