<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

// Check if the request is an AJAX request
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    http_response_code(403);
    die("Forbidden");
}

// Check required parameters
if (!isset($_POST['itemtype'], $_POST['items_id'], $_POST['bpmn'])) {
    http_response_code(400);
    die("Bad request");
}

// Check if the user has the right to update the item
$item = getItemForItemtype($_POST['itemtype']);
if (!$item || !$item->getFromDB((int)$_POST['items_id'])) {
    http_response_code(404);
    die("Item not found");
}

// Check if the user has the right to update the item
if (!$item->canUpdateItem()) {
    http_response_code(403);
    die("Forbidden");
}

// Sanitize input
$bpmn = $_POST['bpmn'];
$itemtype = $_POST['itemtype'];
$items_id = (int)$_POST['items_id'];

// Save the BPMN data to the database
try {
    global $DB;
    
    // Check if a record already exists
    $existing = $DB->request([
        'SELECT id FROM glpi_plugin_flowbpmn_flows
         WHERE itemtype = ? AND items_id = ?',
        [$itemtype, $items_id]
    ]);
    
    if (count($existing)) {
        // Update existing record
        $DB->update('glpi_plugin_flowbpmn_flows', [
            'bpmn' => $bpmn,
            'date_mod' => date('Y-m-d H:i:s')
        ], [
            'id' => $existing->current()['id']
        ]);
    } else {
        // Create new record
        $DB->insert('glpi_plugin_flowbpmn_flows', [
            'itemtype' => $itemtype,
            'items_id' => $items_id,
            'bpmn' => $bpmn,
            'date_creation' => date('Y-m-d H:i:s'),
            'date_mod' => date('Y-m-d H:i:s')
        ]);
    }
    
    // Return success response
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
