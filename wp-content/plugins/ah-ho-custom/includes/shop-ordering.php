<?php
/**
 * Shop Product Ordering
 *
 * Pins Omakase Boxes category products to the top of the shop page.
 *
 * @package AhHoCustom
 * @since 1.5.1
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Pin Omakase Boxes products first on the shop page.
 *
 * Uses a two-pass approach: first query omakase products, then exclude them
 * from the main query and prepend them via the loop_start hook.
 */
add_action('woocommerce_product_query', 'ah_ho_pin_omakase_first', 20);

function ah_ho_pin_omakase_first($query) {
    // Only on the main shop page (not category pages, search, or admin)
    if (is_admin() || !is_shop()) {
        return;
    }

    // Only on the first page or when no specific ordering is chosen by user
    // Skip if user explicitly chose a sort option (price, rating, etc.)
    $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : '';
    if ($orderby && $orderby !== 'menu_order') {
        return;
    }

    // Get omakase product IDs
    $omakase_ids = ah_ho_get_omakase_product_ids();
    if (empty($omakase_ids)) {
        return;
    }

    // Use post__in with orderby to pin omakase products first
    // We modify the orderby to use FIELD() for pinned products
    $query->set('ah_ho_pin_omakase', true);
    $query->set('ah_ho_omakase_ids', $omakase_ids);

    add_filter('posts_clauses', 'ah_ho_omakase_orderby_clauses', 10, 2);
}

/**
 * Modify SQL clauses to sort omakase products first.
 */
function ah_ho_omakase_orderby_clauses($clauses, $query) {
    if (!$query->get('ah_ho_pin_omakase')) {
        return $clauses;
    }

    // Remove this filter so it only runs once
    remove_filter('posts_clauses', 'ah_ho_omakase_orderby_clauses', 10);

    global $wpdb;

    $omakase_ids = $query->get('ah_ho_omakase_ids');
    if (empty($omakase_ids)) {
        return $clauses;
    }

    // Sanitize IDs
    $ids = array_map('absint', $omakase_ids);
    $ids_str = implode(',', $ids);

    // Prepend: omakase products first (FIELD gives them order 1,2,3...),
    // non-omakase products get 0 from NOT IN, so we use a CASE statement
    $clauses['orderby'] = "CASE WHEN {$wpdb->posts}.ID IN ({$ids_str}) THEN 0 ELSE 1 END ASC, " . $clauses['orderby'];

    return $clauses;
}

/**
 * Get product IDs in the omakase-boxes category.
 * Cached for the duration of the request.
 */
function ah_ho_get_omakase_product_ids() {
    static $ids = null;

    if ($ids !== null) {
        return $ids;
    }

    $args = array(
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'tax_query'      => array(
            array(
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => 'omakase-boxes',
            ),
        ),
    );

    $ids = get_posts($args);
    return $ids;
}
