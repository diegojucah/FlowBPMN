<?php

/**
 * -------------------------------------------------------------------------
 * flowBPMN Plugin for GLPI - AJAX Restore Version Handler
 * -------------------------------------------------------------------------
 */

// Define GLPI root for proper includes
if (!defined('GLPI_ROOT')) {
    define('GLPI_ROOT', dirname(__DIR__, 3));
}

include (GLPI_ROOT . '/inc/includes.php');

header('Content-Type: application/json; charset=UTF-8');

Session::checkLoginUser();

$input = json_decode(file_get_contents('php://input'), true);

try {
    // Get input parameters
    $flow_id = (int)($input['flow_id'] ?? 0);
    $version_id = (int)($input['version_id'] ?? 0);

    if (!$flow_id || !$version_id) {
        throw new Exception(__('Missing required parameters', 'flowbpmn'));
    }

    // Get flow to check permissions
    $flow = new PluginFlowbpmnFlow();
    if (!$flow->getFromDB($flow_id)) {
        throw new Exception(__('Flow not found', 'flowbpmn'));
    }

    // Check permissions
    if (!PluginFlowbpmnProfile::canRestoreFlow($flow->fields['itemtype'])) {
        throw new Exception(__('Permission denied', 'flowbpmn'));
    }

    // Get version
    $version = new PluginFlowbpmnVersion();
    if (!$version->getFromDB($version_id)) {
        throw new Exception(__('Version not found', 'flowbpmn'));
    }

    // Verify version belongs to flow
    if ($version->fields['plugin_flowbpmn_flows_id'] != $flow_id) {
        throw new Exception(__('Version does not belong to this flow', 'flowbpmn'));
    }

    // Restore version
    if (PluginFlowbpmnVersion::restoreVersion($flow_id, $version_id)) {
        echo json_encode([
            'success' => true,
            'message' => __('Version restored successfully', 'flowbpmn'),
            'bpmn_xml' => $version->fields['bpmn_xml'],
            'svg_content' => $version->fields['svg_content']
        ]);
    } else {
        throw new Exception(__('Failed to restore version', 'flowbpmn'));
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
