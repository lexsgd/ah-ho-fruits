<?php
/**
 * Temporary diagnostic - checks if files exist and their content
 * Access via: /wp-content/plugins/ah-ho-custom/debug-check.php
 * DELETE THIS FILE after debugging
 */
header('Content-Type: application/json');

$plugin_dir = __DIR__ . '/';

// Check if the main plugin file has the require_once line
$main_file = file_get_contents($plugin_dir . 'ah-ho-custom.php');
$has_require = strpos($main_file, 'order-fulfillment.php') !== false;

// Check if order-fulfillment.php exists
$fulfillment_exists = file_exists($plugin_dir . 'includes/order-fulfillment.php');

// Get the version from the main file
preg_match("/Version:\s*(.+)/", $main_file, $version_match);

// Check file sizes
$main_size = filesize($plugin_dir . 'ah-ho-custom.php');
$fulfillment_size = $fulfillment_exists ? filesize($plugin_dir . 'includes/order-fulfillment.php') : 0;

// Check OPcache status
$opcache_enabled = function_exists('opcache_get_status') ? opcache_get_status(false) : 'opcache not available';

echo json_encode(array(
    'main_file_has_require_once' => $has_require,
    'fulfillment_file_exists' => $fulfillment_exists,
    'plugin_version_in_file' => isset($version_match[1]) ? trim($version_match[1]) : 'unknown',
    'main_file_size' => $main_size,
    'fulfillment_file_size' => $fulfillment_size,
    'opcache_enabled' => is_array($opcache_enabled) ? $opcache_enabled['opcache_enabled'] : $opcache_enabled,
    'php_version' => phpversion(),
), JSON_PRETTY_PRINT);
