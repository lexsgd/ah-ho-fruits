<?php
// Temporary OPcache reset - DELETE IMMEDIATELY AFTER USE
header('Content-Type: application/json');
$result = array();
if (function_exists('opcache_reset')) {
    $result['opcache_reset'] = opcache_reset();
    $result['message'] = 'OPcache cleared';
} else {
    $result['message'] = 'OPcache not available';
}
$result['timestamp'] = date('Y-m-d H:i:s');
echo json_encode($result, JSON_PRETTY_PRINT);
