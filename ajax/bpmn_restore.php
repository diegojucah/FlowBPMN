<?php
/**
 * -------------------------------------------------------------------------
 * flowBPMN Plugin for GLPI - Direct SQL Restore Handler
 * -------------------------------------------------------------------------
 */

header("Content-Type: application/json; charset=UTF-8");

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
$input = json_decode($rawInput, true);

try {
    $flow_id = (int)($input['flow_id'] ?? 0);
    $version_id = (int)($input['version_id'] ?? 0);

    if (!$flow_id || !$version_id) {
        throw new Exception('Invalid parameters');
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

    // 2. Backup Current Flow implementation? (Optional - logic implies we just overwrite current state)
    // The previous state is NOT automatically saved here unless we explicitly do it.
    // However, the user request "para cada salvamento deve ser considerado um versionamento" implies SAVE action triggers versioning.
    // RESTORE is effectively a "Revert". If the user saves after restore, THAT creates a new version.
    // So we just update the current flow state.

    // 3. Update Flow
    $stmt = $db->prepare("UPDATE glpi_plugin_flowbpmn_flows SET bpmn_xml = ?, date_mod = NOW() WHERE id = ?");
    $stmt->bind_param("si", $versionData['bpmn_xml'], $flow_id);
    
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
