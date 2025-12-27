<?php
declare(strict_types=1);
/**
 * -------------------------------------------------------------------------
/**
 * -------------------------------------------------------------------------
 * FlowBPMN Plugin for GLPI - Native DB Flow Handler v3.0
 * -------------------------------------------------------------------------
 */
error_log("DEBUG: FlowPHP Hit at " . date('H:i:s') . "\n", 3, "/tmp/flowbpmn_debug.log");

// Bootstrap GLPI manually (since this file is called directly, not through front controller)
$glpi_root = dirname(__DIR__, 3);

/*
if (!defined('GLPI_ROOT')) {
    define('GLPI_ROOT', $glpi_root);
}
*/

// Include autoloader
require_once $glpi_root . '/vendor/autoload.php';

// Manual includes for Plugin Classes (Autoloader might fail in manual boot)
if (!class_exists('PluginFlowbpmnFlow')) {
    if (file_exists(__DIR__ . '/../inc/flow.class.php')) {
        include_once __DIR__ . '/../inc/flow.class.php';
    }
}
if (!class_exists('PluginFlowbpmnVersion')) {
    if (file_exists(__DIR__ . '/../inc/version.class.php')) {
        include_once __DIR__ . '/../inc/version.class.php';
    }
}

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

// CSRF Protection (optional for now - GLPI 11 may not always provide token in AJAX)
// TODO: Make this mandatory after confirming GLPI token availability
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_SERVER['HTTP_X_GLPI_CSRF_TOKEN'] ?? '';
    
    // Log warning if token is missing (for debugging)
    if (empty($csrfToken)) {
        error_log("FlowBPMN: CSRF token missing in request (user_id=$user_id)");
    }
    
    // For now, only validate if token is provided
    // This maintains backward compatibility while adding security
    if (!empty($csrfToken) && !Session::validateCSRF(['_glpi_csrf_token' => $csrfToken])) {
        http_response_code(403);
        die(json_encode(['success' => false, 'message' => 'Token CSRF inválido']));
    }
}

// Get input
$rawInput = file_get_contents("php://input");
$input = [];

if (!empty($rawInput)) {
    $input = json_decode($rawInput, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        die(json_encode(['success' => false, 'message' => 'Invalid JSON']));
    }
}

