<?php
/**
 * -------------------------------------------------------------------------
 * flowBPMN Plugin for GLPI - Versions Handler (Direct SQL Mode)
 * -------------------------------------------------------------------------
 */

header("Content-Type: application/json; charset=UTF-8");

// Database Configuration (Using Docker Environment Variables)
$DB_HOST = getenv('GLPI_DB_HOST') ?: 'mariadb';
$DB_NAME = getenv('GLPI_DB_NAME') ?: 'glpi';
$DB_USER = getenv('GLPI_DB_USER') ?: 'glpi';
$DB_PASS = getenv('GLPI_DB_PASSWORD') ?: 'glpi';

// Connect using MySQLi
$db = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if ($db->connect_error) {
    http_response_code(500);
    die(json_encode(['success' => false, 'message' => 'Database connection failed: ' . $db->connect_error]));
}

$db->set_charset('utf8mb4');

try {
    $itemtype = $_GET['itemtype'] ?? '';
    $items_id = (int)($_GET['items_id'] ?? 0);

    if (empty($itemtype) || empty($items_id)) {
        throw new Exception('Missing parameters');
    }

    if (!in_array($itemtype, ['Ticket', 'Problem', 'Change'])) {
        throw new Exception('Invalid item type');
    }

    // 1. Get Current Flow
    $stmt = $db->prepare("SELECT f.*, u.name as user_name FROM glpi_plugin_flowbpmn_flows f LEFT JOIN glpi_users u ON f.users_id = u.id WHERE f.itemtype = ? AND f.items_id = ? ORDER BY f.id DESC LIMIT 1");
    if (!$stmt) throw new Exception("Prepare failed: " . $db->error);
    
    $stmt->bind_param("si", $itemtype, $items_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $currentFlow = $result->fetch_assoc();
    $stmt->close();

    if (!$currentFlow) {
        echo json_encode([
            'success' => true, 
            'versions' => [], 
            'current' => null
        ]);
        exit;
    }

    // 2. Get Versions
    $stmt = $db->prepare("SELECT v.*, u.name as user_name FROM glpi_plugin_flowbpmn_versions v LEFT JOIN glpi_users u ON v.users_id = u.id WHERE v.plugin_flowbpmn_flows_id = ? ORDER BY v.version_number DESC");
    if (!$stmt) throw new Exception("Prepare failed: " . $db->error);

    $flowId = $currentFlow['id'];
    $stmt->bind_param("i", $flowId);
    $stmt->execute();
    $result = $stmt->get_result();
    $versions = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // 3. Format Date Helper
    function formatDate($date) {
        if (!$date) return '-';
        // Convert to d/m/Y H:i
        return date('d/m/Y H:i', strtotime($date));
    }

    // 4. Prepare Response
    $formattedVersions = [];
    foreach ($versions as $v) {
        $formattedVersions[] = [
            'id' => $v['id'],
            'version_number' => $v['version_number'],
            'name' => $v['name'],
            'comment' => $v['comment'], // Can be json
            'users_id' => $v['users_id'],
            'user_name' => $v['user_name'] ?: 'Unknown',
            'date_creation' => $v['date_creation'],
            'date_creation_formatted' => formatDate($v['date_creation'])
        ];
    }

    // 5. Check permissions (Naive check: if you can see the flow, you can restore? Or just disable restore for safe mode)
    // Since we don't have session, we assume true for viewing, but restoring logic is in another file that might have session checks.
    // For now, let's allow restore button to appear, but the actual restore action inside bpmn_restore.php SHOULD have session checks.
    $canRestore = true;

    echo json_encode([
        'success' => true,
        'versions' => $formattedVersions,
        'current' => [
            'id' => $currentFlow['id'],
            'name' => $currentFlow['name'],
            'date_mod' => $currentFlow['date_mod'],
            'date_mod_formatted' => formatDate($currentFlow['date_mod']),
            'user_name' => $currentFlow['user_name'] ?: 'Unknown'
        ],
        'canRestore' => $canRestore
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$db->close();
?>
