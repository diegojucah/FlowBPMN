<?php
// ajax/import_items.php

// Output buffer to prevent accidental output
ob_start();

define('GLPI_AJAX', 1);

// Initialize GLPI Environment
include('../../../inc/includes.php');
global $DB;
// Load GLPI configuration
global $CFG_GLPI, $DB;

// Force JSON response
if (!headers_sent()) {
    header("Content-Type: application/json; charset=UTF-8");
}

try {
    Session::checkLoginUser();
    
    $itemtype = $_REQUEST['itemtype'] ?? '';
    $search   = $_REQUEST['search'] ?? '';

    if (!$itemtype || !class_exists($itemtype)) {
        throw new Exception('Invalid Item Type');
    }

    if (!Session::haveRight($itemtype::$rightname, READ)) {
        throw new Exception('Access Denied');
    }

    global $DB;

    $itemTable = getTableForItemType($itemtype);
    $flowTable = 'glpi_plugin_flowbpmn_flows';

    // Search logic
    $where = [
        "$flowTable.itemtype" => $itemtype
    ];

    if (!empty($search)) {
        $where["$itemTable.name"] = ['LIKE', "%$search%"];
    }

    // Query: Get items that have at least one flow
    $iterator = $DB->request([
        'SELECT' => [
            "$itemTable.id",
            "$itemTable.name",
            "$itemTable.date_mod",
            "$itemTable.users_id_recipient", // Creator/Requester
            "$flowTable.svg_content"
        ],
        'DISTINCT' => true,
        'FROM'   => $flowTable,
        'INNER JOIN' => [
            $itemTable => [
                'ON' => [
                    $flowTable => 'items_id',
                    $itemTable => 'id'
                ]
            ]
        ],
        'WHERE'  => $where,
        'ORDER'  => "$itemTable.date_mod DESC",
        'LIMIT'  => 50
    ]);

    $items = [];
    foreach ($iterator as $row) {
        $row['date_mod_formatted'] = Html::convDateTime($row['date_mod']);
        
        $row['user_name'] = '';
        if (isset($row['users_id_recipient']) && $row['users_id_recipient'] > 0) {
            $user = new User();
            if ($user->getFromDB($row['users_id_recipient'])) {
                $row['user_name'] = $user->getName();
            }
        }
        $items[] = $row;
    }

    ob_clean(); // Clean any previous output (headers, whitespace)
    echo json_encode(['success' => true, 'items' => $items]);
    exit;

} catch (Throwable $e) {
    if (!headers_sent()) {
        http_response_code(400); 
    }
    ob_end_clean(); // Ensure buffer is cleared
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
