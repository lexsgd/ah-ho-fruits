<?php
/**
 * Salesperson & Storeman Roles & Permissions
 *
 * Registers the B2B salesperson and storeman roles with restricted access
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
 * Register storeman role on plugin activation
 * Similar to salesperson but with product inventory editing
 */
function ah_ho_register_storeman_role() {
    if (get_role('ah_ho_storeman')) {
        return;
    }

    add_role(
        'ah_ho_storeman',
        __('Storeman', 'ah-ho-custom'),
        array(
            // Basic WordPress capabilities
            'read'                          => true,

            // WooCommerce order capabilities (same as salesperson)
            'read_shop_order'               => true,
            'read_shop_orders'              => true,
            'edit_shop_order'               => true,
            'edit_shop_orders'              => true,
            'publish_shop_orders'           => true,
            'create_shop_orders'            => true,

            // HPOS compatibility
            'edit_others_shop_orders'       => true,
            'read_others_shop_orders'       => true,
            'delete_shop_orders'            => false,
            'delete_others_shop_orders'     => false,

            // Product access - read AND edit for inventory management
            'read_product'                  => true,
            'read_products'                 => true,
            'edit_product'                  => true,
            'edit_products'                 => true,
            'edit_others_products'          => true,
            'edit_published_products'       => true,

            // Customer management
            'list_users'                    => true,
            'read_shop_customer'            => true,

            // Commission viewing
            'view_salesperson_commission'   => true,
        )
    );
}

/**
 * Update storeman role capabilities
 */
