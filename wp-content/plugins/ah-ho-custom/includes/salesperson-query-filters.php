<?php
/**
 * Salesperson Query Filtering - Multi-Layer Security (HPOS Compatible)
 *
 * Ensures salespersons can only access their own assigned orders
 * Implements 4 layers of protection to prevent cross-salesperson data access
 *
 * Compatible with WooCommerce HPOS (High-Performance Order Storage)
 *
 * @since 1.3.0
 * @version 1.4.0 - HPOS compatibility rewrite
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Helper function to check if current user is a salesperson
 */
if (!function_exists('ah_ho_is_current_user_salesperson')) {
    function ah_ho_is_current_user_salesperson() {
        $user = wp_get_current_user();
        return in_array('ah_ho_salesperson', (array) $user->roles);
    }
}

/**
 * CRITICAL: Grant salesperson order editing capabilities directly
 *
 * This runs very early and grants the edit_others_shop_orders capability
 * to the current user's allcaps if they're a salesperson.
 * This is needed for WooCommerce HPOS order editing.
 */
add_filter('user_has_cap', 'ah_ho_grant_salesperson_edit_cap', 1, 4);

function ah_ho_grant_salesperson_edit_cap($allcaps, $caps, $args, $user) {
    // Only for salespersons
    if (!in_array('ah_ho_salesperson', (array) $user->roles)) {
        return $allcaps;
    }

    // Always grant edit_others_shop_orders for salespersons
    // Security is enforced via query filters (they can only SEE their own orders)
    // and via the ah_ho_prevent_unauthorized_order_access function
    $allcaps['edit_others_shop_orders'] = true;
    $allcaps['read_others_shop_orders'] = true;
    $allcaps['edit_shop_orders'] = true;
    $allcaps['edit_shop_order'] = true;

    return $allcaps;
}

/**
 * Map meta capabilities for salesperson order access
 *
 * This filter modifies what capabilities WordPress checks.
 * For salespersons, we allow them to edit orders they're assigned to.
 */
add_filter('map_meta_cap', 'ah_ho_map_order_meta_cap', 1, 4);

function ah_ho_map_order_meta_cap($caps, $cap, $user_id, $args) {
    // Only modify shop_order capabilities
    if (!in_array($cap, array('edit_shop_order', 'read_shop_order', 'delete_shop_order'))) {
        return $caps;
    }

    // Check if user is a salesperson
    $user = get_userdata($user_id);
    if (!$user || !in_array('ah_ho_salesperson', (array) $user->roles)) {
        return $caps;
    }

    // For salespersons, return the capability they have
    if ($cap === 'edit_shop_order') {
        return array('edit_shop_orders');
    }
    if ($cap === 'read_shop_order') {
        return array('read_shop_orders');
    }

    return $caps;
}

/**
 * Helper function to get assigned salesperson from an order (HPOS compatible)
 *
 * @param int|WC_Order $order Order ID or order object
 * @return int|null Salesperson user ID or null
 */
if (!function_exists('ah_ho_get_order_salesperson')) {
    function ah_ho_get_order_salesperson($order) {
        if (is_numeric($order)) {
            $order = wc_get_order($order);
        }

        if (!$order instanceof WC_Order) {
            return null;
        }

        $salesperson_id = $order->get_meta('_assigned_salesperson_id', true);
        return $salesperson_id ? (int) $salesperson_id : null;
    }
}

/**
 * LAYER 1: Filter WooCommerce Order Queries (HPOS Compatible)
 *
 * Uses woocommerce_order_query_args filter which works with both
 * legacy post-based storage and HPOS custom tables
 */
add_filter('woocommerce_order_query_args', 'ah_ho_filter_salesperson_order_query', 10, 1);

