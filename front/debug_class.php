<?php
include ('../../../inc/includes.php');
header('Content-Type: text/plain');
echo "Includes loaded.\n";

try {
    if (class_exists('PluginFlowbpmnProfile')) {
        echo "Class exists.\n";
        $p = new PluginFlowbpmnProfile();
        echo "Class instantiated.\n";
    } else {
        echo "Class PluginFlowbpmnProfile NOT found.\n";
    }
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
