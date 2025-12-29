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
    $itemtype = $_POST['itemtype'] ?? '';
    $items_id = (int)($_POST['items_id'] ?? 0);
    
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
        throw new Exception(__('Item not found', 'flowbpmn'));
    }
    
    if (!$item->canViewItem()) {
        throw new Exception(__('You do not have permission to view this item', 'flowbpmn'));
    }
    
    global $DB;
    
    // Get flow_id for this item
    $iterator = $DB->request([
        'SELECT' => ['id'],
        'FROM' => 'glpi_plugin_flowbpmn_flows',
        'WHERE' => [
            'itemtype' => $itemtype,
            'items_id' => $items_id,
            'bpmn_xml' => ['<>', ''],
            'bpmn_xml' => ['IS NOT', null]
        ],
        'LIMIT' => 1
    ]);
    
    if (count($iterator)) {
        $row = $iterator->current();
        echo json_encode([
            'success' => true,
            'flow_id' => $row['id']
        ]);
    } else {
        throw new Exception(__('No diagram found', 'flowbpmn'));
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
