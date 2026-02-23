<?php
/**
 * OPcache Reset - Permanent endpoint for CI/CD deploys.
 * Security: requires secret token via query string.
 * Version: 5
 */
if (!isset($_GET['token']) || $_GET['token'] !== 'ah_ho_deploy_2026') {
    http_response_code(403);
    exit('Forbidden');
}

$result = array('opcache_available' => function_exists('opcache_reset'));
if (function_exists('opcache_reset')) {
    $result['reset'] = opcache_reset();
}

// Also invalidate specific files
$files_to_invalidate = array(
    __DIR__ . '/wp-content/plugins/ah-ho-invoicing/includes/class-metabox.php',
    __DIR__ . '/wp-content/plugins/ah-ho-invoicing/includes/class-pdf-generator.php',
);
if (function_exists('opcache_invalidate')) {
    foreach ($files_to_invalidate as $f) {
        if (file_exists($f)) {
            opcache_invalidate($f, true);
        }
    }
    $result['files_invalidated'] = count($files_to_invalidate);
}

header('Content-Type: application/json');
echo json_encode($result);

// Persistent — no self-delete. Token-protected for security.
