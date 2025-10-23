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
$required_params = ['bpmn', 'svg', 'itemtype', 'items_id'];
foreach ($required_params as $param) {
    if (!isset($_POST[$param])) {
        sendJsonResponse(false, "Missing required parameter: $param");
    }
}

$bpmn = $_POST['bpmn'];
$svg = $_POST['svg'];
$itemtype = $_POST['itemtype'];
$items_id = (int)$_POST['items_id'];

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

// Save to database
$bpmnFlow = new PluginFlowbpmnFlow();
$existing = $bpmnFlow->find([
    'itemtype' => $itemtype,
    'items_id' => $items_id
], 'date_mod DESC', 1);

$input = [
    'itemtype'    => $itemtype,
    'items_id'    => $items_id,
    'bpmn'        => $bpmn,
    'svg'         => $svg,
    'users_id'    => Session::getLoginUserID(),
    'date_mod'    => $_SESSION['glpi_currenttime']
];

// If this is an update to an existing flow, preserve the creation date
if (count($existing) > 0) {
    $existing = reset($existing);
    $input['id'] = $existing['id'];
    $input['date_creation'] = $existing['date_creation'];
}

// Save the flow
if ($bpmnFlow->save($input)) {
    // Update the item's content to include the SVG preview
    $item->update([
        'id'      => $items_id,
        'content' => $this->updateContentWithBpmnPreview($item->fields['content'], $svg)
    ]);
    
    sendJsonResponse(true, "BPMN diagram saved successfully", [
        'id' => $bpmnFlow->getID()
    ]);
} else {
    sendJsonResponse(false, "Failed to save BPMN diagram");
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
    
    /**
     * Get the tab name for this item type
     */
    function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
        if (in_array($item->getType(), ['Ticket', 'Change', 'Problem'])) {
            return __('BPMN', 'flowbpmn');
        }
        return '';
    }
    
    /**
     * Display the content of the tab
     */
    static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
        if (in_array($item->getType(), ['Ticket', 'Change', 'Problem'])) {
            $bpmn = new self();
            $bpmn->showForm($item);
        }
        return true;
    }
    
    /**
     * Display the BPMN editor form
     */
    function showForm(CommonDBTM $item) {
        global $CFG_GLPI;
        
        // Load required CSS and JS
        echo Html::css(Plugin::getWebDir('flowBPMN') . "/css/bpmn.css");
        
        // Add BPMN editor container
        echo "<div id='bpmn-editor' data-itemtype='" . $item->getType() . "' data-items-id='" . $item->getID() . "'></div>";
        
        // Add BPMN editor script
        $js_path = Plugin::getWebDir('flowBPMN') . "/front/bpmn-js/editor.js";
        echo Html::script($js_path);
        
        // Add Font Awesome for icons
        echo Html::css("https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css");
    }
}
