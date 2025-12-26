<?php
/**
 * -------------------------------------------------------------------------
 * FlowBPMN Plugin for GLPI - Direct SQL Flow Handler + Auto Versioning v2.1
 * -------------------------------------------------------------------------
 */

// Bootstrap GLPI manually (since this file is called directly, not through front controller)
$glpi_root = dirname(__DIR__, 3);

// Include autoloader
require_once $glpi_root . '/vendor/autoload.php';

// Initialize GLPI Kernel
use Glpi\Kernel\Kernel;
use Glpi\Application\Environment;

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

// Get DB config from Docker environment variables
$DB_HOST = getenv('GLPI_DB_HOST') ?: 'mariadb';
$DB_NAME = getenv('GLPI_DB_NAME') ?: 'glpi';
$DB_USER = getenv('GLPI_DB_USER') ?: 'glpi';
$DB_PASS = getenv('GLPI_DB_PASSWORD') ?: 'glpi';

// Connect to database using mysqli
$db = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if ($db->connect_error) {
    http_response_code(500);
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

$db->set_charset('utf8mb4');

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
            
            if (empty($itemtype) || $items_id <= 0 || empty($bpmn_xml)) {
                throw new Exception('Missing required parameters');
            }
            
            // Validate itemtype
            $validTypes = ['Ticket', 'Problem', 'Change'];
            if (!in_array($itemtype, $validTypes)) {
                throw new Exception('Invalid item type');
            }
            
            // Check permissions
            if (!PluginFlowbpmnProfile::canEditFlow($itemtype)) {
                http_response_code(403);
                throw new Exception('Você não tem permissão para editar diagramas BPMN');
            }
            
            // Escape values
            $itemtype = $db->real_escape_string($itemtype);
            $base_bpmn_xml = $bpmn_xml; 
            $bpmn_xml_escaped = $db->real_escape_string($bpmn_xml);
            $name_escaped = $db->real_escape_string($name);
            
            // Get proper entity ID from the actual item table
            $entities_id = 0;
            $tableMap = [
                'Ticket' => 'glpi_tickets',
                'Problem' => 'glpi_problems',
                'Change' => 'glpi_changes'
            ];
            $itemTable = $tableMap[$itemtype] ?? 'glpi_tickets';
            
            $res = $db->query("SELECT entities_id FROM $itemTable WHERE id = $items_id LIMIT 1");
            if ($res && $row = $res->fetch_assoc()) {
                $entities_id = (int)$row['entities_id'];
            }
            
            // 1. Check for Existing Flow (Avoid Duplicates)
            $flow_id = 0;
            // Get the MOST RECENT one if duplicates exist
            $res = $db->query("SELECT id FROM glpi_plugin_flowbpmn_flows WHERE itemtype = '$itemtype' AND items_id = $items_id ORDER BY id DESC LIMIT 1");
            if ($res && $row = $res->fetch_assoc()) {
                $flow_id = (int)$row['id'];
            }
            
            if ($flow_id > 0) {
                // UPDATE
                $sql = "UPDATE glpi_plugin_flowbpmn_flows SET 
                        bpmn_xml = '$bpmn_xml_escaped',
                        name = '$name_escaped',
                        users_id = $user_id,
                        date_mod = NOW()
                        WHERE id = $flow_id";
                if (!$db->query($sql)) {
                    throw new Exception('Database error (Update): ' . $db->error);
                }
            } else {
                // INSERT
                $sql = "INSERT INTO glpi_plugin_flowbpmn_flows 
                        (itemtype, items_id, entities_id, bpmn_xml, name, users_id, date_creation, date_mod)
                        VALUES ('$itemtype', $items_id, $entities_id, '$bpmn_xml_escaped', '$name_escaped', $user_id, NOW(), NOW())";
                if (!$db->query($sql)) {
                    throw new Exception('Database error (Insert): ' . $db->error);
                }
                $flow_id = $db->insert_id;
            }
            
            if (!$flow_id) throw new Exception("Failed to manage Flow ID");

            // 2. Auto-create Version (History)
            $res = $db->query("SELECT MAX(version_number) as max_v FROM glpi_plugin_flowbpmn_versions WHERE plugin_flowbpmn_flows_id = $flow_id");
            $max_v = 0;
            if ($res && $row = $res->fetch_assoc()) {
                $max_v = (int)$row['max_v'];
            }
            $next_v = $max_v + 1;
            
            $version_name = $name_escaped;
            $version_comment = "Versão $next_v (Auto-save)";
            
            $stmt = $db->prepare("INSERT INTO glpi_plugin_flowbpmn_versions 
                                (plugin_flowbpmn_flows_id, version_number, name, comment, bpmn_xml, svg_content, users_id, date_creation) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                                
            if ($stmt) {
                // i = integer, s = string (parameters: flow_id, version_num, name, comment, bpmn_xml, svg_content, users_id)
                $svg_content = $input['svg_content'] ?? '';
                $stmt->bind_param("iissssi", $flow_id, $next_v, $version_name, $version_comment, $base_bpmn_xml, $svg_content, $user_id);
                $stmt->execute();
                $stmt->close();
            }

            // 3. Add entry to timeline using Log::history() with HISTORY_LOG_SIMPLE_MESSAGE
            $action_type = ($flow_id > 0) ? "atualizado" : "criado";
            $log_message = "Diagrama BPMN $action_type: Versão $next_v ($name_escaped)";
            
            Log::history(
                $items_id,
                $itemtype,
                [0, '', $log_message],
                '',                             // itemtype_link empty for simple message
                Log::HISTORY_LOG_SIMPLE_MESSAGE // linked_action = 19
            );

            // 4. Process PNG
            $document_id = null;
            $png_message = '';
            
            if (!empty($input['png_data'])) {
                $result = createGLPIDocument($db, $input['png_data'], $itemtype, $items_id, $entities_id, $name, $user_id);
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
            
            $itemtype = $db->real_escape_string($itemtype);
            
            $sql = "SELECT * FROM glpi_plugin_flowbpmn_flows
                    WHERE itemtype = '$itemtype' AND items_id = $items_id
                    ORDER BY id DESC LIMIT 1";
            
            $result = $db->query($sql);
            $data = $result ? $result->fetch_assoc() : null;
            
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'delete_version':
            $version_id = (int)($input['version_id'] ?? 0);
            if ($version_id <= 0) {
                throw new Exception('Invalid Version ID');
            }
            
            // Get version info to check itemtype
            $res = $db->query("SELECT f.itemtype, f.items_id, v.version_number, v.name FROM glpi_plugin_flowbpmn_versions v 
                               JOIN glpi_plugin_flowbpmn_flows f ON v.plugin_flowbpmn_flows_id = f.id 
                               WHERE v.id = $version_id LIMIT 1");
            
            if (!$res || $res->num_rows === 0) {
                throw new Exception('Versão não encontrada');
            }
            
            $row = $res->fetch_assoc();
            $itemtype = $row['itemtype'];
            $items_id = (int)$row['items_id'];
            $version_num = $row['version_number'];
            $version_name = $row['name'];
            
            // Check permissions
            if (!PluginFlowbpmnProfile::canDeleteFlow($itemtype)) {
                http_response_code(403);
                throw new Exception('Você não tem permissão para excluir versões');
            }
            
            $sql = "DELETE FROM glpi_plugin_flowbpmn_versions WHERE id = $version_id";
            
            if ($db->query($sql)) {
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
            } else {
                throw new Exception('Erro no banco de dados: ' . $db->error);
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

$db->close();

function createGLPIDocument($db, $png_data, $itemtype, $items_id, $entities_id, $name, $user_id) {
    try {
        $png_data = preg_replace('/^data:image\/png;base64,/', '', $png_data);
        $png_binary = base64_decode($png_data);
        
        if (!$png_binary || strlen($png_binary) < 100) return ['document_id' => null, 'message' => ''];
        
        $hash = sha1($png_binary);
        $subdir1 = 'PNG';
        $subdir2 = substr($hash, 0, 2);
        $filename = $hash . '.PNG';
        
        $base_dir = '/var/glpi/files';
        $full_subdir = $base_dir . '/' . $subdir1 . '/' . $subdir2;
        
        if (!is_dir($full_subdir)) mkdir($full_subdir, 0755, true);
        
        $filepath = $full_subdir . '/' . $filename;
        if (!file_put_contents($filepath, $png_binary)) return ['document_id' => null, 'message' => ''];
        
        $timestamp = date('d/m/Y H:i:s');
        $doc_name = $db->real_escape_string($name . ' - Diagrama BPMN - ' . $timestamp);
        $db_filepath = $subdir1 . '/' . $subdir2 . '/' . $filename;
        
        // Remove existing document links for this item to avoid clutter?
        // OPTIONAL: Keep all history
        
        $sql = "INSERT INTO glpi_documents 
                (entities_id, name, filename, filepath, mime, sha1sum, 
                 users_id, date_creation, date_mod, is_deleted)
                VALUES 
                ($entities_id, '$doc_name', '$filename', '$db_filepath', 'image/png', '$hash',
                 $user_id, NOW(), NOW(), 0)";
        
        if (!$db->query($sql)) {
             return ['document_id' => null, 'message' => 'Erro DB Documento: ' . $db->error];
        }
        
        $document_id = $db->insert_id;
        
        $sql = "INSERT INTO glpi_documents_items 
                (documents_id, items_id, itemtype, entities_id, is_recursive, users_id, date_creation, date_mod)
                VALUES 
                ($document_id, $items_id, '$itemtype', $entities_id, 0, $user_id, NOW(), NOW())";
        
        $db->query($sql);
        
        return ['document_id' => $document_id, 'message' => 'PNG anexado.'];
        
    } catch (Exception $e) {
        return ['document_id' => null, 'message' => ''];
    }
}
?>
