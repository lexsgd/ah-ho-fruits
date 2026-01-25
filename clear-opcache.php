<?php
// Clear PHP OPcache - temporary file to reset cache
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "OPcache cleared successfully\n";
} else {
    echo "OPcache not enabled\n";
}

// Also clear WP cache
if (function_exists('wp_cache_flush')) {
    wp_cache_flush();
    echo "WordPress cache cleared\n";
}

echo "Cache clearing complete. Delete this file after use.\n";
