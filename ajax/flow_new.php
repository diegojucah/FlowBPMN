<?php
/**
 * -------------------------------------------------------------------------
 * flowBPMN Plugin for GLPI - AJAX Flow Handler (Simplified & Robust)
 * -------------------------------------------------------------------------
 */

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Buffer control
if (ob_get_level()) ob_end_clean();
ob_start();

// Define GLPI_ROOT
define('GLPI_ROOT', dirname(__FILE__, 4));

// Load GLPI
require_once(GLPI_ROOT . '/inc/includes.php');

// Load plugin classes
require_once(GLPI_ROOT . '/plugins/flowbpmn/inc/flow.class.php');
require_once(GLPI_ROOT . '/plugins/flowbpmn/inc/profile.class.php');
require_once(GLPI_ROOT . '/plugins/flowbpmn/inc/config.class.php');
require_once(GLPI_ROOT . '/plugins/flowbpmn/inc/version.class.php');

// Clear buffer and set JSON header
ob_end_clean();
header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

// Check session
Session::checkLoginUser();

// Get input
$input = json_decode(file_get_contents('php://input'), true);
$action = isset($input['action']) ? $input['action'] : '';

// Log request
error_log("flowBPMN Request: action=$action");

// Process action
try {
    switch ($action) {
        case 'save':
            // Validate
            if (empty($input['itemtype']) || empty($input['items_id']) || empty($input['bpmn_xml'])) {
                throw new Exception('Parâmetros obrigatórios ausentes');
            }

            // Validate itemtype
            if (!in_array($input['itemtype'], ['Ticket', 'Problem', 'Change'])) {
                throw new Exception('Tipo de item inválido');
            }

            // Check permission (simplified)
            $can_edit = true; // Temporary: allow all
            if (method_exists('PluginFlowbpmnProfile', 'canEditFlow')) {
                $can_edit = PluginFlowbpmnProfile::canEditFlow($input['itemtype']);
            }

            if (!$can_edit) {
                throw new Exception('Permissão negada');
            }

            // Save
            $flow = new PluginFlowbpmnFlow();
            $id = $flow->saveFlow(
                $input['itemtype'],
                (int)$input['items_id'],
                $input['bpmn_xml'],
                isset($input['svg_content']) ? $input['svg_content'] : '',
                isset($input['name']) ? $input['name'] : '',
                isset($input['png_data']) ? $input['png_data'] : ''
            );

            if ($id) {
                error_log("flowBPMN: Flow saved with ID $id");
                echo json_encode([
                    'success' => true,
                    'id' => $id,
                    'message' => 'Fluxo flowBPMN salvo com sucesso!'
                ]);
            } else {
                throw new Exception('Falha ao salvar fluxo no banco de dados');
            }
            break;

        case 'load':
            if (empty($input['itemtype']) || empty($input['items_id'])) {
                throw new Exception('Parâmetros obrigatórios ausentes');
            }

            $flow = new PluginFlowbpmnFlow();
            $data = $flow->getForItem($input['itemtype'], (int)$input['items_id']);

            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'versions':
            if (empty($input['itemtype']) || empty($input['items_id'])) {
                throw new Exception('Parâmetros obrigatórios ausentes');
            }

            $flow = new PluginFlowbpmnFlow();
            $versions = $flow->getHistory($input['itemtype'], (int)$input['items_id']);

            echo json_encode(['success' => true, 'versions' => $versions]);
            break;

        default:
            throw new Exception('Ação inválida: ' . $action);
    }

} catch (Exception $e) {
    error_log('flowBPMN ERROR: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

exit;
