<?php
// Debug script to test profile form POST
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== DEBUG PROFILE POST ===\n\n";

// Bootstrap GLPI
include ('../../../inc/includes.php');

echo "1. GLPI bootstrapped OK\n";

// Check session
echo "2. Session user: " . ($_SESSION['glpiname'] ?? 'not set') . "\n";

// Check rights
try {
    Session::checkRight("profile", UPDATE);
    echo "3. Rights check OK\n";
} catch (Exception $e) {
    echo "3. Rights check FAILED: " . $e->getMessage() . "\n";
    exit;
}

// Check if PluginFlowbpmnProfile class exists
if (class_exists('PluginFlowbpmnProfile')) {
    echo "4. PluginFlowbpmnProfile class exists\n";
} else {
    echo "4. PluginFlowbpmnProfile class NOT FOUND\n";
    exit;
}

// Try to instantiate
try {
    $profile = new PluginFlowbpmnProfile();
    echo "5. PluginFlowbpmnProfile instantiated OK\n";
} catch (Exception $e) {
    echo "5. Instantiation FAILED: " . $e->getMessage() . "\n";
    exit;
}

// Test update
$test_data = [
    'id' => 1,
    'profiles_id' => 4,
    'can_view_ticket' => 1,
    'can_edit_ticket' => 1,
    'can_delete_ticket' => 0,
    'can_restore_ticket' => 0,
    'can_view_problem' => 1,
    'can_edit_problem' => 1,
    'can_delete_problem' => 0,
    'can_restore_problem' => 0,
    'can_view_change' => 1,
    'can_edit_change' => 1,
    'can_delete_change' => 0,
    'can_restore_change' => 0
];

echo "6. Test data prepared\n";

try {
    $result = $profile->update($test_data);
    echo "7. Update result: " . ($result ? 'SUCCESS' : 'FAILED') . "\n";
} catch (Exception $e) {
    echo "7. Update EXCEPTION: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== DEBUG COMPLETE ===\n";
