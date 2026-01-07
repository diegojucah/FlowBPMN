<?php
// HARDCODED ROOT for Docker environment consistency
define('GLPI_ROOT', '/var/www/glpi');

if (file_exists(GLPI_ROOT . '/inc/includes.php')) {
    includeAttribute(GLPI_ROOT . '/inc/includes.php');
} else {
    die("FATAL: includes.php not found at " . GLPI_ROOT . "/inc/includes.php\n");
}

function includeAttribute($file) {
    include($file);
}

echo "GLPI_ROOT: " . GLPI_ROOT . "\n";
echo "Class Profile Exists? " . (class_exists('Profile') ? 'YES' : 'NO') . "\n";
echo "Class PluginFlowbpmnProfile Exists? " . (class_exists('PluginFlowbpmnProfile') ? 'YES' : 'NO') . "\n";

if (!class_exists('Profile')) {
    echo "Attempting manual load of Profile...\n";
    // Usually handled by autoloader. 
    // If autoloader fails, we are in trouble.
    // Check vendor autoload
    if (file_exists(GLPI_ROOT . '/vendor/autoload.php')) {
        echo "Vendor autoload found.\n";
    } else {
        echo "Vendor autoload MISSING.\n";
    }
}

if (class_exists('Profile') && class_exists('PluginFlowbpmnProfile')) {
    $profile = new Profile();
    // Mocking ID 1 (Super-Admin usually)
    $profile->fields['id'] = 1; 
    
    // Dummy inputs for testing hook
    $_POST['_glpi_plugin_flowbpmn_marker'] = 1;
    $_POST['can_view_ticket'] = 1;
    $_POST['can_edit_ticket'] = 0;

    echo "Calling hook updateProfileRight...\n";
    PluginFlowbpmnProfile::updateProfileRight($profile);
    echo "Hook executed.\n";
} else {
    echo "Skipping hook call due to missing classes.\n";
}

// Check logs
$possible_logs = [
    GLPI_ROOT . '/files/_log/php-errors.log',
    GLPI_ROOT . '/files/_log/error.log',
    GLPI_ROOT . '/files/_log/sql-errors.log',
    '/var/log/apache2/error.log' // External log
];

echo "\n--- checking logs ---\n";
foreach ($possible_logs as $log) {
    if (file_exists($log)) {
        echo "Log: $log (Size: " . filesize($log) . " bytes)\n";
        // echo shell_exec("tail -n 5 $log");
    } else {
        echo "Log: $log NOT FOUND\n";
    }
}
