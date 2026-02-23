<?php
if (!isset($_GET['token']) || $_GET['token'] !== 'ah_ho_deploy_2026') {
    http_response_code(403);
    exit('Forbidden');
}

$plugin_dir = __DIR__ . '/wp-content/plugins/ah-ho-invoicing/';
$assets_dir = $plugin_dir . 'assets/fonts/';
$fonts_dir = $plugin_dir . 'vendor/dompdf/dompdf/lib/fonts/';
$font_base = 'noto_sans_sc_medium_6414f22937fa743cf56f4eb5b0fa1909';

$checks = array();
$checks['medium_ttf'] = file_exists($assets_dir . 'NotoSansSC-Medium.ttf') ? 'OK (' . round(filesize($assets_dir . 'NotoSansSC-Medium.ttf')/1024/1024, 1) . 'MB)' : 'MISSING';
$checks['medium_ufm'] = file_exists($assets_dir . $font_base . '.ufm') ? 'OK (' . round(filesize($assets_dir . $font_base . '.ufm')/1024) . 'KB)' : 'MISSING';
$checks['dest_ttf'] = file_exists($fonts_dir . $font_base . '.ttf') ? 'OK' : 'pending (will copy at runtime)';
$checks['dest_ufm'] = file_exists($fonts_dir . $font_base . '.ufm') ? 'OK' : 'pending (will copy at runtime)';
$checks['fonts_dir_writable'] = is_writable($fonts_dir) ? 'yes' : 'NO';

// Check registry
$json_path = $fonts_dir . 'installed-fonts.json';
if (file_exists($json_path)) {
    $reg = json_decode(file_get_contents($json_path), true);
    if (isset($reg['noto sans sc'])) {
        $checks['registry'] = ($reg['noto sans sc']['normal'] === $font_base) ? 'CORRECT' : 'WRONG: ' . $reg['noto sans sc']['normal'];
    } else {
        $checks['registry'] = 'MISSING noto sans sc entry';
    }
}

// Check code version
$gen_path = $plugin_dir . 'includes/class-pdf-generator.php';
if (file_exists($gen_path)) {
    $content = file_get_contents($gen_path);
    preg_match("/CACHE_VERSION\s*=\s*'(\d+)'/", $content, $m);
    $checks['cache_version'] = isset($m[1]) ? $m[1] : 'unknown';
    $checks['uses_medium'] = (strpos($content, 'NotoSansSC-Medium.ttf') !== false) ? 'YES' : 'NO (old code)';
    $checks['uses_octet_stream'] = (strpos($content, 'application/octet-stream') !== false) ? 'YES' : 'NO';
}

// Check metabox
$mb_path = $plugin_dir . 'includes/class-metabox.php';
if (file_exists($mb_path)) {
    $mb_content = file_get_contents($mb_path);
    $checks['metabox_has_fetch'] = (strpos($mb_content, 'fetch(url') !== false) ? 'YES (old)' : 'NO (good - using <a download>)';
    $checks['metabox_has_download_attr'] = (strpos($mb_content, 'download="') !== false) ? 'YES' : 'NO';
}

header('Content-Type: application/json');
echo json_encode($checks, JSON_PRETTY_PRINT);
@unlink(__FILE__);
