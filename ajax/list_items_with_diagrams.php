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
    $itemtype = $_GET['itemtype'] ?? '';
    $search = $_GET['search'] ?? '';
    $page = (int)($_GET['page'] ?? 1);
    $limit = 20; // Items per page
    
    // Validate itemtype
    $validItemtypes = ['Ticket', 'Problem', 'Change'];
    if (!in_array($itemtype, $validItemtypes)) {
        throw new Exception(__('Invalid item type', 'flowbpmn'));
    }
    
    global $DB;
    
    // Build query to find items with BPMN diagrams
    $where = [
        'glpi_plugin_flowbpmn_flows.itemtype' => $itemtype,
        'glpi_plugin_flowbpmn_flows.bpmn_xml' => ['<>', ''],
        'glpi_plugin_flowbpmn_flows.bpmn_xml' => ['IS NOT', null]
    ];
    
    // Add search filter if provided
    if (!empty($search)) {
        $itemTable = getTableForItemType($itemtype);
        $where[] = [
            'OR' => [
                "$itemTable.name" => ['LIKE', "%$search%"],
                "$itemTable.id" => ['=', $search]
            ]
        ];
    }
    
    // Get total count
    $countQuery = [
        'COUNT' => 'cpt',
        'FROM' => 'glpi_plugin_flowbpmn_flows',
        'INNER JOIN' => [
            getTableForItemType($itemtype) => [
                'ON' => [
                    'glpi_plugin_flowbpmn_flows' => 'items_id',
                    getTableForItemType($itemtype) => 'id'
                ]
            ]
        ],
        'WHERE' => $where
    ];
    
    $countIterator = $DB->request($countQuery);
    $totalCount = $countIterator->current()['cpt'];
    
    // Get items
    $offset = ($page - 1) * $limit;
    
    $itemTable = getTableForItemType($itemtype);
    $query = [
        'SELECT' => [
            'glpi_plugin_flowbpmn_flows.id AS flow_id',
            'glpi_plugin_flowbpmn_flows.items_id',
            'glpi_plugin_flowbpmn_flows.name AS flow_name',
            'glpi_plugin_flowbpmn_flows.date_mod',
            "$itemTable.name AS item_name",
            "$itemTable.status"
        ],
        'FROM' => 'glpi_plugin_flowbpmn_flows',
        'INNER JOIN' => [
            $itemTable => [
                'ON' => [
                    'glpi_plugin_flowbpmn_flows' => 'items_id',
                    $itemTable => 'id'
                ]
            ]
        ],
        'WHERE' => $where,
        'ORDER' => 'glpi_plugin_flowbpmn_flows.date_mod DESC',
        'LIMIT' => $limit,
        'START' => $offset
    ];
    
    $iterator = $DB->request($query);
    
    $items = [];
    foreach ($iterator as $row) {
        // Check if user has read permission on this item
        $item = new $itemtype();
        if (!$item->getFromDB($row['items_id'])) {
            continue;
        }
        
        if (!$item->canViewItem()) {
            continue;
        }
        
        // Get status name
        $statusName = '';
        if (isset($row['status'])) {
            $statusName = $item->getStatus($row['status']);
        }
        
        // Format date
        $dateFormatted = Html::convDateTime($row['date_mod']);
        
        // Build display name
        $displayName = sprintf(
            '%s #%d - %s',
            $itemtype,
            $row['items_id'],
            $row['item_name'] ?: $row['flow_name']
        );
        
        if ($statusName) {
            $displayName .= " ($statusName)";
        }
        
        $items[] = [
            'id' => $row['flow_id'],
            'items_id' => $row['items_id'],
            'text' => $displayName,
            'name' => $row['item_name'] ?: $row['flow_name'],
            'status' => $statusName,
            'date_mod' => $row['date_mod'],
            'date_mod_formatted' => $dateFormatted
        ];
    }
    
    echo json_encode([
        'success' => true,
        'items' => $items,
        'pagination' => [
            'more' => ($page * $limit) < $totalCount,
            'total' => $totalCount,
            'page' => $page
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
