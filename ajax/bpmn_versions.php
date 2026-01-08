<?php
/**
 * -------------------------------------------------------------------------
 * FlowBPMN Plugin for GLPI - Native DB Versions Handler v3.0
 * -------------------------------------------------------------------------
 */

// Initialize GLPI Environment
include('../../../inc/includes.php');
global $DB;

global $CFG_GLPI, $DB;

header("Content-Type: application/json; charset=UTF-8");

$user_id = Session::getLoginUserID();

if (!$user_id) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Usuário não autenticado']));
}

try {
    $itemtype = $_GET['itemtype'] ?? '';
    $items_id = (int)($_GET['items_id'] ?? 0);

    if (empty($itemtype) || empty($items_id)) {
        throw new Exception('Missing parameters');
    }

    if (!in_array($itemtype, ['Ticket', 'Problem', 'Change'])) {
        throw new Exception('Invalid item type');
    }
    
    // Check permissions
    if (!PluginFlowbpmnProfile::canViewFlow($itemtype)) {
        http_response_code(403);
        throw new Exception('Você não tem permissão para visualizar diagramas BPMN');
    }

    // 1. Get Current Flow with user name
    // Use COALESCE to handle NULL firstname/realname and fallback to username
    $iterator = $DB->request([
        'SELECT' => [
            'f.*',
            new QueryExpression("COALESCE(
                NULLIF(CONCAT(COALESCE(" . $DB->quoteName('u.firstname') . ", ''), ' ', COALESCE(" . $DB->quoteName('u.realname') . ", '')), ' '),
                " . $DB->quoteName('u.name') . ",
                'Unknown'
            ) AS user_name")
        ],
        'FROM'   => 'glpi_plugin_flowbpmn_flows AS f',
        'LEFT JOIN' => [
            'glpi_users AS u' => ['ON' => ['f' => 'users_id', 'u' => 'id']]
        ],
        'WHERE'  => [
            'f.itemtype' => $itemtype,
            'f.items_id' => $items_id
        ],
        'ORDER'  => 'f.id DESC',
        'LIMIT'  => 1
    ]);

    if (!count($iterator)) {
        echo json_encode([
            'success' => true, 
            'versions' => [], 
            'current' => null
        ]);
        exit;
    }

    $currentFlow = $iterator->current();
    $flowId = (int)$currentFlow['id'];

    // 2. Get Versions with user name
    // Use COALESCE to handle NULL firstname/realname and fallback to username
    $iterator = $DB->request([
        'SELECT' => [
            'v.*',
            new QueryExpression("COALESCE(
                NULLIF(CONCAT(COALESCE(" . $DB->quoteName('u.firstname') . ", ''), ' ', COALESCE(" . $DB->quoteName('u.realname') . ", '')), ' '),
                " . $DB->quoteName('u.name') . ",
                'Unknown'
            ) AS user_name")
        ],
        'FROM'   => 'glpi_plugin_flowbpmn_versions AS v',
        'LEFT JOIN' => [
            'glpi_users AS u' => ['ON' => ['v' => 'users_id', 'u' => 'id']]
        ],
        'WHERE'  => ['v.plugin_flowbpmn_flows_id' => $flowId],
        'ORDER'  => 'v.version_number DESC'
    ]);

    // 3. Format Date Helper
    $formatDate = function($date): string {
        if (!$date) return '-';
        return date('d/m/Y H:i', strtotime($date));
    };

    // 4. Prepare Response
    $formattedVersions = [];
    foreach ($iterator as $v) {
        $svg = $v['svg_content'] ?? '';
        
        // Check for compression (standard flowbpmn pattern)
        if (!empty($svg) && strpos($svg, 'COMPRESSED::') === 0) {
            $encoded = substr($svg, 12);
            $compressed = base64_decode($encoded);
            if ($compressed) {
                // Try decompress
                $decompressed = @gzuncompress($compressed);
                if ($decompressed !== false) {
                    $svg = $decompressed;
                }
            }
        }

        $formattedVersions[] = [
            'id' => $v['id'],
            'version_number' => $v['version_number'],
            'name' => $v['name'],
            'comment' => $v['comment'], 
            'users_id' => $v['users_id'],
            'user_name' => $v['user_name'] ?: 'Unknown',
            'date_creation' => $v['date_creation'],
            'date_creation_formatted' => $formatDate($v['date_creation']),
            'svg_content' => $svg
        ];
    }

    // 5. Check restore permissions
    $canRestore = PluginFlowbpmnProfile::canRestoreFlow($itemtype);

    // Calculate actual version number (count of old versions, current is the latest)
    // If there are 30 old versions, current is v30 (not v31)
    $versionNumber = count($formattedVersions);
    
    echo json_encode([
        'success' => true,
        'versions' => $formattedVersions,
        'current' => [
            'id' => $versionNumber, // Show version number for display
            'flow_db_id' => $currentFlow['id'], // Real database ID for restore
            'name' => $currentFlow['name'],
            'date_mod' => $currentFlow['date_mod'],
            'date_mod_formatted' => $formatDate($currentFlow['date_mod']),
            'user_name' => $currentFlow['user_name'] ?: 'Unknown'
        ],
        'canRestore' => $canRestore
    ]);
    
    ob_clean();
    echo json_encode([
        'success' => true,
        'versions' => $formattedVersions,
        'current' => [
            'id' => $versionNumber, // Show version number for display
            'flow_db_id' => $currentFlow['id'], // Real database ID for restore
            'name' => $currentFlow['name'],
            'date_mod' => $currentFlow['date_mod'],
            'date_mod_formatted' => $formatDate($currentFlow['date_mod']),
            'user_name' => $currentFlow['user_name'] ?: 'Unknown'
        ],
        'canRestore' => $canRestore
    ]);

} catch (Exception $e) {
    http_response_code(400);
    http_response_code(400);
    ob_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