function ah_ho_filter_salesperson_order_query($query_args) {
    // Only apply in admin area
    if (!is_admin()) {
        return $query_args;
    }

    // Only filter for salespersons
    if (!ah_ho_is_current_user_salesperson()) {
        return $query_args;
    }

    // Add meta query to filter by assigned salesperson
    if (!isset($query_args['meta_query'])) {
        $query_args['meta_query'] = array();
    }

    $query_args['meta_query'][] = array(
        'key'     => '_assigned_salesperson_id',
        'value'   => get_current_user_id(),
        'compare' => '='
    );

    return $query_args;
}

/**
 * LAYER 2: Filter Admin Orders List Table (HPOS Compatible)
 *
 * This hook fires when WooCommerce prepares the orders list in admin
 */
add_filter('woocommerce_shop_order_list_table_prepare_items_query_args', 'ah_ho_filter_orders_list_table', 10, 1);

function ah_ho_filter_orders_list_table($query_args) {
    // Only filter for salespersons
    if (!ah_ho_is_current_user_salesperson()) {
        return $query_args;
    }

    // Add meta query filter
    if (!isset($query_args['meta_query'])) {
        $query_args['meta_query'] = array();
    }

    $query_args['meta_query'][] = array(
        'key'     => '_assigned_salesperson_id',
        'value'   => get_current_user_id(),
        'compare' => '='
    );

    return $query_args;
}

/**
 * LAYER 3: Direct Order Access Prevention (HPOS Compatible)
 *
 * Prevent accessing order edit page via direct URL
 * Uses the admin_init hook and checks the order object directly
 */
add_action('admin_init', 'ah_ho_prevent_unauthorized_order_access');

function ah_ho_prevent_unauthorized_order_access() {
    global $pagenow;

    // Check if we're on the order edit page
    // HPOS uses admin.php?page=wc-orders&action=edit&id=XXX
    // Legacy uses post.php?post=XXX&action=edit

    $order_id = null;

    // HPOS order edit page
    if ($pagenow === 'admin.php' && isset($_GET['page']) && $_GET['page'] === 'wc-orders') {
        if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
            $order_id = absint($_GET['id']);
        }
    }

    // Legacy order edit page (fallback for when HPOS is disabled)
    if ($pagenow === 'post.php' && isset($_GET['post']) && isset($_GET['action']) && $_GET['action'] === 'edit') {
        $post_id = absint($_GET['post']);
        if (get_post_type($post_id) === 'shop_order') {
            $order_id = $post_id;
        }
    }

    // No order being accessed
    if (!$order_id) {
        return;
    }

    // Only check for salespersons
    if (!ah_ho_is_current_user_salesperson()) {
        return;
    }

    // Get the order and check assignment
    $order = wc_get_order($order_id);
    if (!$order) {
        return;
    }

    $assigned_salesperson = ah_ho_get_order_salesperson($order);
    $current_user_id = get_current_user_id();

    // If order is assigned to current user, allow access
    if ($assigned_salesperson === $current_user_id) {
        return;
    }

    // If order has NO salesperson assigned, auto-assign current salesperson
    // This handles new orders where the woocommerce_new_order hook may not have saved the meta yet
    if ($assigned_salesperson === null) {
        $order->update_meta_data('_assigned_salesperson_id', $current_user_id);
        $order->update_meta_data('_commission_status', 'pending');
        $order->save();

        // Log the auto-assignment for debugging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                '[Ah Ho Custom] Auto-assigned order #%d to salesperson %d (fallback assignment)',
                $order_id,
                $current_user_id
            ));
        }

        return; // Allow access after assignment
    }

    // Order is assigned to someone else - deny access
    ah_ho_log_unauthorized_access($order_id, $current_user_id);

    wp_die(
        __('You do not have permission to access this order.', 'ah-ho-custom'),
        __('Access Denied', 'ah-ho-custom'),
        array('response' => 403, 'back_link' => true)
    );
}

/**
 * LAYER 4: REST API Protection (HPOS Compatible)
 *
 * Prevent unauthorized API access to orders
 */
add_filter('woocommerce_rest_check_permissions', 'ah_ho_rest_order_permissions', 10, 4);

