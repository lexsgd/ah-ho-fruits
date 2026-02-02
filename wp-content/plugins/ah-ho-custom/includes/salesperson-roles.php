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
    // NOTE: edit_others_shop_orders is REQUIRED for HPOS order editing
    // Security is enforced via query filters in salesperson-query-filters.php
    add_role(
        'ah_ho_salesperson',
        __('Salesperson', 'ah-ho-custom'),
        array(
            // Basic WordPress capabilities
            'read'                          => true,

            // WooCommerce order capabilities
            'read_shop_order'               => true,
            'read_shop_orders'              => true,
            'edit_shop_order'               => true,
            'edit_shop_orders'              => true,
            'publish_shop_orders'           => true,
            'create_shop_orders'            => true,

            // HPOS compatibility - required for order editing
            // Security enforced via query filters (Layer 1-4)
            'edit_others_shop_orders'       => true,
            'read_others_shop_orders'       => true,
            'delete_shop_orders'            => false,
            'delete_others_shop_orders'     => false,

            // Product read-only access
            'read_product'                  => true,
            'read_products'                 => true,

            // Customer management (needed for creating orders)
            'list_users'                    => true,
            'read_shop_customer'            => true,

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
    // NOTE: edit_others_shop_orders is REQUIRED for HPOS order editing
    // Security is enforced via query filters in salesperson-query-filters.php
    $capabilities = array(
        'read'                          => true,
        'upload_files'                  => false, // No Media access
        'read_shop_order'               => true,
        'read_shop_orders'              => true,
        'edit_shop_order'               => true,
        'edit_shop_orders'              => true,
        'publish_shop_orders'           => true,
        'create_shop_orders'            => true,
        'edit_others_shop_orders'       => true,  // Required for HPOS - security via query filters
        'read_others_shop_orders'       => true,  // Required for HPOS - security via query filters
        'delete_shop_orders'            => false,
        'delete_others_shop_orders'     => false,
        'read_product'                  => true,
        'read_products'                 => true,
        'edit_product'                  => true,  // Required for adding products to orders
        'edit_products'                 => true,  // Required for product search in orders
        'list_users'                    => true,
        'read_shop_customer'            => true,
        'view_salesperson_commission'   => true,
        // Customer management - security via ah_ho_restrict_salesperson_user_editing filter
        'create_users'                  => true,
        'edit_users'                    => true,
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
 * Allow salespersons to access WP Admin
 * WooCommerce blocks non-admin users by default
 */
add_filter('woocommerce_prevent_admin_access', 'ah_ho_allow_salesperson_admin_access');
add_filter('woocommerce_disable_admin_bar', 'ah_ho_show_salesperson_admin_bar');

function ah_ho_allow_salesperson_admin_access($prevent_access) {
    if (current_user_can('view_salesperson_commission')) {
        return false; // Allow access
    }
    return $prevent_access;
}

function ah_ho_show_salesperson_admin_bar($disable) {
    if (current_user_can('view_salesperson_commission')) {
        return false; // Show admin bar
    }
    return $disable;
}

/**
 * Redirect salespersons to Orders page after login
 * Uses wp_login action which fires after successful authentication
 */
add_action('wp_login', 'ah_ho_salesperson_redirect_after_login', 10, 2);

function ah_ho_salesperson_redirect_after_login($user_login, $user) {
    // Check if user is a salesperson
    if (in_array('ah_ho_salesperson', (array) $user->roles)) {
        wp_safe_redirect(admin_url('admin.php?page=wc-orders'));
        exit;
    }
}

/**
 * Also handle WooCommerce login redirect filter (backup)
 */
add_filter('woocommerce_login_redirect', 'ah_ho_wc_salesperson_login_redirect', 99, 2);

function ah_ho_wc_salesperson_login_redirect($redirect, $user) {
    if ($user && in_array('ah_ho_salesperson', (array) $user->roles)) {
        return admin_url('admin.php?page=wc-orders');
    }
    return $redirect;
}

/**
 * WordPress login redirect filter (backup)
 */
add_filter('login_redirect', 'ah_ho_wp_salesperson_login_redirect', 99, 3);

function ah_ho_wp_salesperson_login_redirect($redirect_to, $requested_redirect_to, $user) {
    if (!is_wp_error($user) && in_array('ah_ho_salesperson', (array) $user->roles)) {
        return admin_url('admin.php?page=wc-orders');
    }
    return $redirect_to;
}

/**
 * Hide Dashboard menu for salespersons
 */
add_action('admin_menu', 'ah_ho_hide_dashboard_for_salesperson', 999);

function ah_ho_hide_dashboard_for_salesperson() {
    if (current_user_can('view_salesperson_commission') && !current_user_can('manage_options')) {
        remove_menu_page('index.php'); // Dashboard
    }
}

/**
 * ========================================
 * CUSTOMER MANAGEMENT SECURITY
 * ========================================
 * Salespersons can only:
 * - Edit their own profile
 * - Create/edit users with 'customer' role
 * - Assign only the 'customer' role
 */

/**
 * Restrict which roles salespersons can see/assign
 * Only show 'customer' role in the dropdown
 */
add_filter('editable_roles', 'ah_ho_restrict_salesperson_editable_roles');

function ah_ho_restrict_salesperson_editable_roles($roles) {
    // Only restrict for salespersons
    if (!ah_ho_is_current_user_salesperson()) {
        return $roles;
    }

    // Only allow customer role
    if (isset($roles['customer'])) {
        return array('customer' => $roles['customer']);
    }

    return array();
}

/**
 * Prevent salespersons from editing privileged users
 * Allow: own profile, customers, subscribers, any non-privileged role
 * Deny: admins, shop_managers, other salespersons, editors, authors
 */
add_filter('user_has_cap', 'ah_ho_restrict_salesperson_user_editing', 10, 4);

function ah_ho_restrict_salesperson_user_editing($allcaps, $caps, $args, $user) {
    // Only check for edit_user capability
    if (!isset($args[0]) || $args[0] !== 'edit_user') {
        return $allcaps;
    }

    // Only restrict salespersons
    if (!in_array('ah_ho_salesperson', (array) $user->roles)) {
        return $allcaps;
    }

    // Get the user being edited
    $target_user_id = isset($args[2]) ? $args[2] : 0;
    if (!$target_user_id) {
        return $allcaps;
    }

    // Allow editing own profile
    if ($target_user_id == $user->ID) {
        return $allcaps;
    }

    // Check if target user has a privileged role
    $target_user = get_userdata($target_user_id);
    if (!$target_user) {
        $allcaps['edit_users'] = false;
        return $allcaps;
    }

    // Privileged roles that salespersons cannot edit
    $protected_roles = array(
        'administrator',
        'shop_manager',
        'ah_ho_salesperson',
        'editor',
        'author',
    );

    // Check if target user has any protected role
    $target_roles = (array) $target_user->roles;
    foreach ($protected_roles as $protected_role) {
        if (in_array($protected_role, $target_roles)) {
            $allcaps['edit_users'] = false;
            return $allcaps;
        }
    }

    // Allow editing - user has non-privileged role (customer, subscriber, etc.)
    return $allcaps;
}

/**
 * Force customer role when salesperson creates a new user
 * Prevents privilege escalation
 */
add_action('user_register', 'ah_ho_force_customer_role_for_salesperson_created_users', 10, 1);

function ah_ho_force_customer_role_for_salesperson_created_users($user_id) {
    // Only apply when salesperson creates user
    if (!ah_ho_is_current_user_salesperson()) {
        return;
    }

    // Force customer role
    $user = new WP_User($user_id);
    $user->set_role('customer');
}

/**
 * Filter user list to only show customers for salespersons
 */
add_action('pre_get_users', 'ah_ho_filter_user_list_for_salesperson');

function ah_ho_filter_user_list_for_salesperson($query) {
    // Only on admin user list
    if (!is_admin()) {
        return;
    }

    // Only for salespersons
    if (!ah_ho_is_current_user_salesperson()) {
        return;
    }

    // Only show customers
    $query->set('role', 'customer');
}

/**
 * Helper: Check if current user is a salesperson
 */
function ah_ho_is_current_user_salesperson() {
    $current_user = wp_get_current_user();
    if (!$current_user || !$current_user->ID) {
        return false;
    }
    return in_array('ah_ho_salesperson', (array) $current_user->roles);
}



