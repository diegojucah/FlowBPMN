<?php
/**
 * Clear PHP OPCache
 */

if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "OPCache cleared successfully!\n";
} else {
    echo "OPCache is not enabled.\n";
}

if (function_exists('apcu_clear_cache')) {
    apcu_clear_cache();
    echo "APCu cache cleared successfully!\n";
}

echo "Done!\n";
