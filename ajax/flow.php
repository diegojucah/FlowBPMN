<?php

/**
 * -------------------------------------------------------------------------
 * flowBPMN Plugin for GLPI - AJAX Flow Handler
 * -------------------------------------------------------------------------
 */

// Disable all error output to prevent JSON corruption
ini_set('display_errors', '0');
error_reporting(E_ALL);

// Start output buffering to prevent any unwanted output
ob_start();

// Define GLPI root for proper includes
if (!defined('GLPI_ROOT')) {
    define('GLPI_ROOT', dirname(__DIR__, 3));
}

// Include GLPI
include (GLPI_ROOT . '/inc/includes.php');

// Clean any previous output and set headers
ob_end_clean();
header('Content-Type: application/json; charset=UTF-8');

// Check if user is logged in
try {
    Session::checkLoginUser();
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Sessão inválida ou expirada'
    ]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

try {

    switch ($action) {
        case 'save':
            // Log the request
            error_log('flowBPMN save request: itemtype=' . ($input['itemtype'] ?? 'null') .
                     ', items_id=' . ($input['items_id'] ?? 'null'));

            // Validate inputs
            if (empty($input['itemtype']) || empty($input['items_id']) || empty($input['bpmn_xml'])) {
                throw new Exception('Parâmetros obrigatórios ausentes');
            }

            // Validate item type
            if (!in_array($input['itemtype'], ['Ticket', 'Problem', 'Change'])) {
                throw new Exception('Tipo de item inválido');
            }

            // Check permissions
            if (class_exists('PluginFlowbpmnProfile')) {
                if (!PluginFlowbpmnProfile::canEditFlow($input['itemtype'])) {
                    throw new Exception('Permissão negada');
                }
            } else {
                error_log('flowBPMN: PluginFlowbpmnProfile class not found, skipping permission check');
            }

            // Save flow
            $flow = new PluginFlowbpmnFlow();
            $id = $flow->saveFlow(
                $input['itemtype'],
                (int)$input['items_id'],
                $input['bpmn_xml'],
                $input['svg_content'] ?? '',
                $input['name'] ?? '',
                $input['png_data'] ?? ''
            );

            if ($id) {
                error_log('flowBPMN: Flow saved successfully with ID: ' . $id);
                echo json_encode([
                    'success' => true,
                    'id' => $id,
                    'message' => 'Fluxo flowBPMN salvo com sucesso!'
                ]);
            } else {
                error_log('flowBPMN: Failed to save flow');
                throw new Exception('Falha ao salvar fluxo');
            }
            break;
            
        case 'load':
            // Validate inputs
            if (empty($input['itemtype']) || empty($input['items_id'])) {
                throw new Exception('Parâmetros obrigatórios ausentes');
            }
            
            // Check permissions
            if (!PluginFlowbpmnProfile::canViewFlow($input['itemtype'])) {
                throw new Exception('Permissão negada');
            }
            
            // Load flow
            $flow = new PluginFlowbpmnFlow();
            $data = $flow->getForItem($input['itemtype'], (int)$input['items_id']);
            
            echo json_encode(['success' => true, 'data' => $data]);
            break;
            
        case 'versions':
            // Get versions list
            if (empty($input['itemtype']) || empty($input['items_id'])) {
                throw new Exception('Parâmetros obrigatórios ausentes');
            }
            
            $flow = new PluginFlowbpmnFlow();
            $versions = $flow->getHistory($input['itemtype'], (int)$input['items_id']);
            
            echo json_encode(['success' => true, 'versions' => $versions]);
            break;
            
        case 'delete':
            // Validate inputs
            if (empty($input['id'])) {
                throw new Exception('Parâmetros obrigatórios ausentes');
            }
            
            // Check permissions
            if (!Session::haveRight('plugin_flowbpmn', DELETE)) {
                throw new Exception('Permissão negada');
            }
            
            // Delete flow
            $flow = new PluginFlowbpmnFlow();
            if ($flow->deleteFlow((int)$input['id'])) {
                echo json_encode(['success' => true, 'message' => 'Fluxo excluído']);
            } else {
                throw new Exception('Falha ao excluir fluxo');
            }
            break;
            
        default:
            throw new Exception('Ação inválida: ' . $action);
    }

} catch (Exception $e) {
    error_log('flowBPMN ERROR: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => [
            'file' => basename($e->getFile()),
            'line' => $e->getLine()
        ]
    ]);
}

// Ensure clean exit
exit;
