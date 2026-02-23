<?php
/**
 * OPcache Reset - Deployed temporarily by CI/CD, self-deletes after use.
 * Security: requires secret token via query string.
 */
if (!isset($_GET['token']) || $_GET['token'] !== 'ah_ho_deploy_2026') {
    http_response_code(403);
    exit('Forbidden');
}

$result = array('opcache_available' => function_exists('opcache_reset'));
if (function_exists('opcache_reset')) {
    $result['reset'] = opcache_reset();
}
header('Content-Type: application/json');
echo json_encode($result);

// Self-delete after successful reset
@unlink(__FILE__);
