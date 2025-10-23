<?php

/**
 * -------------------------------------------------------------------------
 * flowBPMN Plugin for GLPI - AJAX Flow Handler
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2024 by KactuX
 * @license   GPLv3 https://www.gnu.org/licenses/gpl-3.0.html
 * @link      https://github.com/diegojucah/pluginBPMN
 * -------------------------------------------------------------------------
 */

define('GLPI_ROOT', '../../..');
include (GLPI_ROOT . "/inc/includes.php");

header('Content-Type: application/json; charset=UTF-8');

use Glpi\Event;

Session::checkLoginUser();

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

try {
    
    switch ($action) {
        case 'save':
            // Validate inputs
            if (empty($input['itemtype']) || empty($input['items_id']) || empty($input['bpmn_xml'])) {
                throw new Exception('Missing required parameters');
            }
            
            // Check permissions
            if (!PluginFlowbpmnProfile::canEditFlow($input['itemtype'])) {
                throw new Exception('Permission denied');
            }
            
            // Save flow
            $flow = new PluginFlowbpmnFlow();
            $id = $flow->saveFlow(
                $input['itemtype'],
                (int)$input['items_id'],
                $input['bpmn_xml'],
                $input['svg_content'] ?? '',
                $input['name'] ?? ''
            );
            
            if ($id) {
                echo json_encode(['success' => true, 'id' => $id]);
            } else {
                throw new Exception('Failed to save flow');
            }
            break;
            
        case 'load':
            // Validate inputs
            if (empty($input['itemtype']) || empty($input['items_id'])) {
                throw new Exception('Missing required parameters');
            }
            
            // Check permissions
            if (!PluginFlowbpmnProfile::canViewFlow($input['itemtype'])) {
                throw new Exception('Permission denied');
            }
            
            // Load flow
            $flow = new PluginFlowbpmnFlow();
            $data = $flow->getForItem($input['itemtype'], (int)$input['items_id']);
            
            echo json_encode(['success' => true, 'data' => $data]);
            break;
            
        case 'delete':
            // Validate inputs
            if (empty($input['id'])) {
                throw new Exception('Missing required parameters');
            }
            
            // Check permissions (simplified - would need itemtype check in real implementation)
            if (!Session::haveRight('plugin_flowbpmn', DELETE)) {
                throw new Exception('Permission denied');
            }
            
            // Delete flow
            $flow = new PluginFlowbpmnFlow();
            if ($flow->deleteFlow((int)$input['id'])) {
                echo json_encode(['success' => true]);
            } else {
                throw new Exception('Failed to delete flow');
            }
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
