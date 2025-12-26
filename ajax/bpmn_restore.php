<?php
/**
 * -------------------------------------------------------------------------
 * flowBPMN Plugin for GLPI - Direct SQL Restore Handler
 * -------------------------------------------------------------------------
 */

// 1. Bootstrap GLPI manually - MATCHING ajax/flow.php logic
$glpi_root = dirname(__DIR__, 3);

// Define GLPI_ROOT if not defined (required for include checks)
if (!defined('GLPI_ROOT')) {
    define('GLPI_ROOT', $glpi_root);
}

// Include autoloader
require_once $glpi_root . '/vendor/autoload.php';

// Initialize GLPI Kernel
use Glpi\Kernel\Kernel;
use Glpi\Application\Environment;

$kernel = new Kernel(Environment::PRODUCTION->value, false);
$kernel->boot();

// Load GLPI configuration
global $CFG_GLPI, $DB;

header("Content-Type: application/json; charset=UTF-8");

// Check authentication
$user_id = Session::getLoginUserID();
if (!$user_id) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Usuário não autenticado']));
}

// Database Connection
$DB_HOST = getenv('GLPI_DB_HOST') ?: 'mariadb';
$DB_NAME = getenv('GLPI_DB_NAME') ?: 'glpi';
$DB_USER = getenv('GLPI_DB_USER') ?: 'glpi';
$DB_PASS = getenv('GLPI_DB_PASSWORD') ?: 'glpi';

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
    
    // Get flow info
    $stmt = $db->prepare("SELECT itemtype, items_id FROM glpi_plugin_flowbpmn_flows WHERE id = ? LIMIT 1");
    if (!$stmt) throw new Exception("Prepare failed: " . $db->error);
    
    $stmt->bind_param("i", $flow_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $flowData = $res->fetch_assoc();
    $stmt->close();
    
    if (!$flowData) {
        throw new Exception('Flow não encontrado');
    }
    
    $itemtype = $flowData['itemtype'];
    $items_id = (int)$flowData['items_id'];
    
    // Check Permissions
    // Safely load Profile class if not autoloaded
    if (!class_exists('PluginFlowbpmnProfile')) {
        $inc_dir = __DIR__ . '/../inc';
        if (file_exists($inc_dir . '/profile.class.php')) {
            include_once $inc_dir . '/profile.class.php';
        }
    }

    if (!class_exists('PluginFlowbpmnProfile') || !PluginFlowbpmnProfile::canRestoreFlow($itemtype)) {
        http_response_code(403);
        throw new Exception('Você não tem permissão para restaurar versões');
    }

    // 1. Get Target Version Data
    $stmt = $db->prepare("SELECT bpmn_xml, name, svg_content FROM glpi_plugin_flowbpmn_versions WHERE id = ? AND plugin_flowbpmn_flows_id = ? LIMIT 1");
    $stmt->bind_param("ii", $version_id, $flow_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $versionData = $res->fetch_assoc();
    $stmt->close();

    if (!$versionData) {
        throw new Exception('Version not found');
    }

    // Debug Log Collector
    $debug_info = [];
    function add_debug($msg) {
        global $debug_info;
        $debug_info[] = $msg;
        error_log($msg);
    }

    add_debug("Restore initiated for Flow: $flow_id, Version: $version_id");

    // ... (existing code)

    // 2. AUTO-SAVE Current State
    // Get current flow XML
    $stmt = $db->prepare("SELECT bpmn_xml, name, users_id FROM glpi_plugin_flowbpmn_flows WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $flow_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $currentFlow = $res->fetch_assoc();
    $stmt->close();

    if ($currentFlow && !empty($currentFlow['bpmn_xml'])) {
        // Get max version
        $res = $db->query("SELECT MAX(version_number) as max_v FROM glpi_plugin_flowbpmn_versions WHERE plugin_flowbpmn_flows_id = $flow_id");
        $max_v = 0;
        if ($res && $row = $res->fetch_assoc()) {
            $max_v = (int)$row['max_v'];
        }
        $next_v = $max_v + 1;

        $auto_name = $currentFlow['name'];
        $auto_comment = "Backup automático (antes da restauração)";
        $current_user_id = $user_id;

        add_debug("FlowBPMN Auto-Save: Preparing to insert version $next_v for flow $flow_id");

        $stmt = $db->prepare("INSERT INTO glpi_plugin_flowbpmn_versions 
                            (plugin_flowbpmn_flows_id, version_number, name, comment, bpmn_xml, svg_content, users_id, date_creation) 
                            VALUES (?, ?, ?, ?, ?, '0', ?, NOW())");
        
        if (!$stmt) {
             add_debug("FlowBPMN Auto-Save Prepare Failed: " . $db->error);
        } else {
            $stmt->bind_param("iisssi", $flow_id, $next_v, $auto_name, $auto_comment, $currentFlow['bpmn_xml'], $current_user_id);
            if (!$stmt->execute()) {
                add_debug("FlowBPMN Auto-Save Execute Failed: " . $stmt->error);
            } else {
                $new_version_id = $db->insert_id;
                add_debug("FlowBPMN Auto-Save Success: Created version " . $new_version_id);
                
                // Log Auto-Save
                $log_message = "Backup automático criado: Versão $next_v (antes da restauração)";
                Log::history(
                    $items_id, 
                    $itemtype, 
                    [0, '', $log_message], 
                    '', 
                    Log::HISTORY_LOG_SIMPLE_MESSAGE
                );
            }
            $stmt->close();
        }
    } else {
        add_debug("FlowBPMN Auto-Save Skipped: Current flow empty or not found");
    }

    // 3. Restore (Update Flow)
    $stmt = $db->prepare("UPDATE glpi_plugin_flowbpmn_flows SET bpmn_xml = ?, users_id = ?, date_mod = NOW() WHERE id = ?");
    $stmt->bind_param("sii", $versionData['bpmn_xml'], $user_id, $flow_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update flow: ' . $db->error);
    }
    $stmt->close();

    // Log Restore
    $restored_version_num = (int)$versionData['version_number']; // Need to fetch this?
    $restore_name = $versionData['name'];
    $log_message = "Diagrama BPMN restaurado: '$restore_name'"; // Simple message
    
    Log::history(
        $items_id, 
        $itemtype, 
        [0, '', $log_message], 
        '', 
        Log::HISTORY_LOG_SIMPLE_MESSAGE
    );

    echo json_encode([
        'success' => true, 
        'message' => 'Versão restaurada com sucesso (Backup criado).',
        'bpmn_xml' => $versionData['bpmn_xml'],
        'debug_info' => $debug_info
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$db->close();
?>
