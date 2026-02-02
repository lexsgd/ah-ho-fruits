<?php
/**
 * Salesperson Role & Permissions
 *
 * Registers the B2B salesperson role with restricted order access
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register salesperson role on plugin activation
 */
function ah_ho_register_salesperson_role() {
    // Check if role already exists
    if (get_role('ah_ho_salesperson')) {
        return;
    }

    // Create salesperson role based on Shop Manager but with restrictions
    add_role(
        'ah_ho_salesperson',
        __('Salesperson', 'ah-ho-custom'),
        array(
            // Basic WordPress capabilities
            'read'                          => true,
            'upload_files'                  => true,

            // WooCommerce order capabilities
            'read_shop_order'               => true,
            'read_shop_orders'              => true,
            'edit_shop_order'               => true,
            'edit_shop_orders'              => true,
            'publish_shop_orders'           => true,
            'create_shop_orders'            => true,

            // 🔒 SECURITY BOUNDARY - Prevent cross-salesperson access
            'edit_others_shop_orders'       => false,
            'read_others_shop_orders'       => false,
            'delete_shop_orders'            => false,
            'delete_others_shop_orders'     => false,

            // Product read-only access
            'read_product'                  => true,
            'read_products'                 => true,

            // Customer management (needed for creating orders)
            'list_users'                    => true,
            'read_shop_customer'            => true,

            // Customer creation/editing (B2B enhancement)
            'create_users'                  => true,
            'edit_users'                    => true,

            // Custom capability for commission viewing
            'view_salesperson_commission'   => true,
        )
    );
}

/**
 * Update salesperson role capabilities
 * Run this on plugin updates to refresh capabilities
 * ALSO creates the role if it doesn't exist (fallback for activation issues)
 */
function ah_ho_update_salesperson_role() {
    $role = get_role('ah_ho_salesperson');

    if (!$role) {
        // Role doesn't exist, create it immediately
        ah_ho_register_salesperson_role();

        // Log for debugging
        error_log('Ah Ho Custom: Salesperson role created via plugins_loaded hook');
        return;
    }

    // Update capabilities to match current requirements
    $capabilities = array(
        'read'                          => true,
        'upload_files'                  => true,
        'read_shop_order'               => true,
        'read_shop_orders'              => true,
        'edit_shop_order'               => true,
        'edit_shop_orders'              => true,
        'publish_shop_orders'           => true,
        'create_shop_orders'            => true,
        'edit_others_shop_orders'       => false,
        'read_others_shop_orders'       => false,
        'delete_shop_orders'            => false,
        'delete_others_shop_orders'     => false,
        'read_product'                  => true,
        'read_products'                 => true,
        'list_users'                    => true,
        'read_shop_customer'            => true,
        'create_users'                  => true,
        'edit_users'                    => true,
        'view_salesperson_commission'   => true,
    );

    foreach ($capabilities as $cap => $grant) {
        if ($grant) {
            $role->add_cap($cap);
        } else {
            $role->remove_cap($cap);
        }
    }
}

/**
 * Remove salesperson role on plugin deactivation
 */
function ah_ho_remove_salesperson_role() {
    // Don't remove role if users still have it
    $users = get_users(array('role' => 'ah_ho_salesperson'));
    if (count($users) > 0) {
        return; // Keep role if users exist
    }

    remove_role('ah_ho_salesperson');
}

/**
 * Initialize role on plugin activation
 */
add_action('ah_ho_custom_activate', 'ah_ho_register_salesperson_role');

/**
 * Update role capabilities on plugin updates
 */
add_action('plugins_loaded', 'ah_ho_update_salesperson_role', 5);

/**
 * Clean up role on deactivation (only if no users)
 */
add_action('ah_ho_custom_deactivate', 'ah_ho_remove_salesperson_role');

/**
 * Add commission rate field to user profile when creating/editing salesperson
 */
add_action('show_user_profile', 'ah_ho_add_commission_rate_field');
add_action('edit_user_profile', 'ah_ho_add_commission_rate_field');

