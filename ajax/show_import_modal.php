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

Session::checkLoginUser();

$itemtype = $_GET['itemtype'] ?? 'Ticket';

// Validate itemtype
$validItemtypes = ['Ticket', 'Problem', 'Change'];
if (!in_array($itemtype, $validItemtypes)) {
    http_response_code(400);
    die('Invalid itemtype');
}

// Get translated item name
$itemName = $itemtype::getTypeName(1);

echo "<div class='modal-content'>";
echo "<div class='modal-header'>";
echo "<h5 class='modal-title'>";
echo "<i class='ti ti-download'></i> " . sprintf(__('Import from %s', 'flowbpmn'), $itemName);
echo "</h5>";
echo "<button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>";
echo "</div>";
echo "<div class='modal-body'>";
echo "<div class='mb-3'>";
echo "<label class='form-label'>" . sprintf(__('Select %s', 'flowbpmn'), $itemName) . ":</label>";

// Native GLPI Dropdown
// Only show items that have BPMN diagrams
Dropdown::show($itemtype, [
    'name' => 'import_item_id',
    'condition' => [
        'id' => new \QuerySubQuery([
            'SELECT' => 'items_id',
            'DISTINCT' => true,
            'FROM' => 'glpi_plugin_flowbpmn_flows',
            'WHERE' => [
                'itemtype' => $itemtype,
                'bpmn_xml' => ['<>', ''],
                'bpmn_xml' => ['IS NOT', null]
            ]
        ])
    ],
    'display_emptychoice' => true,
    'width' => '100%'
]);

echo "</div>";
echo "</div>";
echo "<div class='modal-footer'>";
echo "<button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>" . __('Cancel') . "</button>";
echo "<button type='button' class='btn btn-primary' id='confirm-import-btn'>";
echo "<i class='ti ti-download'></i> " . __('Import');
echo "</button>";
echo "</div>";
echo "</div>";
