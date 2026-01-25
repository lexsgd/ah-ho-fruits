<?php
header('Content-Type: text/plain');

echo "Checking wp-config.php...\n\n";

if (file_exists('wp-config.php')) {
    echo "wp-config.php: EXISTS\n";
    echo "File size: " . filesize('wp-config.php') . " bytes\n\n";

    // Check if it has the new WP_HOME and WP_SITEURL constants
    $content = file_get_contents('wp-config.php');
    if (strpos($content, 'WP_HOME') !== false) {
        echo "✓ WP_HOME is defined\n";
    } else {
        echo "✗ WP_HOME is NOT defined\n";
    }

    if (strpos($content, 'WP_SITEURL') !== false) {
        echo "✓ WP_SITEURL is defined\n";
    } else {
        echo "✗ WP_SITEURL is NOT defined\n";
    }
} else {
    echo "wp-config.php: MISSING\n";
}

// Also check if WordPress can initialize
echo "\nTrying to load WordPress...\n";
if (file_exists('wp-load.php')) {
    try {
        require_once('wp-load.php');
        echo "✓ WordPress loaded successfully!\n";
        echo "Site URL: " . get_option('siteurl') . "\n";
        echo "Home URL: " . get_option('home') . "\n";
    } catch (Exception $e) {
        echo "✗ WordPress failed to load: " . $e->getMessage() . "\n";
    }
} else {
    echo "✗ wp-load.php not found\n";
}
