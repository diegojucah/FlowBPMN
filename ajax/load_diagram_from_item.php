<?php
/**
 * -------------------------------------------------------------------------
 * flowBPMN Plugin for GLPI
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2024 by KactuX
 * @license   GPLv3 https://www.gnu.org/licenses/gpl-3.0.html
 * -------------------------------------------------------------------------
 */

// Buffer output immediately to catch Warnings during boot
ob_start();

// Initialize GLPI Environment
include('../../../inc/includes.php');
global $DB;

// Clean buffer after boot (remove Deprecation warnings etc)
ob_end_clean();

header('Content-Type: application/json; charset=UTF-8');

// Start robust buffer for JSON response
ob_start();

try {
    Session::checkLoginUser();

    // Get parameters
    $itemtype = $_POST['itemtype'] ?? '';
    $items_id = (int)($_POST['items_id'] ?? 0);
    // flow_id is optional or ignored for import from item
    
    if (!$itemtype || !$items_id) {
        throw new Exception(__('Missing required parameters', 'flowbpmn'));
    }
    
    // Validate itemtype
    $validItemtypes = ['Ticket', 'Problem', 'Change'];
    if (!in_array($itemtype, $validItemtypes)) {
        throw new Exception(__('Invalid item type', 'flowbpmn'));
    }
    
    // Check if user has permission to view the item
    $item = new $itemtype();
    if (!$item->getFromDB($items_id)) {
        throw new Exception(__('Item not found in database', 'flowbpmn'));
    }
    
    if (!$item->canViewItem()) {
        throw new Exception(__('You do not have permission to view this item', 'flowbpmn'));
    }
    
    // Ensure plugin class is loaded
    if (!class_exists('PluginFlowbpmnFlow')) {
        include_once(__DIR__ . '/../inc/flow.class.php');
    }

    // Load the flow associated with this item
    $flow = new PluginFlowbpmnFlow();
    // Try to find flow for this item
    if (!$flow->getFromDBByCrit(['itemtype' => $itemtype, 'items_id' => $items_id])) {
         // If no flow exists for that item, we can't import anything
         throw new Exception(__('No diagram found for this item', 'flowbpmn'));
    }
    
    // Verify flow belongs to the item
    // NOTE: This check might be too strict if we are allowing import from ANY item
    // But for "load_diagram_from_item", it implies we are loading THAT item's flow.
    // However, the caller script might be passing the source item's ID.
    if ($flow->fields['itemtype'] != $itemtype || $flow->fields['items_id'] != $items_id) {
        // If the flow ID provided doesn't match the item ID provided, it might be a mismatch in logic.
        // But let's assume valid input for now.
        // throw new Exception(__('Flow does not belong to this item', 'flowbpmn'));
    }
    
    // Get BPMN XML
    $bpmn_xml = $flow->fields['bpmn_xml'];
    
    if (empty($bpmn_xml)) {
        throw new Exception(__('No diagram found', 'flowbpmn'));
    }
    
    // Decompress if needed
    if (strpos($bpmn_xml, 'COMPRESSED::') === 0) {
        $encoded = substr($bpmn_xml, 12);
        $compressed = base64_decode($encoded);
        if ($compressed !== false) {
            $decompressed = gzuncompress($compressed);
            if ($decompressed !== false) {
                $bpmn_xml = $decompressed;
            }
        }
    }
    
    // Format date
    $dateFormatted = Html::convDateTime($flow->fields['date_mod']);
    
    // Build source name
    $sourceName = sprintf(
        '%s #%d',
        $itemtype,
        $items_id
    );
    
    if (!empty($item->fields['name'])) {
        $sourceName .= ' - ' . $item->fields['name'];
    }
    
    // Success response
    ob_clean(); // Clean any previous noise (warnings/notices)
    echo json_encode([
        'success' => true,
        'bpmn_xml' => $bpmn_xml,
        'name' => $flow->fields['name'],
        'source_name' => $sourceName,
        'date_mod' => $flow->fields['date_mod'],
        'date_mod_formatted' => $dateFormatted
    ]);
    exit;
    
} catch (Throwable $e) {
    ob_clean(); // Clean buffer before error output
    http_response_code(500); // Use 500 for server errors
    error_log("FlowBPMN Import Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
