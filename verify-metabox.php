<?php
if (!isset($_GET['token']) || $_GET['token'] !== 'ah_ho_deploy_2026') {
    http_response_code(403);
    exit('Forbidden');
}

$mb_path = __DIR__ . '/wp-content/plugins/ah-ho-invoicing/includes/class-metabox.php';
$checks = array();

if (file_exists($mb_path)) {
    $content = file_get_contents($mb_path);
    $checks['has_xhr'] = (strpos($content, 'XMLHttpRequest') !== false) ? 'YES (new XHR+Blob)' : 'NO (old code)';
    $checks['has_blob'] = (strpos($content, 'URL.createObjectURL') !== false) ? 'YES' : 'NO';
    $checks['has_download_attr'] = (strpos($content, 'download="') !== false) ? 'YES (old <a download>)' : 'NO (good)';
    $checks['has_button'] = (strpos($content, 'ahHoDownloadPdf(') !== false) ? 'YES' : 'NO';
} else {
    $checks['error'] = 'metabox file not found';
}

header('Content-Type: application/json');
echo json_encode($checks, JSON_PRETTY_PRINT);
@unlink(__FILE__);
