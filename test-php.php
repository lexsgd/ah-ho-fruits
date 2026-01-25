<?php
// Quick PHP version test
header('Content-Type: text/plain');
echo "PHP Version: " . phpversion() . "\n";
echo "Test Time: " . date('Y-m-d H:i:s') . "\n";
echo ".htaccess Working: " . (version_compare(phpversion(), '7.4.0', '>=') ? 'YES' : 'NO') . "\n";
