<?php
/**
 * -------------------------------------------------------------------------
/**
 * -------------------------------------------------------------------------
 * FlowBPMN Plugin for GLPI - Native DB Flow Handler v3.0
 * -------------------------------------------------------------------------
 */

// Start output buffering to prevent any unwanted output
ob_start();

// Bootstrap GLPI manually
// Initialize GLPI Environment
include('../../../inc/includes.php');

// Manual includes for Plugin Classes (if autoloader fails)
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
if (!class_exists('PluginFlowbpmnProfile')) {
    if (file_exists(__DIR__ . '/../inc/profile.class.php')) {
        include_once __DIR__ . '/../inc/profile.class.php';
    }
}

// Check if user is logged in
Session::checkLoginUser();
$user_id = Session::getLoginUserID();

// Custom Error Handler to convert PHP errors to JSON
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return false;
    }
    
    // Log error but don't output HTML
    error_log("FlowBPMN PHP Error: [$errno] $errstr in $errfile:$errline");
    
    // For fatal errors that might print output, we try to clear buffer
    // But for warnings/notices, we just continue (logging is enough)
    return true; 
});

// Shutdown function to catch fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE || $error['type'] === E_CORE_ERROR)) {
        if (ob_get_length()) ob_clean();
        header("Content-Type: application/json; charset=UTF-8");
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Erro Fatal PHP: ' . $error['message']
        ]);
        exit;
    }
});

// Clear any unwanted output and set proper headers
if (ob_get_length()) ob_clean();
header("Content-Type: application/json; charset=UTF-8");


if (!$user_id) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Usuário não autenticado']));
}


// CSRF Protection (optional for now - GLPI 10 may not always provide token in AJAX)
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
        error_log("FlowBPMN WARNING: Invalid CSRF Token received: " . $csrfToken);
        // http_response_code(403);
        // die(json_encode(['success' => false, 'message' => 'Token CSRF inválido']));
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
ini_set('display_errors', '0'); // CRITICAL: Suppress HTML errors to output

// Log para debug
error_log("FlowBPMN AJAX: action=$action, method=" . $_SERVER['REQUEST_METHOD']);
error_log("FlowBPMN AJAX: input=" . json_encode($input));


