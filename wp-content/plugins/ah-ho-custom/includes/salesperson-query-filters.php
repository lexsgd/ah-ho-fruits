<?php
/**
 * Salesperson Query Filtering - Multi-Layer Security
 *
 * Ensures salespersons can only access their own assigned orders
 * Implements 4 layers of protection to prevent cross-salesperson data access
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * LAYER 1: Admin Order List (pre_get_posts)
 * Filter order queries to show only assigned orders for salespersons
 */
add_filter('pre_get_posts', 'ah_ho_filter_salesperson_orders');

function ah_ho_filter_salesperson_orders($query) {
    // Only apply in admin area
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }

    // Only apply to shop_order queries
    global $typenow;
    if ($typenow !== 'shop_order' && get_query_var('post_type') !== 'shop_order') {
        return;
    }

    $user = wp_get_current_user();

    // Only filter for salespersons
    if (!in_array('ah_ho_salesperson', $user->roles)) {
        return;
    }

    // Filter orders to show only those assigned to this salesperson
    $meta_query = $query->get('meta_query') ?: array();
    $meta_query[] = array(
        'key'     => '_assigned_salesperson_id',
        'value'   => get_current_user_id(),
        'compare' => '='
    );

    $query->set('meta_query', $meta_query);
}

/**
 * LAYER 2: SQL Fallback (posts_where)
 * Direct SQL filtering as backup layer
 */
add_filter('posts_where', 'ah_ho_filter_orders_sql', 10, 2);

function ah_ho_filter_orders_sql($where, $query) {
    global $wpdb, $typenow;

    // Only apply in admin area
    if (!is_admin()) {
        return $where;
    }

    $user = wp_get_current_user();

    // Only filter for salespersons
    if (!in_array('ah_ho_salesperson', $user->roles)) {
        return $where;
    }

    // Only apply to shop_order queries
    if ($typenow !== 'shop_order' && get_query_var('post_type') !== 'shop_order') {
        return $where;
    }

    // Add SQL filter
    $where .= $wpdb->prepare(
        " AND {$wpdb->posts}.ID IN (
            SELECT post_id FROM {$wpdb->postmeta}
            WHERE meta_key = '_assigned_salesperson_id'
            AND meta_value = %d
        )",
        get_current_user_id()
    );

    return $where;
}

/**
 * LAYER 3: Direct URL Access Prevention (load-post.php)
 * Prevent accessing order edit page via direct URL
 */
add_action('load-post.php', 'ah_ho_prevent_unauthorized_order_access');

function ah_ho_prevent_unauthorized_order_access() {
    global $post;

    // Must have a post
    if (!$post) {
        return;
    }

    // Only check shop orders
    if ($post->post_type !== 'shop_order') {
        return;
    }

    $user = wp_get_current_user();

    // Only check for salespersons
    if (!in_array('ah_ho_salesperson', $user->roles)) {
        return;
    }

    // Get assigned salesperson
    $assigned_salesperson = get_post_meta($post->ID, '_assigned_salesperson_id', true);

    // If order is not assigned to current user, deny access
    if ($assigned_salesperson != get_current_user_id()) {
        wp_die(
            __('You do not have permission to access this order.', 'ah-ho-custom'),
            __('Access Denied', 'ah-ho-custom'),
            array('response' => 403)
        );
    }
}

/**
 * LAYER 4: REST API Protection
 * Prevent unauthorized API access to orders
 */
add_filter('woocommerce_rest_check_permissions', 'ah_ho_rest_order_permissions', 10, 4);

function ah_ho_rest_order_permissions($permission, $context, $object_id, $post_type) {
    // Only check shop_order permissions
    if ($post_type !== 'shop_order') {
        return $permission;
    }

    $user = wp_get_current_user();

    // Only filter for salespersons
    if (!in_array('ah_ho_salesperson', $user->roles)) {
        return $permission;
    }

    // If accessing a specific order
    if ($object_id) {
        $assigned_salesperson = get_post_meta($object_id, '_assigned_salesperson_id', true);

        // Deny if not assigned to this salesperson
        if ($assigned_salesperson != $user->ID) {
            return false;
        }
    }

    return $permission;
}

/**
 * Filter WooCommerce order count for salespersons
 * Ensures order counts in admin menu reflect only assigned orders
 */
add_filter('woocommerce_menu_order_count', 'ah_ho_filter_order_count', 10, 2);

function ah_ho_filter_order_count($count, $status) {
    $user = wp_get_current_user();

    // Only filter for salespersons
    if (!in_array('ah_ho_salesperson', $user->roles)) {
        return $count;
    }

    // Count only assigned orders
    $args = array(
        'post_type'      => 'shop_order',
        'post_status'    => $status,
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_query'     => array(
            array(
                'key'     => '_assigned_salesperson_id',
                'value'   => $user->ID,
                'compare' => '='
            )
        )
    );

    $query = new WP_Query($args);
    return $query->found_posts;
}

/**
 * Hide unassigned orders from salesperson dashboard widget
 */
add_action('wp_dashboard_setup', 'ah_ho_filter_dashboard_widget');

function ah_ho_filter_dashboard_widget() {
    $user = wp_get_current_user();

    // Only for salespersons
    if (!in_array('ah_ho_salesperson', $user->roles)) {
        return;
    }

    // Remove default WooCommerce status widget (shows all orders)
    remove_meta_box('woocommerce_dashboard_status', 'dashboard', 'normal');
}

/**
 * Additional security: Prevent salespersons from viewing other salespersons' profiles
 */
add_filter('user_has_cap', 'ah_ho_restrict_user_profile_access', 10, 4);

function ah_ho_restrict_user_profile_access($allcaps, $caps, $args, $user) {
    // Check if user is a salesperson
    if (!in_array('ah_ho_salesperson', $user->roles)) {
        return $allcaps;
    }

    // If trying to edit another user
    if (isset($args[2]) && $args[2] !== $user->ID) {
        $target_user = get_userdata($args[2]);

        // Prevent editing other salespersons
        if ($target_user && in_array('ah_ho_salesperson', $target_user->roles)) {
            $allcaps['edit_user'] = false;
            $allcaps['edit_users'] = false;
        }
    }

    return $allcaps;
}

/**
 * Log unauthorized access attempts for security monitoring
 */
function ah_ho_log_unauthorized_access($order_id, $user_id) {
    $log_entry = sprintf(
        '[%s] Unauthorized access attempt: User %d tried to access Order #%d',
        current_time('mysql'),
        $user_id,
        $order_id
    );

    // Log to WordPress error log
    error_log($log_entry);

    // Optionally: Add to custom log table or send alert email
    // This can be expanded based on security requirements
}
