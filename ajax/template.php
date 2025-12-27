<?php
// Buffer output immediately to catch Warnings during boot
ob_start();

$glpi_root = dirname(__DIR__, 3);
require_once $glpi_root . '/vendor/autoload.php';

use Glpi\Kernel\Kernel;
use Glpi\Application\Environment;

// Boot kernel
$kernel = new Kernel(Environment::PRODUCTION->value, false);
$kernel->boot();

global $DB;

// Clean buffer after boot (remove Deprecation warnings etc)
ob_end_clean();

header("Content-Type: application/json; charset=UTF-8");

// Start robust buffer for JSON response
ob_start();

$user_id = Session::getLoginUserID();

if (!$user_id) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Usuário não autenticado']));
}


// Basic CSRF check (Optional based on previous config)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_SERVER['HTTP_X_GLPI_CSRF_TOKEN'] ?? '';
    // if (empty($csrfToken) && !Session::validateCSRF(['_glpi_csrf_token' => $csrfToken])) { ... }
}

$rawInput = file_get_contents("php://input");
$input = json_decode($rawInput, true) ?? [];
$action = $_GET['action'] ?? ($input['action'] ?? '');

try {
    switch ($action) {
        case 'list':
            $keyword = $_GET['keyword'] ?? ''; // Potential future search
            
            if (!class_exists('PluginFlowbpmnTemplate')) {
                 include_once(GLPI_ROOT . '/plugins/flowbpmn/inc/template.class.php');
            }
            
            $templates = PluginFlowbpmnTemplate::getAvailableTemplates();
            
            // Format for easy consumption
            $list = array_map(function($t) {
                return [
                    'id' => $t['id'],
                    'name' => $t['name'],
                    'comment' => $t['comment'],
                    'is_public' => $t['is_public'],
                    'xml_url' => '', // Could provide URL to fetch XML separately if needed
                    // Send minimal data for list, fetch full XML on select if list is huge
                    // For now, sending full data since templates are few
                    'bpmn_xml' => $t['bpmn_xml'] 
                ];
            }, $templates);
            
            echo json_encode(['success' => true, 'templates' => $list]);
            break;
            
        case 'save':
            if (!class_exists('PluginFlowbpmnTemplate')) {
                 include_once(GLPI_ROOT . '/plugins/flowbpmn/inc/template.class.php');
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
                throw new Exception('Erro ao salvar template no banco');
            }
            break;
            
        default:
            throw new Exception('Ação inválida');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
ob_end_flush();
