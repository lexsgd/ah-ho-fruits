<?php
/**
 * OPcache Reset - Permanent endpoint for CI/CD deploys.
 * Security: requires secret token via query string.
 * Version: 8
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

// Check role capabilities if requested
if (isset($_GET['check_roles'])) {
    // First check if the file on disk has the new capabilities
    $roles_file = __DIR__ . '/wp-content/plugins/ah-ho-custom/includes/salesperson-roles.php';
    if (file_exists($roles_file)) {
        $content = file_get_contents($roles_file);
        $result['file_check'] = array(
            'has_manage_woocommerce' => (strpos($content, "'manage_woocommerce'") !== false),
            'has_view_woocommerce_reports' => (strpos($content, "'view_woocommerce_reports'") !== false),
            'file_size' => filesize($roles_file),
            'file_mtime' => date('Y-m-d H:i:s', filemtime($roles_file)),
        );
    } else {
        $result['file_check'] = 'FILE NOT FOUND';
    }
    // Bootstrap WordPress to check roles in database
    define('ABSPATH', __DIR__ . '/');
    define('WPINC', 'wp-includes');
    require_once(ABSPATH . 'wp-load.php');

    // Force-add the capabilities if missing
    $storeman = get_role('ah_ho_storeman');
    $salesperson = get_role('ah_ho_salesperson');

    if ($storeman && empty($storeman->capabilities['manage_woocommerce'])) {
        $storeman->add_cap('manage_woocommerce', true);
        $storeman->add_cap('view_woocommerce_reports', true);
        $result['storeman_caps_added'] = true;
    }
    if ($salesperson && empty($salesperson->capabilities['manage_woocommerce'])) {
        $salesperson->add_cap('manage_woocommerce', true);
        $salesperson->add_cap('view_woocommerce_reports', true);
        $result['salesperson_caps_added'] = true;
    }

    // Re-read roles after update
    $storeman = get_role('ah_ho_storeman');
    $salesperson = get_role('ah_ho_salesperson');

    $result['roles'] = array(
        'storeman' => array(
            'exists' => !empty($storeman),
            'manage_woocommerce' => $storeman ? !empty($storeman->capabilities['manage_woocommerce']) : false,
            'view_woocommerce_reports' => $storeman ? !empty($storeman->capabilities['view_woocommerce_reports']) : false,
        ),
        'salesperson' => array(
            'exists' => !empty($salesperson),
            'manage_woocommerce' => $salesperson ? !empty($salesperson->capabilities['manage_woocommerce']) : false,
            'view_woocommerce_reports' => $salesperson ? !empty($salesperson->capabilities['view_woocommerce_reports']) : false,
        ),
    );
}

header('Content-Type: application/json');
echo json_encode($result);

// Persistent — no self-delete. Token-protected for security.
