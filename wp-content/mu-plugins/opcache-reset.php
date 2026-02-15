<?php
/**
 * TEMPORARY: Reset OPcache once, then self-delete.
 * This forces PHP to recompile all cached files.
 * DELETE THIS FILE if it doesn't self-delete.
 */
if (function_exists('opcache_reset')) {
    opcache_reset();
}

// Also invalidate specific files
$files_to_invalidate = array(
    dirname(__DIR__) . '/plugins/ah-ho-custom/ah-ho-custom.php',
    dirname(__DIR__) . '/plugins/ah-ho-custom/includes/order-fulfillment.php',
);
foreach ($files_to_invalidate as $file) {
    if (file_exists($file) && function_exists('opcache_invalidate')) {
        opcache_invalidate($file, true);
    }
}

// Self-delete after running once
@unlink(__FILE__);
