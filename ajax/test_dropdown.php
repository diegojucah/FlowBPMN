<?php
// Test file to debug the error
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    include('../../../inc/includes.php');
    echo "GLPI loaded successfully<br>";
    
    Session::checkLoginUser();
    echo "Session checked<br>";
    
    $itemtype = 'Ticket';
    echo "Itemtype: $itemtype<br>";
    
    $itemName = $itemtype::getTypeName(1);
    echo "Item name: $itemName<br>";
    
    echo "About to call Dropdown::show()<br>";
    
    Dropdown::show($itemtype, [
        'name' => 'test_dropdown',
        'display_emptychoice' => true
    ]);
    
    echo "<br>Dropdown rendered successfully!";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
    echo "<br>Trace: " . $e->getTraceAsString();
}
