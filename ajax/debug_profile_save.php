<?php
include ('../../../inc/includes.php');

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug Profile Save</h1>";

try {
    if (!class_exists('PluginFlowbpmnProfile')) {
        throw new Exception("Class PluginFlowbpmnProfile not found!");
    }
    echo "Class found.<br>";

    $profile = new PluginFlowbpmnProfile();
    echo "Instance created.<br>";

    // Mock data - verify with existing profile ID 1 (usually Root/Super-Admin)
    $profiles_id = 1; 
    $mock_post = [
        'profiles_id' => $profiles_id,
        'can_view_ticket' => 1,
        'update' => 1
    ];

    echo "Attempting logic...<br>";
    
    // Logic from front/profile.form.php
    $rights = PluginFlowbpmnProfile::getProfileRights($profiles_id);
    echo "Rights check done. Result: " . print_r($rights, true) . "<br>";

    if (isset($rights['id'])) {
        $mock_post['id'] = $rights['id'];
        echo "Updating existing record ID: " . $rights['id'] . "<br>";
        $success = $profile->update($mock_post);
        echo "Update result: " . ($success ? "Success" : "Failure") . "<br>";
    } else {
        echo "Adding new record.<br>";
        $success = $profile->add($mock_post);
        echo "Add result: " . ($success ? "Success/ID: $success" : "Failure") . "<br>";
    }

} catch (Throwable $e) {
    echo "<h2 style='color:red'>Fatal Error: " . $e->getMessage() . "</h2>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