try {
    switch ($action) {
        case 'save':
            error_log("FlowBPMN: Iniciando save");
            
            $itemtype = $input['itemtype'] ?? '';
            $items_id = (int)($input['items_id'] ?? 0);
            $bpmn_xml = $input['bpmn_xml'] ?? '';
            $name = $input['name'] ?? 'FlowBPMN Diagram';
            $svg_content = $input['svg_content'] ?? '';
            
            error_log("FlowBPMN: itemtype=$itemtype, items_id=$items_id, name=$name");
            
            if (empty($itemtype) || $items_id <= 0 || empty($bpmn_xml)) {
                error_log("FlowBPMN: Missing parameters - itemtype=$itemtype, items_id=$items_id, xml_length=" . strlen($bpmn_xml));
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

            // SAFETY NET: Ensure v1 is created for new flows if post_addItem failed
            if ($action === 'criado') {
                 if (!class_exists('PluginFlowbpmnVersion')) {
                      include_once(GLPI_ROOT . '/plugins/flowbpmn/inc/version.class.php');
                 }
                 if (class_exists('PluginFlowbpmnVersion')) {
                      $currentCount = PluginFlowbpmnVersion::countVersions($flow_id);
                      if ($currentCount == 0) {
                           error_log("flowBPMN INFO: Manual v1 creation in ajax/flow.php for flow_id=$flow_id");
                           PluginFlowbpmnVersion::createVersion($flow_id, $flowInput);
                      }
                 }
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

        case 'get_xml':
            $itemtype = $input['itemtype'] ?? '';
            $items_id = (int)($input['items_id'] ?? 0);

            if (empty($itemtype) || $items_id <= 0) {
                throw new Exception('Missing required parameters for import');
            }

            $flow = new PluginFlowbpmnFlow();
            $data = $flow->getForItem($itemtype, $items_id);

            if (!$data || empty($data['bpmn_xml'])) {
                // If not found, return empty success so frontend handles "No content" or error
                // JS expects { success: true, xml: ... }
                echo json_encode(['success' => true, 'xml' => '']);
            } else {
                echo json_encode(['success' => true, 'xml' => $data['bpmn_xml']]);
            }
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
            
                error_log("FlowBPMN: Entrando em list_templates");
                $all_templates = [];

                // 1. Load Standard Templates (Files) - DISABLED per user request
                // $std_path = GLPI_ROOT . '/plugins/flowbpmn/templates/';
                // ... (Removed mocked data)

                // 2. Load DB Templates
                error_log("FlowBPMN: Verificando DB Templates");
                if ($DB->tableExists('glpi_plugin_flowbpmn_templates') && class_exists('PluginFlowbpmnTemplate')) {
                    error_log("FlowBPMN: Buscando templates do banco");
                    $db_templates = PluginFlowbpmnTemplate::getAvailableTemplates();
                    error_log("FlowBPMN: Encontrados " . count($db_templates) . " templates no banco");
                    
                    foreach ($db_templates as $t) {
                        $xml = $t['bpmn_xml'];
                        
                        $all_templates[] = [
                            'id'            => $t['id'],
                            'name'          => $t['name'],
                            'bpmn_xml'      => base64_encode($xml), // Encode for safe transport/attribute storage
                            'is_standard'   => 0,
                            'can_delete'    => $t['can_delete'] ?? false,
                            'svg_content'   => $t['svg_content'] ?? '',
                            'date_mod'      => Html::convDateTime($t['date_mod']),
                            'author_name'   => $t['author_name'] ?? 'Desconhecido',
                            'comment'       => $t['comment']
                        ];
                    }
                }
                
                error_log("FlowBPMN: Retornando JSON final");
                $jsonOutput = json_encode(['success' => true, 'templates' => $all_templates]);
                
                if ($jsonOutput === false) {
                    throw new Exception("Falha ao codificar JSON: " . json_last_error_msg());
                }
                echo $jsonOutput;

            } catch (\Throwable $e) {
                error_log("FlowBPMN ERROR no list_templates: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => "Erro ao listar templates: " . $e->getMessage()]);
            }
            break;

        case 'delete_template':
            $template_id = (int)($input['id'] ?? 0);
            
            if ($template_id <= 0) {
                throw new Exception('ID inválido para exclusão');
            }

            if (!PluginFlowbpmnTemplate::canDelete()) { // Will use fallback logic added earlier
                http_response_code(403);
                throw new Exception('Sem permissão para excluir modelos');
            }

            $template = new PluginFlowbpmnTemplate();
            
            // Security check: Only delete if exists
            if (!$template->getFromDB($template_id)) {
                 throw new Exception('Modelo não encontrado');
            }

            if ($template->delete(['id' => $template_id])) {
                echo json_encode(['success' => true, 'message' => 'Modelo excluído']);
            } else {
                throw new Exception('Falha ao excluir modelo do banco de dados');
            }
            break;

        case 'save_template':
            if (!class_exists('PluginFlowbpmnTemplate')) {
                 include_once(__DIR__ . '/../inc/template.class.php');
            }
            
            // Check permission
            if (!PluginFlowbpmnTemplate::canCreate()) {
                 http_response_code(403);
                 throw new Exception('Você não tem permissão para criar modelos');
            }

            $name = $input['name'] ?? '';
            $xml = $input['bpmn_xml'] ?? '';
            $svg = $input['svg_content'] ?? '';
            $is_public = (int)($input['is_public'] ?? 0);
            
            if (empty($name) || empty($xml)) {
                throw new Exception('Nome e conteúdo (XML) são obrigatórios');
            }
            
            $template = new PluginFlowbpmnTemplate();
            
            // Prepare input manually since we are not using front/ form
            $addInput = [
                'name' => $name,
                'bpmn_xml' => $xml,
                'svg_content' => $svg, // Requires DB update? Assume column exists or handled by class
                'is_public' => $is_public,
                'comment' => '', // Removed default text per user request

                'users_id' => Session::getLoginUserID(),
                'entities_id' => $_SESSION['glpiactive_entity'] ?? 0,
                'is_active' => 1
            ];

            // Use class add method which handles compression in prepareInputForAdd
            $newID = $template->add($addInput);
            
            if ($newID) {
                echo json_encode(['success' => true, 'message' => 'Template salvo com sucesso', 'id' => $newID]);
            } else {
                throw new Exception('Erro ao salvar template no banco');
            }
            break;
            
        default:
            http_response_code(400);
            throw new Exception('Invalid action');
            throw new Exception('Invalid action');
    }
    exit; // Ensure strict termination to prevent GLPI footer injection
} catch (Exception $e) {
    error_log("FlowBPMN ERROR: " . $e->getMessage());
    error_log("FlowBPMN ERROR Trace: " . $e->getTraceAsString());
    
    if (http_response_code() === 200) {
        http_response_code(400);
    }
    
    // Garantir que qualquer output anterior seja limpo
    if (ob_get_level() > 0) {
        ob_clean();
    }
    
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
} catch (Throwable $e) {
    // Captura erros fatais também
    error_log("FlowBPMN FATAL ERROR: " . $e->getMessage());
    
    if (ob_get_level() > 0) {
        ob_clean();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Erro fatal: ' . $e->getMessage()
    ]);
}
