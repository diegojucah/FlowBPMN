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
$required_params = ['id', 'itemtype', 'items_id'];
$input = json_decode(file_get_contents('php://input'), true);

foreach ($required_params as $param) {
    if (!isset($input[$param])) {
        sendJsonResponse(false, "Missing required parameter: $param");
    }
}

$version_id = (int)$input['id'];
$itemtype = $input['itemtype'];
$items_id = (int)$input['items_id'];

// Validate item type
$valid_itemtypes = ['Ticket', 'Change', 'Problem'];
if (!in_array($itemtype, $valid_itemtypes)) {
    sendJsonResponse(false, "Invalid item type");
}

// Check if item exists
if (!($item = getItemForItemtype($itemtype)) || !$item->getFromDB($items_id)) {
    sendJsonResponse(false, "$itemtype not found");
}

// Check write permissions
if (!$item->can($items_id, UPDATE)) {
    sendJsonResponse(false, __("You don't have permission to perform this action."));
}

// Get the version to restore
$bpmnFlow = new PluginFlowbpmnFlow();
if (!$bpmnFlow->getFromDB($version_id)) {
    sendJsonResponse(false, "Version not found");
}

// Verify the version belongs to the same item
if ($bpmnFlow->fields['itemtype'] !== $itemtype || $bpmnFlow->fields['items_id'] != $items_id) {
    sendJsonResponse(false, "Version does not belong to this item");
}

// Create a new version with the restored content
$newVersion = [
    'itemtype'      => $itemtype,
    'items_id'      => $items_id,
    'bpmn'          => $bpmnFlow->fields['bpmn'],
    'svg'           => $bpmnFlow->fields['svg'],
    'users_id'      => Session::getLoginUserID(),
    'date_creation' => $_SESSION['glpi_currenttime'],
    'date_mod'      => $_SESSION['glpi_currenttime']
];

if ($bpmnFlow->add($newVersion)) {
    // Update the item's content to include the SVG preview
    $item->update([
        'id'      => $items_id,
        'content' => updateContentWithBpmnPreview($item->fields['content'], $bpmnFlow->fields['svg'])
    ]);
    
    sendJsonResponse(true, "Version restored successfully", [
        'bpmn' => $bpmnFlow->fields['bpmn'],
        'svg'  => $bpmnFlow->fields['svg']
    ]);
} else {
    sendJsonResponse(false, "Failed to restore version");
}

/**
 * Helper function to update item content with BPMN preview
 */
function updateContentWithBpmnPreview($content, $svg) {
    $bpmnPreview = "\n\n## BPMN Diagram\n\n<div class='bpmn-preview'>$svg</div>\n";
    
    // Check if there's already a BPMN preview and replace it
    if (preg_match('/## BPMN Diagram.*<\/div>/s', $content)) {
        return preg_replace('/## BPMN Diagram.*<\/div>/s', $bpmnPreview, $content);
    }
    
    // Otherwise, append the preview
    return $content . $bpmnPreview;
}

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