function ah_ho_update_storeman_role() {
    $role = get_role('ah_ho_storeman');

    if (!$role) {
        ah_ho_register_storeman_role();
        error_log('Ah Ho Custom: Storeman role created via plugins_loaded hook');
        return;
    }

    $capabilities = array(
        'read'                          => true,
        'upload_files'                  => false,
        'read_shop_order'               => true,
        'read_shop_orders'              => true,
        'edit_shop_order'               => true,
        'edit_shop_orders'              => true,
        'publish_shop_orders'           => true,
        'create_shop_orders'            => true,
        'edit_others_shop_orders'       => true,
        'read_others_shop_orders'       => true,
        'delete_shop_orders'            => false,
        'delete_others_shop_orders'     => false,
        'read_product'                  => true,
        'read_products'                 => true,
        'edit_product'                  => true,
        'edit_products'                 => true,
        'edit_others_products'          => true,
        'edit_published_products'       => true,
        'list_users'                    => true,
        'read_shop_customer'            => true,
        'view_salesperson_commission'   => true,
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
 * Initialize roles on plugin activation
 */
add_action('ah_ho_custom_activate', 'ah_ho_register_salesperson_role');
add_action('ah_ho_custom_activate', 'ah_ho_register_storeman_role');

/**
 * Update role capabilities on plugin updates
 */
add_action('plugins_loaded', 'ah_ho_update_salesperson_role', 5);
add_action('plugins_loaded', 'ah_ho_update_storeman_role', 5);

/**
 * Clean up roles on deactivation (only if no users)
 */
add_action('ah_ho_custom_deactivate', 'ah_ho_remove_salesperson_role');

/**
 * ========================================
 * CUSTOMER PAYMENT TERMS FIELD
 * ========================================
 * Track whether customer pays COD or has Credit Terms
 * This is stored on the customer profile, not per-order
 */

/**
 * Add payment terms field to customer profile
 */
add_action('show_user_profile', 'ah_ho_add_payment_terms_field');
add_action('edit_user_profile', 'ah_ho_add_payment_terms_field');
add_action('user_new_form', 'ah_ho_add_payment_terms_field_new_user');

function ah_ho_add_payment_terms_field($user) {
    // Only show for customers
    if (!in_array('customer', (array) $user->roles)) {
        return;
    }

    $payment_terms = get_user_meta($user->ID, '_payment_terms', true);
    ?>
    <h3><?php _e('B2B Payment Terms', 'ah-ho-custom'); ?></h3>
    <table class="form-table">
        <tr>
            <th><label for="payment_terms"><?php _e('Payment Terms', 'ah-ho-custom'); ?></label></th>
            <td>
                <select name="payment_terms" id="payment_terms" style="min-width: 200px;">
                    <option value=""><?php _e('-- Select Payment Terms --', 'ah-ho-custom'); ?></option>
                    <option value="cod" <?php selected($payment_terms, 'cod'); ?>><?php _e('COD (Cash on Delivery)', 'ah-ho-custom'); ?></option>
                    <option value="credit_7" <?php selected($payment_terms, 'credit_7'); ?>><?php _e('Credit - 7 Days', 'ah-ho-custom'); ?></option>
                    <option value="credit_14" <?php selected($payment_terms, 'credit_14'); ?>><?php _e('Credit - 14 Days', 'ah-ho-custom'); ?></option>
                    <option value="credit_30" <?php selected($payment_terms, 'credit_30'); ?>><?php _e('Credit - 30 Days', 'ah-ho-custom'); ?></option>
                </select>
                <p class="description">
                    <?php _e('COD = Payment collected on delivery. Credit = Invoice sent, payment due within X days.', 'ah-ho-custom'); ?>
                </p>
            </td>
        </tr>
    </table>
    <?php
}

/**
 * Add payment terms field to new user form
 */
function ah_ho_add_payment_terms_field_new_user($operation) {
    // Only show when adding new user and current user is salesperson or admin
    if ($operation !== 'add-new-user') {
        return;
    }
    ?>
    <h3><?php _e('B2B Payment Terms', 'ah-ho-custom'); ?></h3>
    <table class="form-table">
        <tr>
            <th><label for="payment_terms"><?php _e('Payment Terms', 'ah-ho-custom'); ?></label></th>
            <td>
                <select name="payment_terms" id="payment_terms" style="min-width: 200px;">
                    <option value=""><?php _e('-- Select Payment Terms --', 'ah-ho-custom'); ?></option>
                    <option value="cod"><?php _e('COD (Cash on Delivery)', 'ah-ho-custom'); ?></option>
                    <option value="credit_7"><?php _e('Credit - 7 Days', 'ah-ho-custom'); ?></option>
                    <option value="credit_14"><?php _e('Credit - 14 Days', 'ah-ho-custom'); ?></option>
                    <option value="credit_30"><?php _e('Credit - 30 Days', 'ah-ho-custom'); ?></option>
                </select>
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
        $valid_terms = array('', 'cod', 'credit_7', 'credit_14', 'credit_30');

        if (in_array($terms, $valid_terms)) {
            update_user_meta($user_id, '_payment_terms', $terms);
        }
    }
}

/**
 * Add payment terms column to users list
 */
add_filter('manage_users_columns', 'ah_ho_add_payment_terms_column');
add_filter('manage_users_custom_column', 'ah_ho_display_payment_terms_column', 10, 3);

function ah_ho_add_payment_terms_column($columns) {
    $columns['payment_terms'] = __('Payment Terms', 'ah-ho-custom');
    return $columns;
}

function ah_ho_display_payment_terms_column($value, $column_name, $user_id) {
    if ($column_name === 'payment_terms') {
        $user = get_userdata($user_id);

        // Only show for customers
        if (!in_array('customer', (array) $user->roles)) {
            return '—';
        }

        $terms = get_user_meta($user_id, '_payment_terms', true);
        $labels = array(
            'cod' => '<span style="background:#2ea44f;color:#fff;padding:2px 8px;border-radius:3px;font-size:11px;">COD</span>',
            'credit_7' => '<span style="background:#dba617;color:#fff;padding:2px 8px;border-radius:3px;font-size:11px;">Credit 7d</span>',
            'credit_14' => '<span style="background:#f56e28;color:#fff;padding:2px 8px;border-radius:3px;font-size:11px;">Credit 14d</span>',
            'credit_30' => '<span style="background:#b32d2e;color:#fff;padding:2px 8px;border-radius:3px;font-size:11px;">Credit 30d</span>',
        );

        return isset($labels[$terms]) ? $labels[$terms] : '<span style="color:#999;">Not set</span>';
    }
    return $value;
}

/**
 * Add commission rate field to user profile when creating/editing salesperson
 */
add_action('show_user_profile', 'ah_ho_add_commission_rate_field');
add_action('edit_user_profile', 'ah_ho_add_commission_rate_field');

function ah_ho_add_commission_rate_field($user) {
    // Only show for salespersons and storemen
    if (!in_array('ah_ho_salesperson', $user->roles) && !in_array('ah_ho_storeman', $user->roles)) {
        return;
    }

    // Only show if custom rates are enabled
    if (!get_option('ah_ho_enable_custom_rates', true)) {
        return;
    }

    $commission_rate = get_user_meta($user->ID, '_commission_rate', true);
    $default_rate = get_option('ah_ho_default_commission_rate', 10);
    $per_carton_rate = get_user_meta($user->ID, '_commission_per_carton_rate', true);
    $default_per_carton_rate = get_option('ah_ho_default_per_carton_rate', 0);
    ?>
    <h3><?php _e('Commission Settings', 'ah-ho-custom'); ?></h3>
    <table class="form-table">
        <tr>
            <th><label for="commission_rate"><?php _e('Percentage Commission Rate (%)', 'ah-ho-custom'); ?></label></th>
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
                    <?php printf(__('Percentage of order total. Leave blank to use default rate (%s%%)', 'ah-ho-custom'), $default_rate); ?>
                </p>
            </td>
        </tr>
        <tr>
            <th><label for="commission_per_carton_rate"><?php _e('Per-Carton Commission ($)', 'ah-ho-custom'); ?></label></th>
            <td>
                <input type="number"
                       name="commission_per_carton_rate"
                       id="commission_per_carton_rate"
                       value="<?php echo esc_attr($per_carton_rate); ?>"
                       step="0.01"
                       min="0"
                       class="regular-text" />
                <p class="description">
                    <?php printf(__('Fixed dollar amount per carton. Leave blank to use default rate ($%s)', 'ah-ho-custom'), number_format($default_per_carton_rate, 2)); ?>
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

    if (isset($_POST['commission_per_carton_rate'])) {
        $per_carton_rate = floatval($_POST['commission_per_carton_rate']);

        if ($per_carton_rate < 0) {
            $per_carton_rate = 0;
        }

        update_user_meta($user_id, '_commission_per_carton_rate', $per_carton_rate);
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
    // Check if user is a salesperson or storeman
    if (in_array('ah_ho_salesperson', (array) $user->roles) || in_array('ah_ho_storeman', (array) $user->roles)) {
        wp_safe_redirect(admin_url('admin.php?page=wc-orders'));
        exit;
    }
}

/**
 * Also handle WooCommerce login redirect filter (backup)
 */
add_filter('woocommerce_login_redirect', 'ah_ho_wc_salesperson_login_redirect', 99, 2);

function ah_ho_wc_salesperson_login_redirect($redirect, $user) {
    if ($user && (in_array('ah_ho_salesperson', (array) $user->roles) || in_array('ah_ho_storeman', (array) $user->roles))) {
        return admin_url('admin.php?page=wc-orders');
    }
    return $redirect;
}

/**
 * WordPress login redirect filter (backup)
 */
add_filter('login_redirect', 'ah_ho_wp_salesperson_login_redirect', 99, 3);

function ah_ho_wp_salesperson_login_redirect($redirect_to, $requested_redirect_to, $user) {
    if (!is_wp_error($user) && (in_array('ah_ho_salesperson', (array) $user->roles) || in_array('ah_ho_storeman', (array) $user->roles))) {
        return admin_url('admin.php?page=wc-orders');
    }
    return $redirect_to;
}

/**
 * Hide unnecessary menu items for salespersons
 * Keep only: WooCommerce > Orders, My Commission, Users
 */
add_action('admin_menu', 'ah_ho_hide_menus_for_salesperson', 999);

function ah_ho_hide_menus_for_salesperson() {
    if (!current_user_can('view_salesperson_commission') || current_user_can('manage_options')) {
        return;
    }

    $current_user = wp_get_current_user();
    $is_storeman = in_array('ah_ho_storeman', (array) $current_user->roles);

    // Hide top-level menus
    remove_menu_page('index.php');                    // Dashboard
    remove_menu_page('upload.php');                   // Media
    remove_menu_page('woocommerce-marketing');        // Marketing
    remove_menu_page('admin.php?page=wc-settings&tab=checkout&from=PAYMENTS_MENU_ITEM'); // Payments
    remove_menu_page('ah-ho-pdf-bulk');               // PDF Documents

    // Storeman keeps Products menu for inventory management
    // Salesperson does not get Products menu
    if (!$is_storeman) {
        remove_menu_page('edit.php?post_type=product');
    }

    // Hide WooCommerce submenus (keep only Orders)
    remove_submenu_page('woocommerce', 'wc-admin');                    // Home
    remove_submenu_page('woocommerce', 'payment-gateway-fees');        // Gateway Fees
    remove_submenu_page('woocommerce', 'wc-stripe-main');              // Stripe by Payment Plugins
    remove_submenu_page('woocommerce', 'wc-settings');                 // Settings
    remove_submenu_page('woocommerce', 'wc-status');                   // Status

    // Also try alternative menu slugs for Payments
    global $menu;
    if (is_array($menu)) {
        foreach ($menu as $key => $item) {
            if (isset($item[2])) {
                // Remove Payments menu (various possible slugs)
                if (strpos($item[2], 'wc-settings') !== false && strpos($item[2], 'checkout') !== false) {
                    unset($menu[$key]);
                }
                if ($item[2] === 'wc-admin&path=/payments/overview') {
                    unset($menu[$key]);
                }
            }
        }
    }
}

/**
 * Remove WooCommerce admin bar menu items for salespersons
 */
add_action('admin_bar_menu', 'ah_ho_clean_admin_bar_for_salesperson', 999);

function ah_ho_clean_admin_bar_for_salesperson($wp_admin_bar) {
    if (!current_user_can('view_salesperson_commission') || current_user_can('manage_options')) {
        return;
    }

    // Remove "New" menu items salespersons don't need
    $wp_admin_bar->remove_node('new-media');
    $wp_admin_bar->remove_node('new-post');
    $wp_admin_bar->remove_node('new-page');
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
    // Only restrict for salespersons and storemen
    if (!ah_ho_is_current_user_staff()) {
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

    // Only restrict salespersons and storemen
    if (!in_array('ah_ho_salesperson', (array) $user->roles) && !in_array('ah_ho_storeman', (array) $user->roles)) {
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

    // Privileged roles that salespersons/storemen cannot edit
    $protected_roles = array(
        'administrator',
        'shop_manager',
        'ah_ho_salesperson',
        'ah_ho_storeman',
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

    // Only allow editing customers created by this staff member
    $created_by = get_user_meta($target_user_id, '_created_by_staff_id', true);
    if ($created_by && (int) $created_by !== $user->ID) {
        $allcaps['edit_users'] = false;
        return $allcaps;
    }
    // Also block if customer has no creator meta (created by admin/web)
    if (!$created_by) {
        $allcaps['edit_users'] = false;
        return $allcaps;
    }

    // Allow editing - customer was created by this staff member
    return $allcaps;
}

/**
 * Force customer role when salesperson creates a new user
 * Prevents privilege escalation
 */
add_action('user_register', 'ah_ho_force_customer_role_for_salesperson_created_users', 10, 1);

function ah_ho_force_customer_role_for_salesperson_created_users($user_id) {
    // Only apply when salesperson/storeman creates user
    if (!ah_ho_is_current_user_staff()) {
        return;
    }

    // Force customer role
    $user = new WP_User($user_id);
    $user->set_role('customer');

    // Track which staff member created this customer
    update_user_meta($user_id, '_created_by_staff_id', get_current_user_id());
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

    // Only for salespersons and storemen
    if (!ah_ho_is_current_user_staff()) {
        return;
    }

    // Only show customers
    $query->set('role', 'customer');

    // Only show customers created by this staff member
    $meta_query = $query->get('meta_query') ?: array();
    $meta_query[] = array(
        'key'     => '_created_by_staff_id',
        'value'   => get_current_user_id(),
        'compare' => '='
    );
    $query->set('meta_query', $meta_query);
}

/**
 * Helper: Check if current user is a salesperson or storeman
 */
function ah_ho_is_current_user_salesperson() {
    $current_user = wp_get_current_user();
    if (!$current_user || !$current_user->ID) {
        return false;
    }
    $roles = (array) $current_user->roles;
    return in_array('ah_ho_salesperson', $roles) || in_array('ah_ho_storeman', $roles);
}

/**
 * Helper: Check if current user is a salesperson OR storeman
 * Used for shared functionality (orders, commissions, customer management)
 */
function ah_ho_is_current_user_staff() {
    $current_user = wp_get_current_user();
    if (!$current_user || !$current_user->ID) {
        return false;
    }
    $roles = (array) $current_user->roles;
    return in_array('ah_ho_salesperson', $roles) || in_array('ah_ho_storeman', $roles);
}



