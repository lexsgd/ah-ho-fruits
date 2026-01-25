<?php
// Test WordPress loading
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Step 1: PHP is working<br>\n";

// Try to load wp-config.php
if (file_exists('./wp-config.php')) {
    echo "Step 2: wp-config.php found<br>\n";
    require_once('./wp-config.php');
    echo "Step 3: wp-config.php loaded successfully<br>\n";
} else {
    die("ERROR: wp-config.php not found<br>\n");
}

// Try to load WordPress
if (file_exists('./wp-settings.php')) {
    echo "Step 4: wp-settings.php found<br>\n";
    require_once('./wp-settings.php');
    echo "Step 5: WordPress loaded successfully!<br>\n";
    echo "Site URL: " . get_option('siteurl') . "<br>\n";
} else {
    die("ERROR: wp-settings.php not found<br>\n");
}
