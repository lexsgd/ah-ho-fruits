<?php
if (!isset($_GET['token']) || $_GET['token'] !== 'ah_ho_deploy_2026') {
    http_response_code(403);
    exit('Forbidden');
}

// Check font files
$plugin_dir = __DIR__ . '/wp-content/plugins/ah-ho-invoicing/';
$assets_dir = $plugin_dir . 'assets/fonts/';
$fonts_dir = $plugin_dir . 'vendor/dompdf/dompdf/lib/fonts/';
$font_base = 'noto_sans_sc_normal_d4251901d7080d141f2a9b2da90ce3a3';

$checks = array(
    'cache_version' => 'checking...',
    'source_ttf' => file_exists($assets_dir . 'NotoSansSC-Regular.ttf') ? 'exists (' . filesize($assets_dir . 'NotoSansSC-Regular.ttf') . 'B)' : 'MISSING',
    'source_ufm' => file_exists($assets_dir . $font_base . '.ufm') ? 'exists (' . filesize($assets_dir . $font_base . '.ufm') . 'B)' : 'MISSING',
    'dest_ttf_hashed' => file_exists($fonts_dir . $font_base . '.ttf') ? 'exists' : 'MISSING (will be copied at runtime)',
    'dest_ufm_hashed' => file_exists($fonts_dir . $font_base . '.ufm') ? 'exists' : 'MISSING (will be copied at runtime)',
    'dest_ttf_original' => file_exists($fonts_dir . 'NotoSansSC-Regular.ttf') ? 'exists (legacy)' : 'not present',
    'dest_ufm_original' => file_exists($fonts_dir . 'NotoSansSC-Regular.ufm') ? 'exists (legacy - WRONG NAME)' : 'not present (expected)',
    'fonts_dir_writable' => is_writable($fonts_dir) ? 'YES' : 'NO',
    'cache_dir' => is_dir(__DIR__ . '/wp-content/pdf-cache/') ? 'exists' : 'MISSING',
);

// Check installed-fonts.json
$json = $fonts_dir . 'installed-fonts.json';
if (file_exists($json)) {
    $reg = json_decode(file_get_contents($json), true);
    if (isset($reg['noto sans sc'])) {
        $checks['registry_normal'] = $reg['noto sans sc']['normal'];
        $checks['registry_bold'] = $reg['noto sans sc']['bold'];
        $checks['registry_correct'] = ($reg['noto sans sc']['normal'] === $font_base && $reg['noto sans sc']['bold'] === $font_base) ? 'YES' : 'NO';
    } else {
        $checks['registry'] = 'noto sans sc NOT in registry';
    }
}

// Check CACHE_VERSION via reflection
require_once $plugin_dir . 'vendor/autoload.php';
require_once $plugin_dir . 'includes/class-pdf-generator.php';
if (defined('AH_HO_PDF_Generator::CACHE_VERSION')) {
    // Use constant directly
}
$checks['cache_version'] = AH_HO_PDF_Generator::CACHE_VERSION ?? 'unknown';
$checks['cjk_font_base'] = defined('AH_HO_PDF_Generator::CJK_FONT_BASE') ? AH_HO_PDF_Generator::CJK_FONT_BASE : 'NOT DEFINED (old code!)';

header('Content-Type: application/json');
echo json_encode($checks, JSON_PRETTY_PRINT);

// Self-delete
@unlink(__FILE__);
