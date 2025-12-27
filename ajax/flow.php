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
            
            // Get proper entity ID from the actual item table
            $entities_id = 0;
            $tableMap = [
                'Ticket' => 'glpi_tickets',
                'Problem' => 'glpi_problems',
                'Change' => 'glpi_changes'
            ];
            $itemTable = $tableMap[$itemtype] ?? 'glpi_tickets';
            
            $iterator = $DB->request([
                'SELECT' => 'entities_id',
                'FROM'   => $itemTable,
                'WHERE'  => ['id' => $items_id],
                'LIMIT'  => 1
            ]);
            if (count($iterator)) {
                $row = $iterator->current();
                $entities_id = (int)$row['entities_id'];
            }
            
            // 1. Check for Existing Flow (Avoid Duplicates)
            $flow_id = 0;
            $iterator = $DB->request([
                'SELECT' => 'id',
                'FROM'   => 'glpi_plugin_flowbpmn_flows',
                'WHERE'  => [
                    'itemtype' => $itemtype,
                    'items_id' => $items_id
                ],
                'ORDER'  => 'id DESC',
                'LIMIT'  => 1
            ]);
            if (count($iterator)) {
                $row = $iterator->current();
                $flow_id = (int)$row['id'];
            }
            
            if ($flow_id > 0) {
                // UPDATE existing flow
                $success = $DB->update('glpi_plugin_flowbpmn_flows', [
                    'bpmn_xml'    => $bpmn_xml,
                    'svg_content' => $svg_content,
                    'name'        => $name,
                    'users_id'    => $user_id,
                    'date_mod'    => $_SESSION['glpi_currenttime'] ?? date('Y-m-d H:i:s')
                ], [
                    'id' => $flow_id
                ]);
                
                if (!$success) {
                    throw new Exception('Falha ao atualizar diagrama existente');
                }
            } else {
                // INSERT new flow
                $flow_id = $DB->insert('glpi_plugin_flowbpmn_flows', [
                    'itemtype'      => $itemtype,
                    'items_id'      => $items_id,
                    'entities_id'   => $entities_id,
                    'bpmn_xml'      => $bpmn_xml,
                    'svg_content'   => $svg_content,
                    'name'          => $name,
                    'users_id'      => $user_id,
                    'date_creation' => $_SESSION['glpi_currenttime'] ?? date('Y-m-d H:i:s'),
                    'date_mod'      => $_SESSION['glpi_currenttime'] ?? date('Y-m-d H:i:s')
                ]);
                
                if (!$flow_id) {
                    throw new Exception('Falha ao criar novo diagrama');
                }
            }
            
            if (!$flow_id) throw new Exception("Failed to manage Flow ID");

            // 2. Auto-create Version (History)
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
            
            $DB->insert('glpi_plugin_flowbpmn_versions', [
                'plugin_flowbpmn_flows_id' => $flow_id,
                'version_number'           => $next_v,
                'name'                     => $name,
                'comment'                  => "Versão $next_v (Auto-save)",
                'bpmn_xml'                 => $bpmn_xml,
                'svg_content'              => $svg_content,
                'users_id'                 => $user_id,
                'date_creation'            => $_SESSION['glpi_currenttime'] ?? date('Y-m-d H:i:s')
            ]);

            // 3. Add entry to timeline using Log::history()
            $log_message = "Diagrama BPMN atualizado: Versão $next_v ($name)";
            Log::history(
                $items_id,
                $itemtype,
                [0, '', $log_message],
                '',
                Log::HISTORY_LOG_SIMPLE_MESSAGE
            );

            // 4. Process PNG
            $document_id = null;
            $png_message = '';
            
            if (!empty($input['png_data'])) {
                $result = createGLPIDocumentNative($input['png_data'], $itemtype, $items_id, $entities_id, $name, $user_id);
                $document_id = $result['document_id'];
                $png_message = $result['message'];
            }
            
            echo json_encode([
                'success' => true,
                'id' => $flow_id,
                'version_created' => $next_v,
                'document_id' => $document_id,
                'message' => 'Diagrama salvo (v' . $next_v . ')' . ($png_message ? ' + PNG anexado.' : '')
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

/**
 * Create GLPI Document using native DB methods
 */
function createGLPIDocumentNative(string $png_data, string $itemtype, int $items_id, int $entities_id, string $name, int $user_id): array {
    global $DB;
    
    try {
        $png_data = preg_replace('/^data:image\/png;base64,/', '', $png_data);
        $png_binary = base64_decode($png_data);
        
        if (!$png_binary || strlen($png_binary) < 100) {
            return ['document_id' => null, 'message' => ''];
        }
        
        $hash = sha1($png_binary);
        $subdir1 = 'PNG';
        $subdir2 = substr($hash, 0, 2);
        $filename = $hash . '.PNG';
        
        $base_dir = GLPI_VAR_DIR . '/_uploads';
        if (!is_dir($base_dir)) {
            $base_dir = '/var/glpi/files';
        }
        $full_subdir = $base_dir . '/' . $subdir1 . '/' . $subdir2;
        
        if (!is_dir($full_subdir)) {
            mkdir($full_subdir, 0755, true);
        }
        
        $filepath = $full_subdir . '/' . $filename;
        if (!file_put_contents($filepath, $png_binary)) {
            return ['document_id' => null, 'message' => ''];
        }
        
        $timestamp = date('d/m/Y H:i:s');
        $doc_name = $name . ' - Diagrama BPMN - ' . $timestamp;
        $db_filepath = $subdir1 . '/' . $subdir2 . '/' . $filename;
        
        $document_id = $DB->insert('glpi_documents', [
            'entities_id'   => $entities_id,
            'name'          => $doc_name,
            'filename'      => $filename,
            'filepath'      => $db_filepath,
            'mime'          => 'image/png',
            'sha1sum'       => $hash,
            'users_id'      => $user_id,
            'date_creation' => $_SESSION['glpi_currenttime'] ?? date('Y-m-d H:i:s'),
            'date_mod'      => $_SESSION['glpi_currenttime'] ?? date('Y-m-d H:i:s'),
            'is_deleted'    => 0
        ]);
        
        if (!$document_id) {
            return ['document_id' => null, 'message' => 'Erro ao criar documento'];
        }
        
        $DB->insert('glpi_documents_items', [
            'documents_id'  => $document_id,
            'items_id'      => $items_id,
            'itemtype'      => $itemtype,
            'entities_id'   => $entities_id,
            'is_recursive'  => 0,
            'users_id'      => $user_id,
            'date_creation' => $_SESSION['glpi_currenttime'] ?? date('Y-m-d H:i:s'),
            'date_mod'      => $_SESSION['glpi_currenttime'] ?? date('Y-m-d H:i:s')
        ]);
        
        return ['document_id' => $document_id, 'message' => 'PNG anexado.'];
        
    } catch (Exception $e) {
        return ['document_id' => null, 'message' => ''];
    }
}
