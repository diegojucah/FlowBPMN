<?php

global $CFG_GLPI;

// Ensure plugin is installed and activated
if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

// Check if it's an AJAX request
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("This script can only be accessed via AJAX");
}

// Check required parameters
$required_params = ['itemtype', 'items_id'];
foreach ($required_params as $param) {
    if (!isset($_GET[$param])) {
        sendJsonResponse(false, "Missing required parameter: $param");
    }
}

$itemtype = $_GET['itemtype'];
$items_id = (int)$_GET['items_id'];

// Validate item type
$valid_itemtypes = ['Ticket', 'Change', 'Problem'];
if (!in_array($itemtype, $valid_itemtypes)) {
    sendJsonResponse(false, "Invalid item type");
}

// Check if item exists
if (!($item = getItemForItemtype($itemtype)) || !$item->getFromDB($items_id)) {
    sendJsonResponse(false, "$itemtype not found");
}

// Check read permissions
if (!$item->can($items_id, READ)) {
    sendJsonResponse(false, __("You don't have permission to view this item."));
}

// Get versions from database
$bpmnFlow = new PluginFlowbpmnFlow();
$versions = $bpmnFlow->find([
    'itemtype' => $itemtype,
    'items_id' => $items_id
], 'date_mod DESC');

// Format the response
$response = [];
foreach ($versions as $version) {
    $response[] = [
        'id' => $version['id'],
        'date_creation' => $version['date_creation'],
        'date_mod' => $version['date_mod'],
        'users_id' => $version['users_id'],
        'user_name' => getUserName($version['users_id'])
    ];
}

sendJsonResponse(true, "Versions retrieved successfully", $response);

/**
 * Send a JSON response
 */
function sendJsonResponse($success, $message = '', $data = []) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data'    => $data
    ]);
    exit();
}

class PluginFlowbpmnFlow extends CommonDBTM {
    
    static $rightname = 'ticket';
    
    /**
     * Get the table name for this item type
     */
    static function getTable($classname = null) {
        return 'glpi_plugin_flowbpmn_flows';
    }
    
    /**
     * Get the name of this item type
     */
    static function getTypeName($nb = 0) {
        return __('BPMN Flow', 'flowbpmn');
    }
}