function ah_ho_rest_order_permissions($permission, $context, $object_id, $post_type) {
    // Only check shop_order permissions
    if ($post_type !== 'shop_order') {
        return $permission;
    }

    // Only filter for salespersons
    if (!ah_ho_is_current_user_salesperson()) {
        return $permission;
    }

    // If accessing a specific order
    if ($object_id) {
        $assigned_salesperson = ah_ho_get_order_salesperson($object_id);

        // Deny if not assigned to this salesperson
        if ($assigned_salesperson !== get_current_user_id()) {
            ah_ho_log_unauthorized_access($object_id, get_current_user_id());
            return false;
        }
    }

    return $permission;
}

/**
 * Filter REST API collection queries for salespersons
 * Ensures listing endpoints only return assigned orders
 */
add_filter('woocommerce_rest_orders_prepare_object_query', 'ah_ho_filter_rest_orders_query', 10, 2);

function ah_ho_filter_rest_orders_query($args, $request) {
    // Only filter for salespersons
    if (!ah_ho_is_current_user_salesperson()) {
        return $args;
    }

    // Add meta query to filter by assigned salesperson
    if (!isset($args['meta_query'])) {
        $args['meta_query'] = array();
    }

    $args['meta_query'][] = array(
        'key'     => '_assigned_salesperson_id',
        'value'   => get_current_user_id(),
        'compare' => '='
    );

    return $args;
}

/**
 * Filter WooCommerce order count for salespersons (HPOS Compatible)
 *
 * Ensures order counts in admin menu reflect only assigned orders
 */
add_filter('woocommerce_orders_count', 'ah_ho_filter_order_count', 10, 2);

function ah_ho_filter_order_count($count, $status) {
    // Only filter for salespersons
    if (!ah_ho_is_current_user_salesperson()) {
        return $count;
    }

    // Format status properly for wc_get_orders
    $order_status = strpos($status, 'wc-') === 0 ? $status : 'wc-' . $status;

    // Count only assigned orders using wc_get_orders (HPOS compatible)
    $orders = wc_get_orders(array(
        'status'     => $order_status,
        'limit'      => -1,
        'return'     => 'ids',
        'meta_query' => array(
            array(
                'key'     => '_assigned_salesperson_id',
                'value'   => get_current_user_id(),
                'compare' => '='
            )
        )
    ));

    return count($orders);
}

/**
 * Hide unassigned orders from salesperson dashboard widget
 */
add_action('wp_dashboard_setup', 'ah_ho_filter_dashboard_widget', 20);

function ah_ho_filter_dashboard_widget() {
    // Only for salespersons
    if (!ah_ho_is_current_user_salesperson()) {
        return;
    }

    // Remove default WooCommerce status widget (shows all orders)
    remove_meta_box('woocommerce_dashboard_status', 'dashboard', 'normal');
}

/**
 * Grant salespersons permission to edit their own orders and unassigned orders (HPOS Compatible)
 *
 * This filter intercepts capability checks to allow salespersons to:
 * 1. Edit orders assigned to them
 * 2. Edit newly created orders (no salesperson assigned yet)
 */
add_filter('user_has_cap', 'ah_ho_grant_salesperson_order_caps', 10, 4);

