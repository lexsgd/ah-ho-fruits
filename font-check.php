<?php
if (!isset($_GET['token']) || $_GET['token'] !== 'ah_ho_deploy_2026') {
    http_response_code(403);
    exit('Forbidden');
}

error_reporting(E_ALL);
ini_set('display_errors', 0);

$plugin_dir = __DIR__ . '/wp-content/plugins/ah-ho-invoicing/';
$assets_dir = $plugin_dir . 'assets/fonts/';
$fonts_dir = $plugin_dir . 'vendor/dompdf/dompdf/lib/fonts/';
$font_base = 'noto_sans_sc_normal_d4251901d7080d141f2a9b2da90ce3a3';
$cache_dir = __DIR__ . '/wp-content/pdf-cache/';

$checks = array();

// Source files in assets/fonts/
$checks['source_ttf'] = file_exists($assets_dir . 'NotoSansSC-Regular.ttf') ? 'OK (' . round(filesize($assets_dir . 'NotoSansSC-Regular.ttf')/1024/1024, 1) . 'MB)' : 'MISSING';
$checks['source_ufm'] = file_exists($assets_dir . $font_base . '.ufm') ? 'OK (' . round(filesize($assets_dir . $font_base . '.ufm')/1024) . 'KB)' : 'MISSING';

// Destination files in Dompdf fonts dir
$checks['dest_ttf_hashed'] = file_exists($fonts_dir . $font_base . '.ttf') ? 'OK' : 'MISSING';
$checks['dest_ufm_hashed'] = file_exists($fonts_dir . $font_base . '.ufm') ? 'OK' : 'MISSING';

// Legacy files (wrong names from old code)
$checks['legacy_ttf'] = file_exists($fonts_dir . 'NotoSansSC-Regular.ttf') ? 'present' : 'absent';
$checks['legacy_ufm'] = file_exists($fonts_dir . 'NotoSansSC-Regular.ufm') ? 'PRESENT (wrong name!)' : 'absent (correct)';

// Directory permissions
$checks['fonts_dir_writable'] = is_writable($fonts_dir) ? 'yes' : 'NO';
$checks['cache_dir_exists'] = is_dir($cache_dir) ? 'yes' : 'no';

// Check installed-fonts.json registry
$json_path = $fonts_dir . 'installed-fonts.json';
if (file_exists($json_path)) {
    $reg = json_decode(file_get_contents($json_path), true);
    if (isset($reg['noto sans sc'])) {
        $checks['registry_normal'] = $reg['noto sans sc']['normal'];
        $checks['registry_bold'] = $reg['noto sans sc']['bold'];
        $all_correct = ($reg['noto sans sc']['normal'] === $font_base
            && $reg['noto sans sc']['bold'] === $font_base
            && $reg['noto sans sc']['italic'] === $font_base
            && $reg['noto sans sc']['bold_italic'] === $font_base);
        $checks['registry_all_correct'] = $all_correct ? 'YES' : 'NO';
    } else {
        $checks['registry'] = 'noto sans sc NOT in registry';
    }
} else {
    $checks['registry'] = 'installed-fonts.json MISSING';
}

// Check class-pdf-generator.php for CACHE_VERSION
$gen_path = $plugin_dir . 'includes/class-pdf-generator.php';
if (file_exists($gen_path)) {
    $gen_content = file_get_contents($gen_path);
    if (preg_match("/CACHE_VERSION\s*=\s*'(\d+)'/", $gen_content, $m)) {
        $checks['cache_version'] = $m[1];
    }
    if (preg_match("/CJK_FONT_BASE\s*=\s*'([^']+)'/", $gen_content, $m)) {
        $checks['cjk_font_base'] = $m[1];
    } else {
        $checks['cjk_font_base'] = 'NOT FOUND (old code!)';
    }
} else {
    $checks['generator_file'] = 'MISSING';
}

header('Content-Type: application/json');
echo json_encode($checks, JSON_PRETTY_PRINT);

// Self-delete
@unlink(__FILE__);
