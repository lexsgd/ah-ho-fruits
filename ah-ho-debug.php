<?php
/**
 * Temporary diagnostic - DELETE AFTER USE
 */
header('Content-Type: application/json');

$plugin_dir = __DIR__ . '/wp-content/plugins/ah-ho-custom/';

$main_file = file_get_contents($plugin_dir . 'ah-ho-custom.php');
$has_require = strpos($main_file, 'order-fulfillment.php') !== false;
$fulfillment_exists = file_exists($plugin_dir . 'includes/order-fulfillment.php');

preg_match("/Version:\s*(.+)/", $main_file, $ver);
preg_match("/AH_HO_CUSTOM_VERSION',\s*'([^']+)'/", $main_file, $const_ver);

echo json_encode(array(
    'main_has_require_once' => $has_require,
    'fulfillment_exists' => $fulfillment_exists,
    'header_version' => isset($ver[1]) ? trim($ver[1]) : 'unknown',
    'const_version' => isset($const_ver[1]) ? trim($const_ver[1]) : 'unknown',
    'main_size' => filesize($plugin_dir . 'ah-ho-custom.php'),
    'fulfillment_size' => $fulfillment_exists ? filesize($plugin_dir . 'includes/order-fulfillment.php') : 0,
    'opcache' => function_exists('opcache_get_status'),
), JSON_PRETTY_PRINT);
