<?php
// Debug Script for FlowBPMN
// Run this via CLI: php test_debug.php
// Or access via Browser: /plugins/flowbpmn/test_debug.php

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>FlowBPMN Debug</h1>";

// 1. Root Path
$glpi_root = dirname(__DIR__, 3);
echo "GLPI Root: " . $glpi_root . "<br>";

// 2. Autoload
if (file_exists($glpi_root . '/vendor/autoload.php')) {
    echo "Autoload: Found<br>";
    require_once $glpi_root . '/vendor/autoload.php';
} else {
    echo "Autoload: NOT Found<br>";
}

// 3. Kernel Boot
use Glpi\Kernel\Kernel;
use Glpi\Application\Environment;

try {
    if (class_exists('Glpi\Kernel\Kernel')) {
        echo "Kernel Class: Exists<br>";
        $kernel = new Kernel(Environment::PRODUCTION->value, false);
        $kernel->boot();
        echo "Kernel Boot: Success<br>";
    } else {
        echo "Kernel Class: Not Found<br>";
    }
} catch (\Exception $e) {
    echo "Kernel Boot Error: " . $e->getMessage() . "<br>";
}

// 4. Checking Profile Class
$inc_dir = __DIR__ . '/inc';
echo "Checking Profile Class...<br>";

if (class_exists('PluginFlowbpmnProfile')) {
    echo "Class PluginFlowbpmnProfile: Loaded via Autoload<br>";
} else {
    // Manual include
    $classFile = __DIR__ . '/inc/profile.class.php';
    if (file_exists($classFile)) {
        echo "File profile.class.php: Found<br>";
        include_once $classFile;
        if (class_exists('PluginFlowbpmnProfile')) {
             echo "Class PluginFlowbpmnProfile: Loaded manually<br>";
        } else {
             echo "Class PluginFlowbpmnProfile: FAILED to load even after include<br>";
        }
    } else {
        echo "File profile.class.php: NOT Found at $classFile<br>";
    }
}

// 5. Check DB
global $DB;
if ($DB) {
     echo "DB Connection: OK<br>";
} else {
     echo "DB Connection: FAILED<br>";
}

echo "Done.";
?>
