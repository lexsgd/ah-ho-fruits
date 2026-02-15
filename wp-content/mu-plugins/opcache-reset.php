<?php
/**
 * TEMPORARY: Load order-fulfillment.php directly via mu-plugin.
 * This bypasses OPcache on ah-ho-custom.php which has stale cached version.
 * Also registers a debug REST endpoint to verify loading.
 * DELETE THIS FILE once OPcache is resolved.
 */

// Load order-fulfillment.php after WooCommerce is ready
add_action('plugins_loaded', function() {
    $file = WP_CONTENT_DIR . '/plugins/ah-ho-custom/includes/order-fulfillment.php';
    if (file_exists($file) && !function_exists('ah_ho_get_delivered_qty_per_item')) {
        require_once $file;
    }
}, 20); // Priority 20 to run after the main plugin (priority 10)

// Debug REST endpoint
add_action('rest_api_init', function() {
    register_rest_route('ah-ho/v1', '/debug', array(
        'methods' => 'GET',
        'callback' => function() {
            $plugin_file = WP_CONTENT_DIR . '/plugins/ah-ho-custom/ah-ho-custom.php';
            $fulfillment_file = WP_CONTENT_DIR . '/plugins/ah-ho-custom/includes/order-fulfillment.php';

            // Read the actual file to check if require_once line exists
            $main_content = file_get_contents($plugin_file);
            $has_require = strpos($main_content, 'order-fulfillment.php') !== false;

            // Get version from file content
            preg_match("/AH_HO_CUSTOM_VERSION',\s*'([^']+)'/", $main_content, $ver);

            return array(
                'mu_plugin' => 'loaded',
                'fulfillment_file_exists' => file_exists($fulfillment_file),
                'fulfillment_fn_loaded' => function_exists('ah_ho_get_delivered_qty_per_item'),
                'meta_box_fn_loaded' => function_exists('ah_ho_add_fulfillment_meta_box'),
                'main_file_has_require' => $has_require,
                'file_version' => isset($ver[1]) ? $ver[1] : 'unknown',
                'runtime_version' => defined('AH_HO_CUSTOM_VERSION') ? AH_HO_CUSTOM_VERSION : 'not_defined',
                'opcache_enabled' => function_exists('opcache_get_status') ? (opcache_get_status(false)['opcache_enabled'] ?? 'error') : 'not_available',
                'php_version' => phpversion(),
            );
        },
        'permission_callback' => '__return_true',
    ));
});
