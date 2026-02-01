<?php
/**
 * Delivery Date Helper
 *
 * Auto-detects and handles delivery date meta keys from various plugins.
 *
 * Supported plugins:
 * - Order Delivery Date for WooCommerce (Tyche Softwares)
 * - JCK Woo Delivery Slots
 * - PI WooCommerce Order Date Time Picker
 * - Coderockz WooCommerce Delivery Date
 * - Custom _delivery_date field
 *
 * @package AhHoInvoicing
 * @since 1.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class AH_HO_Delivery_Date_Helper {

    /**
     * Known delivery date meta keys from popular plugins
     * Order matters - first match wins
     */
    private static $known_meta_keys = array(
        // Generic/Custom
        '_delivery_date',
        'delivery_date',

        // Order Delivery Date for WooCommerce (Tyche Softwares)
        '_orddd_delivery_date',
        'orddd_delivery_date',

        // Order Delivery Date Pro
        '_orddd_lite_delivery_date',

        // JCK Woo Delivery Slots
        '_jckwds_date',
        '_jckwds_delivery_date',
        'jckwds_date',

        // PI WooCommerce Order Date Time Picker
        '_pi_delivery_date',
        '_pi_date',
        'pi_delivery_date',

        // Coderockz WooCommerce Delivery Date
        '_coderockz_delivery_date',
        '_coderockz_woo_delivery_date',
        'coderockz_woo_delivery_delivery_date',

        // WooCommerce Delivery Slots
        '_wc_delivery_date',

        // Iconic Delivery Slots
        '_iconic_wds_date',

        // Local Pickup Plus
        '_lpd_order_date',

        // Flexible Shipping
        '_flexible_shipping_delivery_date',

        // Other common variations
        '_date_of_delivery',
        '_expected_delivery_date',
        '_scheduled_delivery_date',
    );

    /**
     * Cache for detected meta key
     */
    private static $detected_meta_key = null;

    /**
     * Get the delivery date meta key being used
     *
     * @param bool $force_redetect Force re-detection even if cached
     * @return string|null The detected meta key or null if none found
     */
    public static function get_meta_key($force_redetect = false) {
        // Check cache first
        if (!$force_redetect && self::$detected_meta_key !== null) {
            return self::$detected_meta_key;
        }

        // Check if manually configured
        $configured_key = get_option('ah_ho_delivery_date_meta_key', '');
        if (!empty($configured_key)) {
            self::$detected_meta_key = $configured_key;
            return $configured_key;
        }

        // Auto-detect from recent orders
        self::$detected_meta_key = self::detect_meta_key();
        return self::$detected_meta_key;
    }

    /**
     * Detect which delivery date meta key is in use
     *
     * @return string|null The detected meta key or null
     */
    private static function detect_meta_key() {
        global $wpdb;

        // Get recent order IDs (last 100 orders)
        $order_ids = wc_get_orders(array(
            'limit'  => 100,
            'return' => 'ids',
            'status' => array('wc-processing', 'wc-on-hold', 'wc-completed', 'wc-out-for-delivery'),
        ));

        if (empty($order_ids)) {
            return '_delivery_date'; // Default fallback
        }

        // Check which meta keys exist
        $placeholders = implode(',', array_fill(0, count($order_ids), '%d'));
        $meta_keys_placeholder = implode(',', array_fill(0, count(self::$known_meta_keys), '%s'));

        // Build query to find existing meta keys
        $query = $wpdb->prepare(
            "SELECT DISTINCT meta_key, COUNT(*) as count
             FROM {$wpdb->postmeta}
             WHERE post_id IN ($placeholders)
             AND meta_key IN ($meta_keys_placeholder)
             AND meta_value != ''
             GROUP BY meta_key
             ORDER BY count DESC",
            array_merge($order_ids, self::$known_meta_keys)
        );

        $results = $wpdb->get_results($query);

        if (!empty($results)) {
            // Return the most commonly used key
            return $results[0]->meta_key;
        }

        // Fallback to default
        return '_delivery_date';
    }

    /**
     * Get delivery date from an order
     *
     * @param int|WC_Order $order Order ID or object
     * @param string $format Date format (default: Y-m-d)
     * @return string|null Formatted date or null
     */
    public static function get_delivery_date($order, $format = 'Y-m-d') {
        if (is_numeric($order)) {
            $order = wc_get_order($order);
        }

        if (!$order) {
            return null;
        }

        $order_id = $order->get_id();

        // Try each known meta key
        foreach (self::$known_meta_keys as $meta_key) {
            $value = get_post_meta($order_id, $meta_key, true);

            if (!empty($value)) {
                return self::normalize_date($value, $format);
            }

            // Also try via order meta (HPOS compatible)
            $value = $order->get_meta($meta_key, true);
            if (!empty($value)) {
                return self::normalize_date($value, $format);
            }
        }

        return null;
    }

    /**
     * Normalize date to consistent format
     *
     * @param string $date_string The date string to normalize
     * @param string $format Output format
     * @return string|null Formatted date or null
     */
    public static function normalize_date($date_string, $format = 'Y-m-d') {
        if (empty($date_string)) {
            return null;
        }

        // Try to parse the date
        $timestamp = strtotime($date_string);

        if ($timestamp === false) {
            // Try common formats manually
            $formats = array(
                'd/m/Y',      // 29/01/2026
                'm/d/Y',      // 01/29/2026
                'd-m-Y',      // 29-01-2026
                'Y/m/d',      // 2026/01/29
                'd M Y',      // 29 Jan 2026
                'M d, Y',     // Jan 29, 2026
                'd F Y',      // 29 January 2026
                'F d, Y',     // January 29, 2026
            );

            foreach ($formats as $parse_format) {
                $date = DateTime::createFromFormat($parse_format, $date_string);
                if ($date !== false) {
                    return $date->format($format);
                }
            }

            return null;
        }

        return date($format, $timestamp);
    }

    /**
     * Query orders by delivery date
     *
     * @param string $delivery_date Date in Y-m-d format
     * @param array $statuses Order statuses to include
     * @return array Order IDs
     */
    public static function get_orders_by_delivery_date($delivery_date, $statuses = array('wc-processing')) {
        global $wpdb;

        $meta_key = self::get_meta_key();

        // Normalize the input date
        $normalized_date = self::normalize_date($delivery_date, 'Y-m-d');
        if (!$normalized_date) {
            return array();
        }

        // Try multiple date formats for matching
        $date_variations = array(
            $normalized_date,                                    // 2026-01-29
            date('d/m/Y', strtotime($normalized_date)),          // 29/01/2026
            date('m/d/Y', strtotime($normalized_date)),          // 01/29/2026
            date('d-m-Y', strtotime($normalized_date)),          // 29-01-2026
            date('Y/m/d', strtotime($normalized_date)),          // 2026/01/29
            date('d M Y', strtotime($normalized_date)),          // 29 Jan 2026
            date('M d, Y', strtotime($normalized_date)),         // Jan 29, 2026
            date('F d, Y', strtotime($normalized_date)),         // January 29, 2026
            date('d F Y', strtotime($normalized_date)),          // 29 January 2026
        );

        // First try standard WC query with detected meta key
        $args = array(
            'status'      => $statuses,
            'limit'       => -1,
            'meta_key'    => $meta_key,
            'meta_value'  => $normalized_date,
            'return'      => 'ids',
        );

        $order_ids = wc_get_orders($args);

        // If no results, try with date variations
        if (empty($order_ids)) {
            foreach ($date_variations as $date_variant) {
                $args['meta_value'] = $date_variant;
                $order_ids = wc_get_orders($args);
                if (!empty($order_ids)) {
                    break;
                }
            }
        }

        // If still no results, try all known meta keys with all date formats
        if (empty($order_ids)) {
            foreach (self::$known_meta_keys as $try_meta_key) {
                foreach ($date_variations as $date_variant) {
                    $args['meta_key'] = $try_meta_key;
                    $args['meta_value'] = $date_variant;
                    $order_ids = wc_get_orders($args);
                    if (!empty($order_ids)) {
                        // Cache the detected key for future use
                        self::$detected_meta_key = $try_meta_key;
                        break 2;
                    }
                }
            }
        }

        return $order_ids;
    }

    /**
     * Get all known meta keys
     *
     * @return array
     */
    public static function get_known_meta_keys() {
        return self::$known_meta_keys;
    }

    /**
     * Check which meta keys have data in the database
     *
     * @return array Meta keys with their order counts
     */
    public static function get_meta_key_stats() {
        global $wpdb;

        $stats = array();

        foreach (self::$known_meta_keys as $meta_key) {
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta}
                 WHERE meta_key = %s AND meta_value != ''",
                $meta_key
            ));

            if ($count > 0) {
                $stats[$meta_key] = (int) $count;
            }
        }

        return $stats;
    }

    /**
     * Debug: Get sample delivery dates from orders
     *
     * @param int $limit Number of samples
     * @return array Sample data
     */
    public static function get_sample_data($limit = 5) {
        global $wpdb;

        $samples = array();

        foreach (self::$known_meta_keys as $meta_key) {
            $results = $wpdb->get_results($wpdb->prepare(
                "SELECT post_id, meta_value FROM {$wpdb->postmeta}
                 WHERE meta_key = %s AND meta_value != ''
                 ORDER BY post_id DESC LIMIT %d",
                $meta_key,
                $limit
            ));

            if (!empty($results)) {
                $samples[$meta_key] = $results;
            }
        }

        return $samples;
    }

    /**
     * Check if any delivery date meta fields exist
     *
     * @return bool True if delivery date fields are found
     */
    public static function has_delivery_date_fields() {
        $stats = self::get_meta_key_stats();
        return !empty($stats);
    }

    /**
     * Query orders by ORDER DATE (date_created) - fallback when no delivery date plugin
     *
     * @param string $order_date Date in Y-m-d format
     * @param array $statuses Order statuses to include
     * @return array Order IDs
     */
    public static function get_orders_by_order_date($order_date, $statuses = array('wc-processing')) {
        // Normalize the input date
        $normalized_date = self::normalize_date($order_date, 'Y-m-d');
        if (!$normalized_date) {
            return array();
        }

        // Calculate date range (full day)
        $date_start = $normalized_date . ' 00:00:00';
        $date_end = $normalized_date . ' 23:59:59';

        // Query orders by date_created
        $args = array(
            'status'       => $statuses,
            'limit'        => -1,
            'date_created' => $date_start . '...' . $date_end,
            'return'       => 'ids',
        );

        return wc_get_orders($args);
    }

    /**
     * Smart query: tries delivery date first, falls back to order date
     *
     * @param string $date Date in Y-m-d format
     * @param array $statuses Order statuses to include
     * @param bool $use_order_date_fallback Whether to fallback to order date
     * @return array ['order_ids' => array, 'date_type' => 'delivery'|'order']
     */
    public static function get_orders_by_date_smart($date, $statuses = array('wc-processing'), $use_order_date_fallback = true) {
        // First check if we have delivery date fields
        $has_delivery_fields = self::has_delivery_date_fields();

        if ($has_delivery_fields) {
            // Try delivery date first
            $order_ids = self::get_orders_by_delivery_date($date, $statuses);
            if (!empty($order_ids)) {
                return array(
                    'order_ids' => $order_ids,
                    'date_type' => 'delivery',
                );
            }
        }

        // Fallback to order date
        if ($use_order_date_fallback) {
            $order_ids = self::get_orders_by_order_date($date, $statuses);
            return array(
                'order_ids' => $order_ids,
                'date_type' => 'order',
            );
        }

        return array(
            'order_ids' => array(),
            'date_type' => null,
        );
    }
}
