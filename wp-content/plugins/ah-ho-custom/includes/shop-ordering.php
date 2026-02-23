<?php
/**
 * Shop Product Ordering
 *
 * Pins Omakase Boxes category products to the top of the shop page.
 * Sorts B2C products before B2B (wholesale) products.
 *
 * @package AhHoCustom
 * @since 1.5.1
 * @modified 2026-02-23 - B2C products first, B2B products last
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Pin Omakase Boxes products first and sort B2C before B2B on the shop page.
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

    // Get B2B product IDs (products with wholesale price)
    $b2b_ids = ah_ho_get_b2b_product_ids();

    // Need at least one set of IDs to modify ordering
    if (empty($omakase_ids) && empty($b2b_ids)) {
        return;
    }

    // Store IDs on the query for use in the clauses filter
    $query->set('ah_ho_pin_omakase', true);
    $query->set('ah_ho_omakase_ids', $omakase_ids ?: array());
    $query->set('ah_ho_b2b_ids', $b2b_ids ?: array());

    add_filter('posts_clauses', 'ah_ho_omakase_orderby_clauses', 10, 2);
}

/**
 * Modify SQL clauses to sort omakase products first, B2B products last.
 *
 * ORDER BY:
 *   1. Omakase products first (CASE 0 vs 1)
 *   2. B2B products last (CASE 1 vs 0)
 *   3. Original ordering
 */
function ah_ho_omakase_orderby_clauses($clauses, $query) {
    if (!$query->get('ah_ho_pin_omakase')) {
        return $clauses;
    }

    // Remove this filter so it only runs once
    remove_filter('posts_clauses', 'ah_ho_omakase_orderby_clauses', 10);

    global $wpdb;

    $omakase_ids = $query->get('ah_ho_omakase_ids');
    $b2b_ids     = $query->get('ah_ho_b2b_ids');

    $order_parts = array();

    // Tier 1: Omakase products first
    if (!empty($omakase_ids)) {
        $ids = array_map('absint', $omakase_ids);
        $ids_str = implode(',', $ids);
        $order_parts[] = "CASE WHEN {$wpdb->posts}.ID IN ({$ids_str}) THEN 0 ELSE 1 END ASC";
    }

    // Tier 2: B2B (wholesale) products last
    if (!empty($b2b_ids)) {
        $ids = array_map('absint', $b2b_ids);
        $ids_str = implode(',', $ids);
        $order_parts[] = "CASE WHEN {$wpdb->posts}.ID IN ({$ids_str}) THEN 1 ELSE 0 END ASC";
    }

    // Prepend our custom ordering before the existing orderby
    if (!empty($order_parts)) {
        $clauses['orderby'] = implode(', ', $order_parts) . ', ' . $clauses['orderby'];
    }

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
                'terms'    => 'omakase-fruit-boxes',
            ),
        ),
    );

    $ids = get_posts($args);
    return $ids;
}

/**
 * Get B2B product IDs (products with wholesale pricing).
 * These are products that have a _wholesale_price meta set.
 * Cached for the duration of the request.
 */
function ah_ho_get_b2b_product_ids() {
    static $ids = null;

    if ($ids !== null) {
        return $ids;
    }

    $args = array(
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_query'     => array(
            array(
                'key'     => '_wholesale_price',
                'value'   => '',
                'compare' => '!=',
            ),
        ),
    );

    $ids = get_posts($args);
    return $ids;
}