function ah_ho_add_commission_rate_field($user) {
    // Only show for salespersons
    if (!in_array('ah_ho_salesperson', $user->roles)) {
        return;
    }

    // Only show if custom rates are enabled
    if (!get_option('ah_ho_enable_custom_rates', true)) {
        return;
    }

    $commission_rate = get_user_meta($user->ID, '_commission_rate', true);
    $default_rate = get_option('ah_ho_default_commission_rate', 10);
    ?>
    <h3><?php _e('Commission Settings', 'ah-ho-custom'); ?></h3>
    <table class="form-table">
        <tr>
            <th><label for="commission_rate"><?php _e('Custom Commission Rate (%)', 'ah-ho-custom'); ?></label></th>
            <td>
                <input type="number"
                       name="commission_rate"
                       id="commission_rate"
                       value="<?php echo esc_attr($commission_rate); ?>"
                       step="0.01"
                       min="0"
                       max="100"
                       class="regular-text" />
                <p class="description">
                    <?php printf(__('Leave blank to use default rate (%s%%)', 'ah-ho-custom'), $default_rate); ?>
                </p>
            </td>
        </tr>
    </table>
    <?php
}

/**
 * Save commission rate field
 */
add_action('personal_options_update', 'ah_ho_save_commission_rate_field');
add_action('edit_user_profile_update', 'ah_ho_save_commission_rate_field');

function ah_ho_save_commission_rate_field($user_id) {
    if (!current_user_can('edit_user', $user_id)) {
        return;
    }

    if (isset($_POST['commission_rate'])) {
        $rate = floatval($_POST['commission_rate']);

        // Validate rate is between 0-100
        if ($rate < 0 || $rate > 100) {
            return;
        }

        update_user_meta($user_id, '_commission_rate', $rate);
    }
}

/**
 * ====================================
 * B2B Enhancement Features v1.6.0
 * ====================================
 */

/**
 * Setup B2B enhancement filters on init
 * Using init hook to ensure WordPress is fully loaded
 */
add_action('init', 'ah_ho_setup_b2b_enhancement_filters', 20);

function ah_ho_setup_b2b_enhancement_filters() {
    // Customer role restrictions for salespersons
    add_filter('editable_roles', 'ah_ho_restrict_salesperson_editable_roles');
    add_filter('pre_option_default_role', 'ah_ho_force_customer_default_role');
    add_filter('user_has_cap', 'ah_ho_restrict_salesperson_user_editing', 10, 4);
}

/**
 * Helper function to check if current user is a salesperson
 */
function ah_ho_is_current_user_salesperson() {
    if (!function_exists('wp_get_current_user')) {
        return false;
    }

    $current_user = wp_get_current_user();

    if (!$current_user || !$current_user->exists() || empty($current_user->roles)) {
        return false;
    }

    return in_array('ah_ho_salesperson', (array) $current_user->roles);
}

/**
 * Restrict salespersons to only see/assign 'customer' role
 */
function ah_ho_restrict_salesperson_editable_roles($roles) {
    if (ah_ho_is_current_user_salesperson()) {
        if (isset($roles['customer'])) {
            return array('customer' => $roles['customer']);
        }
        return array();
    }
    return $roles;
}

/**
 * Force customer role when salesperson creates a user
 */
function ah_ho_force_customer_default_role($default) {
    if (ah_ho_is_current_user_salesperson()) {
        return 'customer';
    }
    return $default;
}

/**
 * Validate role on user creation by salesperson
 */
add_action('user_register', 'ah_ho_validate_salesperson_created_user', 1);

function ah_ho_validate_salesperson_created_user($user_id) {
    if (!ah_ho_is_current_user_salesperson()) {
        return;
    }

    $user = get_userdata($user_id);
    if (!$user) {
        return;
    }

    $user->set_role('customer');
}

/**
 * Restrict which users salespersons can edit
 */
function ah_ho_restrict_salesperson_user_editing($allcaps, $caps, $args, $user) {
    if (!isset($user->roles) || !in_array('ah_ho_salesperson', (array) $user->roles)) {
        return $allcaps;
    }

    if (!in_array('edit_user', $caps) && !in_array('edit_users', $caps)) {
        return $allcaps;
    }

    if (isset($args[2]) && $args[2]) {
        $target_user = get_userdata($args[2]);
        if ($target_user && !in_array('customer', (array) $target_user->roles)) {
            $allcaps['edit_user'] = false;
            $allcaps['edit_users'] = false;
        }
    }

    return $allcaps;
}

/**
 * Add payment terms field to customer profile
 */
