<?php
/**
 * B2B Sales Enhancement Features
 *
 * - Customer create/edit capabilities for salespersons
 * - Payment terms field for customers
 *
 * @since 1.6.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Helper function to check if current user is a salesperson
 * Safe to call at any point - returns false if user not available
 */
function ah_ho_is_current_user_salesperson() {
    if (!function_exists('wp_get_current_user') || !did_action('init')) {
        return false;
    }

    $current_user = wp_get_current_user();

    if (!$current_user || !$current_user->exists()) {
        return false;
    }

    if (empty($current_user->roles)) {
        return false;
    }

    return in_array('ah_ho_salesperson', (array) $current_user->roles);
}

/**
 * Add salesperson customer management capabilities
 * Runs once on plugin update to add new caps
 */
add_action('admin_init', 'ah_ho_add_salesperson_customer_caps', 5);

function ah_ho_add_salesperson_customer_caps() {
    // Only run once per version
    $version = get_option('ah_ho_b2b_caps_version', '0');
    if (version_compare($version, '1.6.0', '>=')) {
        return;
    }

    $role = get_role('ah_ho_salesperson');
    if ($role) {
        $role->add_cap('create_users');
        $role->add_cap('edit_users');
        update_option('ah_ho_b2b_caps_version', '1.6.0');
    }
}

/**
 * Restrict salespersons to only see/assign 'customer' role
 */
add_filter('editable_roles', 'ah_ho_restrict_salesperson_editable_roles');

function ah_ho_restrict_salesperson_editable_roles($roles) {
    if (!is_admin()) {
        return $roles;
    }

    if (!ah_ho_is_current_user_salesperson()) {
        return $roles;
    }

    // Only allow customer role for salespersons
    if (isset($roles['customer'])) {
        return array('customer' => $roles['customer']);
    }

    return array();
}

/**
 * Force customer role when salesperson creates a user
 */
add_filter('pre_option_default_role', 'ah_ho_force_customer_default_role');

function ah_ho_force_customer_default_role($default) {
    if (!is_admin()) {
        return $default;
    }

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
    if ($user) {
        $user->set_role('customer');
    }
}

/**
 * Restrict which users salespersons can edit
 */
add_filter('user_has_cap', 'ah_ho_restrict_salesperson_user_editing', 10, 4);

function ah_ho_restrict_salesperson_user_editing($allcaps, $caps, $args, $user) {
    // Only filter in admin
    if (!is_admin()) {
        return $allcaps;
    }

    // Only filter for salespersons
    if (!isset($user->roles) || !in_array('ah_ho_salesperson', (array) $user->roles)) {
        return $allcaps;
    }

    // Check if this is an edit_user capability check
    if (!in_array('edit_user', $caps) && !in_array('edit_users', $caps)) {
        return $allcaps;
    }

    // If editing a specific user
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
    // Determine if this is a new user form or existing user profile
    $is_new_user = !is_object($user);

    // For existing users, only show for customers
    if (!$is_new_user) {
        if (!isset($user->roles) || !in_array('customer', (array) $user->roles)) {
            return;
        }
    }

    // For new user form, show only for those who can create users
    if ($is_new_user && !current_user_can('create_users')) {
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
    if ($column_name !== 'payment_terms') {
        return $value;
    }

    $user = get_userdata($user_id);
    if (!$user || !in_array('customer', (array) $user->roles)) {
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
