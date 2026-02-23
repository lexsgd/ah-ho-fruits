<?php
// Temporary OPcache reset - delete after use
if (function_exists('opcache_reset')) {
    $result = opcache_reset();
    header('Content-Type: application/json');
    echo json_encode(['opcache_reset' => $result]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'OPcache not available']);
}