function ah_ho_grant_salesperson_order_caps($allcaps, $caps, $args, $user) {
    // Check if user is a salesperson
    if (!in_array('ah_ho_salesperson', (array) $user->roles)) {
        return $allcaps;
    }

    // Check if this is an order-related capability check
    $order_caps = array('edit_shop_order', 'read_shop_order', 'delete_shop_order', 'edit_others_shop_orders');
    $is_order_cap = false;
    foreach ($caps as $cap) {
        if (in_array($cap, $order_caps)) {
            $is_order_cap = true;
            break;
        }
    }

    if (!$is_order_cap) {
        return $allcaps;
    }

    // Get the order ID from the args (if checking a specific order)
    $order_id = isset($args[2]) ? $args[2] : 0;

    if ($order_id) {
        $order = wc_get_order($order_id);

        if ($order) {
            $assigned_salesperson = $order->get_meta('_assigned_salesperson_id', true);

            // Allow if order is assigned to this salesperson
            if ($assigned_salesperson && (int) $assigned_salesperson === $user->ID) {
                $allcaps['edit_shop_order'] = true;
                $allcaps['edit_shop_orders'] = true;
                $allcaps['edit_others_shop_orders'] = true;
                $allcaps['read_shop_order'] = true;
                return $allcaps;
            }

            // Allow if order has no salesperson assigned (new order)
            // Also auto-assign the order to this salesperson
            if (!$assigned_salesperson) {
                $order->update_meta_data('_assigned_salesperson_id', $user->ID);
                $order->update_meta_data('_commission_status', 'pending');
                $order->save();

                $allcaps['edit_shop_order'] = true;
                $allcaps['edit_shop_orders'] = true;
                $allcaps['edit_others_shop_orders'] = true;
                $allcaps['read_shop_order'] = true;
                return $allcaps;
            }

            // Order is assigned to someone else - deny (keep original caps)
            return $allcaps;
        }
    }

    return $allcaps;
}

/**
 * Additional security: Prevent salespersons from viewing other salespersons' profiles
 */
add_filter('user_has_cap', 'ah_ho_restrict_user_profile_access', 20, 4);

function ah_ho_restrict_user_profile_access($allcaps, $caps, $args, $user) {
    // Check if user is a salesperson
    if (!in_array('ah_ho_salesperson', (array) $user->roles)) {
        return $allcaps;
    }

    // If trying to edit another user
    if (isset($args[2]) && $args[2] !== $user->ID) {
        $target_user = get_userdata($args[2]);

        // Prevent editing other salespersons
        if ($target_user && in_array('ah_ho_salesperson', (array) $target_user->roles)) {
            $allcaps['edit_user'] = false;
            $allcaps['edit_users'] = false;
        }
    }

    return $allcaps;
}

/**
 * Filter HPOS order table queries directly
 *
 * This is an additional layer for when using HPOS custom tables
 */
add_filter('woocommerce_order_data_store_cpt_get_orders_query', 'ah_ho_filter_hpos_order_query', 10, 2);

function ah_ho_filter_hpos_order_query($query, $query_vars) {
    // Only apply in admin
    if (!is_admin()) {
        return $query;
    }

    // Only filter for salespersons
    if (!ah_ho_is_current_user_salesperson()) {
        return $query;
    }

    // This filter is for the CPT data store, meta_query should work
    if (!isset($query['meta_query'])) {
        $query['meta_query'] = array();
    }

    $query['meta_query'][] = array(
        'key'     => '_assigned_salesperson_id',
        'value'   => get_current_user_id(),
        'compare' => '='
    );

    return $query;
}

/**
 * Log unauthorized access attempts for security monitoring
 *
 * @param int $order_id The order ID that was accessed
 * @param int $user_id The user who attempted access
 */
function ah_ho_log_unauthorized_access($order_id, $user_id) {
    $log_entry = sprintf(
        '[%s] Unauthorized access attempt: User %d tried to access Order #%d',
        current_time('mysql'),
        $user_id,
        $order_id
    );

    // Log to WordPress error log
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log($log_entry);
    }

    // Store in transient for admin review (optional - keeps last 50 attempts)
    $recent_attempts = get_transient('ah_ho_unauthorized_attempts') ?: array();
    array_unshift($recent_attempts, array(
        'time'     => current_time('mysql'),
        'user_id'  => $user_id,
        'order_id' => $order_id
    ));
    $recent_attempts = array_slice($recent_attempts, 0, 50);
    set_transient('ah_ho_unauthorized_attempts', $recent_attempts, DAY_IN_SECONDS);
}
