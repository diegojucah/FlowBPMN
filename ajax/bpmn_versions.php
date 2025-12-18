<?php

/**
 * -------------------------------------------------------------------------
 * flowBPMN Plugin for GLPI - AJAX Versions Handler
 * -------------------------------------------------------------------------
 */

// Start output buffering to prevent any unwanted output
ob_start();

// Define GLPI root for proper includes
if (!defined('GLPI_ROOT')) {
    define('GLPI_ROOT', dirname(__DIR__, 3));
}

include (GLPI_ROOT . '/inc/includes.php');

// Clean any previous output and set headers
ob_end_clean();
header('Content-Type: application/json; charset=UTF-8');

Session::checkLoginUser();

try {
    // Get input parameters
    $itemtype = $_GET['itemtype'] ?? '';
    $items_id = (int)($_GET['items_id'] ?? 0);

    // Validate item type
    if (!in_array($itemtype, ['Ticket', 'Problem', 'Change'])) {
        throw new Exception(__('Invalid item type', 'flowbpmn'));
    }

    // Check permissions
    if (!PluginFlowbpmnProfile::canViewFlow($itemtype)) {
        throw new Exception(__('Permission denied', 'flowbpmn'));
    }

    // Get current flow
    $flow = new PluginFlowbpmnFlow();
    $currentFlow = $flow->getForItem($itemtype, $items_id);

    if (!$currentFlow) {
        echo json_encode([
            'success' => true,
            'versions' => [],
            'current' => null
        ]);
        exit;
    }

    // Get versions
    $versions = PluginFlowbpmnVersion::getVersions($currentFlow['id']);

    // Format versions for display
    $formattedVersions = [];
    foreach ($versions as $version) {
        $formattedVersions[] = [
            'id' => $version['id'],
            'version_number' => $version['version_number'],
            'name' => $version['name'],
            'comment' => $version['comment'],
            'users_id' => $version['users_id'],
            'user_name' => getUserName($version['users_id']),
            'date_creation' => $version['date_creation'],
            'date_creation_formatted' => Html::convDateTime($version['date_creation'])
        ];
    }

    echo json_encode([
        'success' => true,
        'versions' => $formattedVersions,
        'current' => [
            'id' => $currentFlow['id'],
            'name' => $currentFlow['name'],
            'date_mod' => $currentFlow['date_mod'],
            'date_mod_formatted' => Html::convDateTime($currentFlow['date_mod']),
            'user_name' => getUserName($currentFlow['users_id'])
        ],
        'canRestore' => PluginFlowbpmnProfile::canRestoreFlow($itemtype)
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