add_action('show_user_profile', 'ah_ho_add_payment_terms_field');
add_action('edit_user_profile', 'ah_ho_add_payment_terms_field');
add_action('user_new_form', 'ah_ho_add_payment_terms_field');

function ah_ho_add_payment_terms_field($user) {
    $is_new_user = !is_object($user);

    if (!$is_new_user && !in_array('customer', (array) $user->roles)) {
        return;
    }

    if ($is_new_user && !ah_ho_is_current_user_salesperson() && !current_user_can('manage_options')) {
        return;
    }

    $payment_terms = $is_new_user ? '' : get_user_meta($user->ID, '_payment_terms', true);
    ?>
    <h3><?php _e('Payment Terms', 'ah-ho-custom'); ?></h3>
    <table class="form-table">
        <tr>
            <th><label for="payment_terms"><?php _e('Payment Terms', 'ah-ho-custom'); ?> <span class="description"><?php _e('(required)', 'ah-ho-custom'); ?></span></label></th>
            <td>
                <select name="payment_terms" id="payment_terms" class="regular-text">
                    <option value=""><?php _e('-- Select Payment Terms --', 'ah-ho-custom'); ?></option>
                    <option value="cod" <?php selected($payment_terms, 'cod'); ?>><?php _e('Cash on Delivery (COD)', 'ah-ho-custom'); ?></option>
                    <option value="credit" <?php selected($payment_terms, 'credit'); ?>><?php _e('Credit Terms', 'ah-ho-custom'); ?></option>
                </select>
                <p class="description"><?php _e('Select the payment terms for this customer.', 'ah-ho-custom'); ?></p>
            </td>
        </tr>
    </table>
    <?php
}

/**
 * Save payment terms field
 */
add_action('personal_options_update', 'ah_ho_save_payment_terms_field');
add_action('edit_user_profile_update', 'ah_ho_save_payment_terms_field');
add_action('user_register', 'ah_ho_save_payment_terms_field');

function ah_ho_save_payment_terms_field($user_id) {
    if (!current_user_can('edit_user', $user_id)) {
        return;
    }

    if (isset($_POST['payment_terms'])) {
        $terms = sanitize_text_field($_POST['payment_terms']);
        if (in_array($terms, array('cod', 'credit'))) {
            update_user_meta($user_id, '_payment_terms', $terms);
        }
    }
}

/**
 * Add payment terms column to users list
 */
add_filter('manage_users_columns', 'ah_ho_add_payment_terms_column');

function ah_ho_add_payment_terms_column($columns) {
    $columns['payment_terms'] = __('Payment Terms', 'ah-ho-custom');
    return $columns;
}

/**
 * Display payment terms in users list
 */
add_filter('manage_users_custom_column', 'ah_ho_display_payment_terms_column', 10, 3);

function ah_ho_display_payment_terms_column($value, $column_name, $user_id) {
    if ($column_name === 'payment_terms') {
        $user = get_userdata($user_id);

        if (!in_array('customer', (array) $user->roles)) {
            return '—';
        }

        $terms = get_user_meta($user_id, '_payment_terms', true);

        if ($terms === 'cod') {
            return '<span style="color: #2ea44f;">COD</span>';
        } elseif ($terms === 'credit') {
            return '<span style="color: #dba617;">Credit</span>';
        }

        return '<span style="color: #dc3545;">Not Set</span>';
    }

    return $value;
}

/**
 * Display customer payment terms in order admin
 */
add_action('woocommerce_admin_order_data_after_billing_address', 'ah_ho_display_payment_terms_in_order');

function ah_ho_display_payment_terms_in_order($order) {
    $customer_id = $order->get_customer_id();

    if (!$customer_id) {
        return;
    }

    $payment_terms = get_user_meta($customer_id, '_payment_terms', true);

    if ($payment_terms) {
        $label = $payment_terms === 'cod' ? __('Cash on Delivery', 'ah-ho-custom') : __('Credit Terms', 'ah-ho-custom');
        $color = $payment_terms === 'cod' ? '#2ea44f' : '#dba617';

        printf(
            '<p><strong>%s:</strong> <span style="color: %s; font-weight: bold;">%s</span></p>',
            __('Payment Terms', 'ah-ho-custom'),
            $color,
            esc_html($label)
        );
    }
}


