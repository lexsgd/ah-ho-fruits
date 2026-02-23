<?php
// Temporary - delete after use
header('Content-Type: application/json');

$result = [];

// 1. Reset OPcache
if (function_exists('opcache_reset')) {
    $result['opcache_reset'] = opcache_reset();
} else {
    $result['opcache'] = 'not available';
}

// 2. Check current code version
$gen_file = __DIR__ . '/wp-content/plugins/ah-ho-invoicing/includes/class-pdf-generator.php';
if (file_exists($gen_file)) {
    $content = file_get_contents($gen_file);

    // Check CACHE_VERSION
    if (preg_match("/CACHE_VERSION\s*=\s*'(\d+)'/", $content, $m)) {
        $result['cache_version'] = $m[1];
    }

    // Check subsetting setting
    if (strpos($content, 'isFontSubsettingEnabled\', true') !== false ||
        strpos($content, "isFontSubsettingEnabled', true") !== false) {
        $result['subsetting'] = 'ENABLED (bad)';
    } else {
        $result['subsetting'] = 'disabled (good)';
    }
}

// 3. Check font files exist
$font_dir = __DIR__ . '/wp-content/plugins/ah-ho-invoicing/vendor/dompdf/dompdf/lib/fonts/';
$result['font_ttf'] = file_exists($font_dir . 'NotoSansSC-Regular.ttf') ? 'exists' : 'MISSING';
$result['font_ufm'] = file_exists($font_dir . 'NotoSansSC-Regular.ufm') ? 'exists' : 'MISSING';
$result['font_json'] = file_exists($font_dir . 'installed-fonts.json') ? 'exists' : 'MISSING';

// 4. Check cache dir
$cache_dir = __DIR__ . '/wp-content/plugins/ah-ho-invoicing/cache/';
$result['cache_dir_exists'] = is_dir($cache_dir);
$result['cache_dir_writable'] = is_writable($cache_dir);

echo json_encode($result, JSON_PRETTY_PRINT);
