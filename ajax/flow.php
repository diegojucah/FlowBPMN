<?php
/**
 * -------------------------------------------------------------------------
 * flowBPMN Plugin for GLPI - AJAX Flow Handler
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2024 by KactuX
 * @license   GPLv3 https://www.gnu.org/licenses/gpl-3.0.html
 * -------------------------------------------------------------------------
 */

// Start output buffering to prevent any unwanted output
ob_start();

// Define GLPI root for proper includes
if (!defined('GLPI_ROOT')) {
    define('GLPI_ROOT', dirname(__DIR__, 3));
}

include (GLPI_ROOT . '/inc/includes.php');

// Clean any previous output
ob_end_clean();

// Capture fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if (!headers_sent()) {
            header("Content-Type: application/json; charset=UTF-8");
            http_response_code(500);
        }
        echo json_encode([
            'success' => false,
            'message' => 'Fatal server error',
            'error' => $error['message'],
            'file' => basename($error['file']),
            'line' => $error['line']
        ]);
    }
});

// Set JSON header
header("Content-Type: application/json; charset=UTF-8");

// Check session
Session::checkLoginUser();

// Get input
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Invalid JSON']));
}

$action = $input['action'] ?? '';

try {
    switch ($action) {
        case 'save':
            // Validate required parameters
            if (empty($input['itemtype']) || empty($input['items_id']) || empty($input['bpmn_xml'])) {
                throw new Exception('Missing required parameters');
            }

            // Validate itemtype
            if (!in_array($input['itemtype'], ['Ticket', 'Problem', 'Change'])) {
                throw new Exception('Invalid item type');
            }

            // Check permissions
            if (!PluginFlowbpmnProfile::canEditFlow($input['itemtype'])) {
                http_response_code(403);
                throw new Exception('Permission denied');
            }

            // Sanitize BPMN XML
            $bpmn_xml = PluginFlowbpmnHelper::sanitizeBpmnXml($input['bpmn_xml']);

            // Sanitize SVG
            $svg_content = '';
            if (!empty($input['svg_content'])) {
                try {
                    $svg_content = PluginFlowbpmnHelper::sanitizeSvg($input['svg_content']);
                } catch (Exception $e) {
                    // Continue without SVG
                }
            }

            // Create flow instance
            $flow = new PluginFlowbpmnFlow();

            // Save flow
            $id = $flow->saveFlow(
                $input['itemtype'],
                (int)$input['items_id'],
                $bpmn_xml,
                $svg_content,
                $input['name'] ?? '',
                $input['png_data'] ?? ''
            );

            if ($id) {
                echo json_encode([
                    'success' => true,
                    'id' => $id,
                    'message' => __('BPMN Flow saved successfully!', 'flowbpmn')
                ]);
            } else {
                throw new Exception('Failed to save flow to database');
            }
            break;

        case 'load':
            // Validate parameters
            if (empty($input['itemtype']) || empty($input['items_id'])) {
                throw new Exception('Missing required parameters');
            }

            // Check permissions
            if (!PluginFlowbpmnProfile::canViewFlow($input['itemtype'])) {
                http_response_code(403);
                throw new Exception('Permission denied');
            }

            // Load flow
            $flow = new PluginFlowbpmnFlow();
            $data = $flow->getForItem($input['itemtype'], (int)$input['items_id']);

            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'versions':
            // Validate parameters
            if (empty($input['itemtype']) || empty($input['items_id'])) {
                throw new Exception('Missing required parameters');
            }

            // Check permissions
            if (!PluginFlowbpmnProfile::canViewFlow($input['itemtype'])) {
                http_response_code(403);
                throw new Exception('Permission denied');
            }

            // Get versions
            $flow = new PluginFlowbpmnFlow();
            $versions = $flow->getHistory($input['itemtype'], (int)$input['items_id']);

            echo json_encode(['success' => true, 'versions' => $versions]);
            break;

        default:
            http_response_code(400);
            throw new Exception('Invalid action: ' . $action);
    }

} catch (Exception $e) {
    // Return appropriate status code if not already set
    if (http_response_code() === 200) {
        http_response_code(400);
    }

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
