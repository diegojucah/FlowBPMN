<?php
/**
 * -------------------------------------------------------------------------
 * flowBPMN Plugin for GLPI - Direct SQL Restore Handler
 * -------------------------------------------------------------------------
 */

// Bootstrap GLPI
define('GLPI_ROOT', dirname(dirname(dirname(__DIR__))));
include (GLPI_ROOT . "/inc/includes.php");

header("Content-Type: application/json; charset=UTF-8");

// Check authentication
Session::checkLoginUser();

// Get authenticated user ID
$user_id = Session::getLoginUserID();

if (!$user_id) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Usuário não autenticado']));
}

// Database Configuration
$DB_HOST = getenv('GLPI_DB_HOST') ?: 'mariadb';
$DB_NAME = getenv('GLPI_DB_NAME') ?: 'glpi';
$DB_USER = getenv('GLPI_DB_USER') ?: 'glpi';
$DB_PASS = getenv('GLPI_DB_PASSWORD') ?: 'glpi';

// Connect
$db = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if ($db->connect_error) {
    http_response_code(500);
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

$db->set_charset('utf8mb4');

// Get Input
$rawInput = file_get_contents("php://input");
error_log("flowBPMN restore input: " . $rawInput);
$input = json_decode($rawInput, true);

try {
    $flow_id = (int)($input['flow_id'] ?? 0);
    $version_id = (int)($input['version_id'] ?? 0);

    if (!$flow_id || !$version_id) {
        throw new Exception('Invalid parameters');
    }
    
    // Get flow info to check itemtype and permissions
    $stmt = $db->prepare("SELECT itemtype FROM glpi_plugin_flowbpmn_flows WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $flow_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $flowData = $res->fetch_assoc();
    $stmt->close();
    
    if (!$flowData) {
        throw new Exception('Flow não encontrado');
    }
    
    $itemtype = $flowData['itemtype'];
    
    // Check restore permissions
    if (!PluginFlowbpmnProfile::canRestoreFlow($itemtype)) {
        http_response_code(403);
        throw new Exception('Você não tem permissão para restaurar versões');
    }

    // 1. Get Version Data
    $stmt = $db->prepare("SELECT bpmn_xml, name FROM glpi_plugin_flowbpmn_versions WHERE id = ? AND plugin_flowbpmn_flows_id = ? LIMIT 1");
    $stmt->bind_param("ii", $version_id, $flow_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $versionData = $res->fetch_assoc();
    $stmt->close();

    if (!$versionData) {
        throw new Exception('Version not found');
    }

    // 2. Update Flow with restored content and log the user who restored it
    $stmt = $db->prepare("UPDATE glpi_plugin_flowbpmn_flows SET bpmn_xml = ?, users_id = ?, date_mod = NOW() WHERE id = ?");
    $stmt->bind_param("sii", $versionData['bpmn_xml'], $user_id, $flow_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update flow: ' . $db->error);
    }
    $stmt->close();

    echo json_encode([
        'success' => true, 
        'message' => 'Restored successfully',
        'bpmn_xml' => $versionData['bpmn_xml']
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$db->close();
?>
