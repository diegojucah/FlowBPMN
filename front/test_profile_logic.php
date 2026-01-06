<?php
include ('/home/diego/glpi11/glpi-11.0.1/glpi/inc/includes.php');
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Loaded GLPI.\n";

// Mock Session
$_SESSION['glpidefault_entity'] = 0;
$_SESSION['glpiactive_entity']  = 0;
$_SESSION['glpilanguage']       = 'pt_BR';

echo "Instantiating PluginFlowbpmnProfile...\n";
try {
    $profile = new PluginFlowbpmnProfile();
    echo "Class instantiated.\n";
} catch (Throwable $e) {
    die("Fatal: " . $e->getMessage() . "\n");
}

$profiles_id = 1; // Super-Admin
echo "Checking rights for Profile $profiles_id...\n";
try {
    $rights = PluginFlowbpmnProfile::getProfileRights($profiles_id);
    print_r($rights);
} catch (Throwable $e) {
    die("Fatal in getProfileRights: " . $e->getMessage() . "\n");
}

// Simulate Add/Update
$input = [
    'profiles_id' => $profiles_id,
    'can_view_ticket' => 1
];

if (isset($rights['id'])) {
    echo "Updating ID " . $rights['id'] . "\n";
    $input['id'] = $rights['id'];
    $res = $profile->update($input);
    echo "Update result: " . ($res ? "OK" : "FAIL") . "\n";
} else {
    echo "Adding new...\n";
    $res = $profile->add($input);
    echo "Add result: " . ($res ? "OK" : "FAIL") . "\n";
}
