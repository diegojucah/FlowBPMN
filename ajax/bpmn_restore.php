<?php
declare(strict_types=1);
/**
 * -------------------------------------------------------------------------
 * FlowBPMN Plugin for GLPI - Native DB Restore Handler v3.0
 * -------------------------------------------------------------------------
 */

// Bootstrap GLPI
$glpi_root = dirname(__DIR__, 3);
require_once $glpi_root . '/vendor/autoload.php';

use Glpi\Kernel\Kernel;
use Glpi\Application\Environment;
use Glpi\DBAL\QueryExpression;

$kernel = new Kernel(Environment::PRODUCTION->value, false);
$kernel->boot();

global $CFG_GLPI, $DB;

header("Content-Type: application/json; charset=UTF-8");

$user_id = Session::getLoginUserID();
if (!$user_id) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Usuário não autenticado']));
}

// Validate CSRF token (security fix)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_SERVER['HTTP_X_GLPI_CSRF_TOKEN'] ?? '';
    
    if (empty($csrfToken) || !Session::validateCSRF(['_glpi_csrf_token' => $csrfToken])) {
        http_response_code(403);
        die(json_encode(['success' => false, 'message' => 'Token CSRF inválido']));
    }
}

// Get Input
$rawInput = file_get_contents("php://input");
$input = json_decode($rawInput, true);

try {
    $debug_file = __DIR__ . '/../debug_restore.txt';
    file_put_contents($debug_file, "\n" . date('Y-m-d H:i:s') . " - Restore request received\n", FILE_APPEND);
    file_put_contents($debug_file, "Input: " . json_encode($input) . "\n", FILE_APPEND);
    
    $flow_id = (int)($input['flow_id'] ?? 0);
    $version_id = (int)($input['version_id'] ?? 0);
    
    file_put_contents($debug_file, "Parsed: flow_id=$flow_id, version_id=$version_id\n", FILE_APPEND);
    
    if (!$flow_id || !$version_id) {
        file_put_contents($debug_file, "ERROR: Invalid parameters\n", FILE_APPEND);
        throw new Exception('Invalid parameters');
    }
    
    // Get flow info
    $iterator = $DB->request([
        'SELECT' => ['itemtype', 'items_id', 'bpmn_xml', 'name'],
        'FROM'   => 'glpi_plugin_flowbpmn_flows',
        'WHERE'  => ['id' => $flow_id],
        'LIMIT'  => 1
    ]);
    
    if (!count($iterator)) {
        throw new Exception('Flow não encontrado');
    }
    
    $flowData = $iterator->current();
    $itemtype = $flowData['itemtype'];
    $items_id = (int)$flowData['items_id'];
    
    // Check Permissions
    if (!PluginFlowbpmnProfile::canRestoreFlow($itemtype)) {
        http_response_code(403);
        throw new Exception('Você não tem permissão para restaurar versões');
    }

    // 1. Get Target Version Data
    $iterator = $DB->request([
        'SELECT' => ['bpmn_xml', 'name', 'svg_content', 'version_number'],
        'FROM'   => 'glpi_plugin_flowbpmn_versions',
        'WHERE'  => [
            'id' => $version_id,
            'plugin_flowbpmn_flows_id' => $flow_id
        ],
        'LIMIT'  => 1
    ]);

    if (!count($iterator)) {
        throw new Exception('Version not found');
    }

    $versionData = $iterator->current();

    // 2. AUTO-SAVE Current State (Backup before restore)
    if (!empty($flowData['bpmn_xml'])) {
        // Get max version number
        $iterator = $DB->request([
            'SELECT' => [new QueryExpression('MAX(version_number) as max_v')],
            'FROM'   => 'glpi_plugin_flowbpmn_versions',
            'WHERE'  => ['plugin_flowbpmn_flows_id' => $flow_id]
        ]);
        $max_v = 0;
        if (count($iterator)) {
            $row = $iterator->current();
            $max_v = (int)($row['max_v'] ?? 0);
        }
        $next_v = $max_v + 1;

        // Insert backup version
        $DB->insert('glpi_plugin_flowbpmn_versions', [
            'plugin_flowbpmn_flows_id' => $flow_id,
            'version_number'           => $next_v,
            'name'                     => $flowData['name'],
            'comment'                  => 'Backup automático (antes da restauração)',
            'bpmn_xml'                 => $flowData['bpmn_xml'],
            'svg_content'              => '',
            'users_id'                 => $user_id,
            'date_creation'            => $_SESSION['glpi_currenttime'] ?? date('Y-m-d H:i:s')
        ]);
        
        // Log Auto-Save
        Log::history(
            $items_id, 
            $itemtype, 
            [0, '', "Backup automático criado: Versão $next_v (antes da restauração)"], 
            '', 
            Log::HISTORY_LOG_SIMPLE_MESSAGE
        );
    }

    // 3. Restore (Update Flow)
    $DB->update('glpi_plugin_flowbpmn_flows', [
        'bpmn_xml'    => $versionData['bpmn_xml'],
        'svg_content' => $versionData['svg_content'] ?? '',
        'users_id'    => $user_id,
        'date_mod'    => $_SESSION['glpi_currenttime'] ?? date('Y-m-d H:i:s')
    ], [
        'id' => $flow_id
    ]);

    // Log Restore
    $restore_name = $versionData['name'];
    $version_num = $versionData['version_number'];
    Log::history(
        $items_id, 
        $itemtype, 
        [0, '', "Diagrama BPMN restaurado: Versão $version_num ($restore_name)"], 
        '', 
        Log::HISTORY_LOG_SIMPLE_MESSAGE
    );

    file_put_contents($debug_file, "Restore successful, returning XML\n", FILE_APPEND);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Versão restaurada com sucesso (Backup criado).',
        'bpmn_xml' => $versionData['bpmn_xml']
    ]);

} catch (Exception $e) {
    $debug_file = __DIR__ . '/../debug_restore.txt';
    file_put_contents($debug_file, "EXCEPTION: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
