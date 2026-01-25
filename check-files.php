<?php
header('Content-Type: text/plain');
echo "PHP Version: " . phpversion() . "\n\n";
echo "Checking WordPress files...\n\n";

$files_to_check = [
    'wp-config.php',
    'wp-load.php',
    'index.php',
    'wp-settings.php',
    'wp-blog-header.php',
    '.htaccess'
];

foreach ($files_to_check as $file) {
    $exists = file_exists($file);
    $readable = $exists && is_readable($file);
    $size = $exists ? filesize($file) : 0;

    echo "$file: " . ($exists ? 'EXISTS' : 'MISSING');
    if ($exists) {
        echo " (" . $size . " bytes)";
    }
    if ($exists && !$readable) {
        echo " - NOT READABLE";
    }
    echo "\n";
}

echo "\nCurrent directory: " . getcwd() . "\n";
echo "Document root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
