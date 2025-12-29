<?php
/**
 * -------------------------------------------------------------------------
 * flowBPMN Plugin for GLPI
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2024 by KactuX
 * @license   GPLv3 https://www.gnu.org/licenses/gpl-3.0.html
 * -------------------------------------------------------------------------
 */

include ('../../../inc/includes.php');

header('Content-Type: application/json; charset=UTF-8');

Session::checkLoginUser();

try {
    // Get parameters
    $flow_id = (int)($_POST['flow_id'] ?? 0);
    $itemtype = $_POST['itemtype'] ?? '';
    $items_id = (int)($_POST['items_id'] ?? 0);
    
    if (!$flow_id || !$itemtype || !$items_id) {
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
        throw new Exception(__('Flow not found', 'flowbpmn'));
    }
    
    if (!$item->canViewItem()) {
        throw new Exception(__('You do not have permission to view this item', 'flowbpmn'));
    }
    
    // Load the flow
    $flow = new PluginFlowbpmnFlow();
    if (!$flow->getFromDB($flow_id)) {
        throw new Exception(__('Flow not found', 'flowbpmn'));
    }
    
    // Verify flow belongs to the item
    if ($flow->fields['itemtype'] != $itemtype || $flow->fields['items_id'] != $items_id) {
        throw new Exception(__('Flow not found', 'flowbpmn'));
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
    
    echo json_encode([
        'success' => true,
        'bpmn_xml' => $bpmn_xml,
        'name' => $flow->fields['name'],
        'source_name' => $sourceName,
        'date_mod' => $flow->fields['date_mod'],
        'date_mod_formatted' => $dateFormatted
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
