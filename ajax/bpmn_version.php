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

// Check required parameter
if (!isset($_GET['id'])) {
    sendJsonResponse(false, "Missing required parameter: id");
}

$version_id = (int)$_GET['id'];

// Get the version
$bpmnFlow = new PluginFlowbpmnFlow();
if (!$bpmnFlow->getFromDB($version_id)) {
    sendJsonResponse(false, "Version not found");
}

// Check if the current user has permission to view the item
$itemtype = $bpmnFlow->fields['itemtype'];
$items_id = $bpmnFlow->fields['items_id'];

// Check if item exists and user has permission
if (!($item = getItemForItemtype($itemtype)) || !$item->getFromDB($items_id)) {
    sendJsonResponse(false, "Item not found");
}

// Check read permissions
if (!$item->can($items_id, READ)) {
    sendJsonResponse(false, __("You don't have permission to view this item."));
}

// Return the version data
sendJsonResponse(true, "Version retrieved successfully", [
    'id' => $bpmnFlow->fields['id'],
    'itemtype' => $bpmnFlow->fields['itemtype'],
    'items_id' => $bpmnFlow->fields['items_id'],
    'bpmn' => $bpmnFlow->fields['bpmn'],
    'svg' => $bpmnFlow->fields['svg'],
    'users_id' => $bpmnFlow->fields['users_id'],
    'user_name' => getUserName($bpmnFlow->fields['users_id']),
    'date_creation' => $bpmnFlow->fields['date_creation'],
    'date_mod' => $bpmnFlow->fields['date_mod']
]);

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
