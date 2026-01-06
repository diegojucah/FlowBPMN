<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function my_shutdown() {
    $error = error_get_last();
    if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE || $error['type'] === E_COMPILE_ERROR)) {
        echo "<h1>FATAL ERROR CAUGHT</h1>";
        echo "<pre>" . print_r($error, true) . "</pre>";
    }
}
register_shutdown_function('my_shutdown');

echo "<h2>FlowBPMN Diagnostics</h2>";
echo "Step 1: Including includes.php...<br>";

try {
    include ('../../../inc/includes.php');
} catch (Throwable $e) {
    echo "<b>Exception during include:</b> " . $e->getMessage() . "<br>";
}

echo "Step 2: Checking Classes...<br>";
$classes = ['PluginFlowbpmn', 'PluginFlowbpmnProfile', 'PluginFlowbpmnConfig', 'PluginFlowbpmnFlow'];
foreach ($classes as $class) {
    echo "Checking $class: " . (class_exists($class) ? "<span style='color:green'>OK</span>" : "<span style='color:red'>MISSING</span>") . "<br>";
}

echo "Step 3: Checking Tables...<br>";
global $DB;
if (isset($DB)) {
    $tables = ['glpi_plugin_flowbpmn_profiles', 'glpi_plugin_flowbpmn_configs', 'glpi_plugin_flowbpmn_flows'];
    foreach ($tables as $table) {
        echo "Checking $table: " . ($DB->tableExists($table) ? "<span style='color:green'>OK</span>" : "<span style='color:red'>MISSING</span>") . "<br>";
    }
} else {
    echo "<span style='color:red'>DB Object not found!</span><br>";
}

echo "Diagnostics Complete.<br>";