$action = $_GET['action'] ?? ($input['action'] ?? '');

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
            
            // Prepare input for GLPI class methods
            $flowInput = [
                'itemtype'    => $itemtype,
                'items_id'    => $items_id,
                'name'        => $name,
                'bpmn_xml'    => $bpmn_xml,
                'svg_content' => $svg_content,
                '_png_data'   => $input['png_data'] ?? '', // Underscore prefix = temporary field
            ];
            
            // Check if flow already exists
            $flow = new PluginFlowbpmnFlow();
            $existing = $flow->getForItem($itemtype, $items_id);
            
            if ($existing) {
                // Optimistic Locking Check (Priority 4.2)
                $clientDateMod = $input['date_mod'] ?? '';
                $forceOverwrite = $input['force_overwrite'] ?? false;
                
                if (!$forceOverwrite && !empty($clientDateMod) && isset($existing['date_mod'])) {
                     $dbDateMod = strtotime($existing['date_mod']);
                     $clientTimestamp = strtotime($clientDateMod);
                     
                     // Tolerance of 2s for clock skew
                     if ($dbDateMod > $clientTimestamp + 2) {
                        echo json_encode([
                            'success' => false,
                            'conflict' => true,
                            'server_date_mod' => $existing['date_mod'],
                            'message' => 'O diagrama foi modificado por outro usuário.'
                        ]);
                        break; // Use break to exit switch standardly
                     }
                }

                // UPDATE existing flow
                $flowInput['id'] = $existing['id'];
                $success = $flow->update($flowInput);
                $flow_id = $existing['id'];
                $action = 'atualizado';
            } else {
                // CREATE new flow
                $flow_id = $flow->add($flowInput);
                $success = ($flow_id > 0);
                $action = 'criado';
            }
            
            if (!$success || !$flow_id) {
                throw new Exception('Falha ao salvar diagrama');
            }

            // Get updated flow data to return new date_mod
            $updatedFlow = $flow->getFromDB($flow_id);
            $newDateMod = $updatedFlow ? $flow->fields['date_mod'] : date('Y-m-d H:i:s');
            
            // Get version count for response
            $versionCount = 0;
            if (class_exists('PluginFlowbpmnVersion')) {
                $versionCount = PluginFlowbpmnVersion::countVersions($flow_id);
            }
            
            echo json_encode([
                'success' => true,
                'id' => $flow_id,
                'version_created' => $versionCount,
                'date_mod' => $newDateMod,
                'message' => "Diagrama $action com sucesso (v$versionCount)"
            ]);
            break;
            
        case 'load':
            $itemtype = $input['itemtype'] ?? '';
            $items_id = (int)($input['items_id'] ?? 0);
            
            if (empty($itemtype) || $items_id <= 0) {
                throw new Exception('Missing required parameters');
            }
            
            // Refactored to use class method - gains Cache and Decompression features (Priority 2 & 3 support)
            $flow = new PluginFlowbpmnFlow();
            $data = $flow->getForItem($itemtype, $items_id);
            
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

        // Heartbeat Logic (Priority 4.2)
        case 'heartbeat':
            global $DB;
            $items_id = $input['items_id'] ?? 0;
            $itemtype = $input['itemtype'] ?? '';
            $uid      = Session::getLoginUserID();
            
            if (!$items_id || !$itemtype || !$uid) {
                echo json_encode(['success'=>false]);
                break;
            }
            
            // 0. Lazy Migration (Auto-create table if missing)
            if (!$DB->tableExists('glpi_plugin_flowbpmn_sessions')) {
                 $DB->query("CREATE TABLE IF NOT EXISTS `glpi_plugin_flowbpmn_sessions` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `itemtype` varchar(100) NOT NULL,
                    `items_id` int(11) NOT NULL,
                    `users_id` int(11) NOT NULL,
                    `last_ping` datetime NOT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `unique_session` (`itemtype`, `items_id`, `users_id`),
                    KEY `last_ping` (`last_ping`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            }

            // 1. Register/Update presence
            $DB->query("
                INSERT INTO glpi_plugin_flowbpmn_sessions (itemtype, items_id, users_id, last_ping)
                VALUES ('$itemtype', $items_id, $uid, NOW())
                ON DUPLICATE KEY UPDATE last_ping = NOW()
            ");
            
            // 2. Garbage Collection (Clean old sessions > 1 min)
            $DB->query("DELETE FROM glpi_plugin_flowbpmn_sessions WHERE last_ping < (NOW() - INTERVAL 1 MINUTE)");
            
            // 3. Get active users
            $iterator = $DB->request([
                'SELECT' => ['glpi_users.id', 'glpi_users.name', 'glpi_users.realname', 'glpi_users.firstname'],
                'FROM'   => 'glpi_plugin_flowbpmn_sessions',
                'INNER JOIN' => [
                    'glpi_users' => [
                        'ON' => [
                            'glpi_plugin_flowbpmn_sessions' => 'users_id',
                            'glpi_users' => 'id'
                        ]
                    ]
                ],
                'WHERE' => [
                    'glpi_plugin_flowbpmn_sessions.itemtype' => $itemtype,
                    'glpi_plugin_flowbpmn_sessions.items_id' => $items_id,
                    'glpi_plugin_flowbpmn_sessions.users_id' => ['<>', $uid]
                ]
            ]);
            
            $active_users = [];
            foreach ($iterator as $data) {
                $display_name = formatUserName($data['id'], $data['name'], $data['realname'], $data['firstname']);
                $initials = strtoupper(substr($display_name, 0, 2));
                
                $active_users[] = [
                    'id' => $data['id'],
                    'name' => $display_name,
                    'initials' => $initials,
                    'color' => '#'.substr(md5($data['name']), 0, 6) // Deterministic color
                ];
            }
            
            echo json_encode(['success' => true, 'users' => $active_users]);
            break;

        // Templates Logic (Priority 4.1)
        case 'list_templates':
            try {
                global $DB;
                if (!isset($DB)) {
                     throw new Exception("Database not initialized");
                }
            
                // Check if table exists
                if (!$DB->tableExists('glpi_plugin_flowbpmn_templates')) {
                     echo json_encode(['success' => true, 'templates' => []]);
                     break;
                }

                if (!class_exists('PluginFlowbpmnTemplate')) {
                     $path = __DIR__ . '/../inc/template.class.php';
                     if (file_exists($path)) {
                        include_once($path);
                     }
                }
                
                if (!class_exists('PluginFlowbpmnTemplate')) {
                    throw new Exception("Class PluginFlowbpmnTemplate not found");
                }
                
                $templates = PluginFlowbpmnTemplate::getAvailableTemplates();
                
                // Format for easy consumption
                $list = array_map(function($t) {
                    return [
                        'id' => $t['id'],
                        'name' => $t['name'],
                        'comment' => $t['comment'],
                        'is_public' => $t['is_public'],
                        'bpmn_xml' => $t['bpmn_xml'] 
                    ];
                }, $templates);
                
                echo json_encode(['success' => true, 'templates' => $list]);
            } catch (\Throwable $e) {
                // Return JSON error instead of 500 html
                echo json_encode(['success' => false, 'message' => "Erro interno: " . $e->getMessage()]);
            }
            break;

        case 'save_template':
            if (!class_exists('PluginFlowbpmnTemplate')) {
                 include_once(__DIR__ . '/../inc/template.class.php');
            }
            
            $name = $input['name'] ?? '';
            $xml = $input['bpmn_xml'] ?? '';
            $svg = $input['svg_content'] ?? '';
            $is_public = (int)($input['is_public'] ?? 0);
            
            if (empty($name) || empty($xml)) {
                throw new Exception('Nome e conteúdo (XML) são obrigatórios');
            }
            
            $template = new PluginFlowbpmnTemplate();
            $newID = $template->add([
                'name' => $name,
                'bpmn_xml' => $xml,
                'svg_content' => $svg,
                'is_public' => $is_public,
                'comment' => 'Created from diagram'
            ]);
            
            if ($newID) {
                echo json_encode(['success' => true, 'message' => 'Template salvo com sucesso', 'id' => $newID]);
            } else {
                throw new Exception('Erro ao salvar template');
            }
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
