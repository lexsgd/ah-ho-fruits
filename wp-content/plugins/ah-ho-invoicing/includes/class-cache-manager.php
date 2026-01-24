<?php
/**
 * Cache Manager Class
 *
 * Manages PDF caching and cleanup
 */

if (!defined('ABSPATH')) {
    exit;
}

class AH_HO_Cache_Manager {

    /**
     * Initialize cache manager
     */
    public static function init() {
        // Clear cache when order is updated
        add_action('woocommerce_update_order', array(__CLASS__, 'clear_order_cache'));
        add_action('woocommerce_order_status_changed', array(__CLASS__, 'clear_order_cache'));
    }

    /**
     * Clear cached PDFs for an order when it's updated
     *
     * @param int $order_id Order ID
     */
    public static function clear_order_cache($order_id) {
        $cache_dir = AH_HO_INVOICING_CACHE_DIR;

        // Delete all cached PDFs for this order
        $patterns = array(
            "invoice_{$order_id}_*.pdf",
            "packing-slip_{$order_id}_*.pdf",
            "delivery-order_{$order_id}_*.pdf",
        );

        foreach ($patterns as $pattern) {
            $files = glob($cache_dir . $pattern);
            foreach ($files as $file) {
                unlink($file);
            }
        }
    }

    /**
     * Get cache directory size
     *
     * @return int Size in bytes
     */
    public static function get_cache_size() {
        $cache_dir = AH_HO_INVOICING_CACHE_DIR;
        $files = glob($cache_dir . '*.pdf');
        $total_size = 0;

        foreach ($files as $file) {
            $total_size += filesize($file);
        }

        return $total_size;
    }

    /**
     * Get cache file count
     *
     * @return int Number of cached PDFs
     */
    public static function get_cache_count() {
        $cache_dir = AH_HO_INVOICING_CACHE_DIR;
        $files = glob($cache_dir . '*.pdf');
        return count($files);
    }

    /**
     * Clear all cache
     */
    public static function clear_all_cache() {
        $cache_dir = AH_HO_INVOICING_CACHE_DIR;
        $files = glob($cache_dir . '*.pdf');

        $deleted_count = 0;
        foreach ($files as $file) {
            if (unlink($file)) {
                $deleted_count++;
            }
        }

        return $deleted_count;
    }

    /**
     * Format bytes to human-readable size
     *
     * @param int $bytes Size in bytes
     * @return string Formatted size (e.g., "2.5 MB")
     */
    public static function format_bytes($bytes) {
        $units = array('B', 'KB', 'MB', 'GB');
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
