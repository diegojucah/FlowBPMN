<?php
declare(strict_types=1);
/**
 * -------------------------------------------------------------------------
 * FlowBPMN Plugin for GLPI - Native DB Flow Handler v3.0
 * -------------------------------------------------------------------------
 */

// Bootstrap GLPI manually (since this file is called directly, not through front controller)
$glpi_root = dirname(__DIR__, 3);

// Include autoloader
require_once $glpi_root . '/vendor/autoload.php';

// Initialize GLPI Kernel
use Glpi\Kernel\Kernel;
use Glpi\Application\Environment;
use Glpi\DBAL\QueryExpression;

$kernel = new Kernel(Environment::PRODUCTION->value, false);
$kernel->boot();

// Load GLPI configuration
global $CFG_GLPI, $DB;

// Get user_id from session
$user_id = Session::getLoginUserID();

// Set headers
header("Content-Type: application/json; charset=UTF-8");

if (!$user_id) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Usuário não autenticado']));
}

// Get input
$rawInput = file_get_contents("php://input");
$input = json_decode($rawInput, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Invalid JSON']));
}

$action = $input['action'] ?? '';

try {
    switch ($action) {
        case 'save':
            $itemtype = $input['itemtype'] ?? '';
            $items_id = (int)($input['items_id'] ?? 0);
            $bpmn_xml = $input['bpmn_xml'] ?? '';
            $name = $input['name'] ?? 'FlowBPMN Diagram';
            $svg_content = $input['svg_content'] ?? '';
            
            if (empty($itemtype) || $items_id <= 0 || empty($bpmn_xml)) {
                throw new Exception('Missing required parameters');
            }
            
            // Validate itemtype
            $validTypes = ['Ticket', 'Problem', 'Change'];
            if (!in_array($itemtype, $validTypes)) {
                throw new Exception('Invalid item type');
            }
            
            // Normalize itemtype to ensure proper casing (critical for DB lookups)
            $itemtype = ucfirst(strtolower($itemtype));
            if ($itemtype === 'Ticket') $itemtype = 'Ticket';
            elseif ($itemtype === 'Problem') $itemtype = 'Problem';
            elseif ($itemtype === 'Change') $itemtype = 'Change';
            
            // Check permissions
            if (!PluginFlowbpmnProfile::canEditFlow($itemtype)) {
                http_response_code(403);
                throw new Exception('Você não tem permissão para editar diagramas BPMN');
            }
            
            // Use the PluginFlowbpmnFlow class which has the working savePNGAsDocument() method
            $flow = new PluginFlowbpmnFlow();
            $png_data = $input['png_data'] ?? '';
            
            $flow_id = $flow->saveFlow($itemtype, $items_id, $bpmn_xml, $svg_content, $name, $png_data);
            
            $debug_file = __DIR__ . '/../debug_versions.txt';
            file_put_contents($debug_file, "\n" . date('Y-m-d H:i:s') . " - saveFlow returned flow_id=$flow_id for $itemtype #$items_id\n", FILE_APPEND);
            
            if (!$flow_id) {
                throw new Exception('Falha ao salvar diagrama');
            }
            
            // Create version manually (saveFlow() version creation may not work reliably)
            $debug_file = __DIR__ . '/../debug_versions.txt';
            file_put_contents($debug_file, date('Y-m-d H:i:s') . " - Creating version for flow_id=$flow_id\n", FILE_APPEND);
            
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
            
            file_put_contents($debug_file, "Max version: $max_v, Next version: $next_v\n", FILE_APPEND);
            
            $version_id = $DB->insert('glpi_plugin_flowbpmn_versions', [
                'plugin_flowbpmn_flows_id' => $flow_id,
                'version_number'           => $next_v,
                'name'                     => $name,
                'comment'                  => "Versão $next_v (Auto-save)",
                'bpmn_xml'                 => $bpmn_xml,
                'svg_content'              => $svg_content,
                'users_id'                 => $user_id,
                'date_creation'            => $_SESSION['glpi_currenttime'] ?? date('Y-m-d H:i:s')
            ]);
            
            file_put_contents($debug_file, "Version insert result: version_id=$version_id\n", FILE_APPEND);
            
            if (!$version_id) {
                file_put_contents($debug_file, "ERROR: Failed to create version!\n", FILE_APPEND);
            }
            
            // Add entry to timeline
            $log_message = "Diagrama BPMN atualizado: Versão $next_v ($name)";
            Log::history(
                $items_id,
                $itemtype,
                [0, '', $log_message],
                '',
                Log::HISTORY_LOG_SIMPLE_MESSAGE
            );
            
            file_put_contents($debug_file, "Timeline entry added\n", FILE_APPEND);
            
            echo json_encode([
                'success' => true,
                'id' => $flow_id,
                'version_created' => $next_v,
                'message' => 'Diagrama salvo (v' . $next_v . ')'
            ]);
            break;
            
        case 'load':
            $itemtype = $input['itemtype'] ?? '';
            $items_id = (int)($input['items_id'] ?? 0);
            
            if (empty($itemtype) || $items_id <= 0) {
                throw new Exception('Missing required parameters');
            }
            
            $iterator = $DB->request([
                'FROM'  => 'glpi_plugin_flowbpmn_flows',
                'WHERE' => [
                    'itemtype' => $itemtype,
                    'items_id' => $items_id
                ],
                'ORDER' => 'id DESC',
                'LIMIT' => 1
            ]);
            
            $data = count($iterator) ? $iterator->current() : null;
            
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'delete_version':
            $version_id = (int)($input['version_id'] ?? 0);
            if ($version_id <= 0) {
                throw new Exception('Invalid Version ID');
            }
            
            // Get version info to check itemtype
            $iterator = $DB->request([
                'SELECT' => ['f.itemtype', 'f.items_id', 'v.version_number', 'v.name'],
                'FROM'   => 'glpi_plugin_flowbpmn_versions AS v',
                'INNER JOIN' => [
                    'glpi_plugin_flowbpmn_flows AS f' => [
                        'ON' => ['v' => 'plugin_flowbpmn_flows_id', 'f' => 'id']
                    ]
                ],
                'WHERE'  => ['v.id' => $version_id],
                'LIMIT'  => 1
            ]);
            
            if (!count($iterator)) {
                throw new Exception('Versão não encontrada');
            }
            
            $row = $iterator->current();
            $itemtype = $row['itemtype'];
            $items_id = (int)$row['items_id'];
            $version_num = $row['version_number'];
            $version_name = $row['name'];
            
            // Check permissions
            if (!PluginFlowbpmnProfile::canDeleteFlow($itemtype)) {
                http_response_code(403);
                throw new Exception('Você não tem permissão para excluir versões');
            }
            
            $DB->delete('glpi_plugin_flowbpmn_versions', ['id' => $version_id]);
            
            // Log deletion
            $log_message = "Versão do Diagrama BPMN excluída: Versão $version_num ($version_name)";
            Log::history(
                $items_id,
                $itemtype,
                [0, '', $log_message],
                '',
                Log::HISTORY_LOG_SIMPLE_MESSAGE
            );

            echo json_encode(['success' => true, 'message' => 'Versão excluída']);
            break;
            
        default:
            http_response_code(400);
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    if (http_response_code() === 200) {
        http_response_code(400);
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
