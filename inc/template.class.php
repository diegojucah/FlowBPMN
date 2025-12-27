<?php
declare(strict_types=1);

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Template class - Manages BPMN diagram templates
 */
class PluginFlowbpmnTemplate extends CommonDBTM {

    static function getTable($classname = null) {
        return 'glpi_plugin_flowbpmn_templates';
    }

    static function getTypeName($nb = 0) {
        return _n('Flow Template', 'Flow Templates', $nb, 'flowbpmn');
    }
    
    static function canCreate() {
        return Session::haveRight('plugin_flowbpmn_profile', CREATE);
    }

    static function canView() {
        return Session::haveRight('plugin_flowbpmn_profile', READ);
    }
    
    /**
     * Define tabs for the template
     */
    function defineTabs($options = []) {
        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addStandardTab(__CLASS__, $ong, $options);
        return $ong;
    }
    
    /**
     * Prepare input for add
     */
    function prepareInputForAdd($input) {
        // Validation
        if (empty($input['name'])) {
            Session::addMessageAfterRedirect(
                __('Name is required', 'flowbpmn'),
                false,
                ERROR
            );
            return false;
        }
        
        // Set defaults
        $input['users_id'] = Session::getLoginUserID();
        $input['entities_id'] = $_SESSION['glpiactive_entity'] ?? 0;
        $input['date_creation'] = $_SESSION['glpi_currenttime'] ?? date('Y-m-d H:i:s');
        $input['date_mod'] = $input['date_creation'];
        $input['is_active'] = 1;
        
        // Compression handling (reuse logic if large)
        if (isset($input['bpmn_xml']) && strlen($input['bpmn_xml']) > 10240) {
            $compressed = gzcompress($input['bpmn_xml'], 6);
            if ($compressed !== false) {
                $input['bpmn_xml'] = 'COMPRESSED::' . base64_encode($compressed);
            }
        }
        
        return $input;
    }
    
    /**
     * Prepare input for update
     */
    function prepareInputForUpdate($input) {
        $input['date_mod'] = $_SESSION['glpi_currenttime'] ?? date('Y-m-d H:i:s');
        
        // Compression handling
        if (isset($input['bpmn_xml']) && strlen($input['bpmn_xml']) > 10240 && strpos($input['bpmn_xml'], 'COMPRESSED::') === false) {
            $compressed = gzcompress($input['bpmn_xml'], 6);
            if ($compressed !== false) {
                $input['bpmn_xml'] = 'COMPRESSED::' . base64_encode($compressed);
            }
        }
        
        return $input;
    }
    
    /**
     * Get templates available for current user
     */
    static function getAvailableTemplates() {
        global $DB;
        
        $templates = [];
        $uid = Session::getLoginUserID();
        $eid = $_SESSION['glpiactive_entity'] ?? 0;
        
        // Get public templates from current entity, OR private templates owned by user
        // Note: Recursive entity search (getSonsOf) skipped for stability in ajax context
        $iterator = $DB->request([
            'FROM'  => self::getTable(),
            'WHERE' => [
                'is_active' => 1,
                'OR' => [
                    ['is_public' => 1, 'entities_id' => $eid],
                    ['users_id' => $uid]
                ]
            ],
            'ORDER' => 'name ASC'
        ]);
        
        foreach ($iterator as $data) {
            // Decompress if needed
            if (isset($data['bpmn_xml']) && strpos($data['bpmn_xml'], 'COMPRESSED::') === 0) {
                $encoded = substr($data['bpmn_xml'], 12);
                $compressed = base64_decode($encoded);
                if ($compressed) {
                    $decompressed = gzuncompress($compressed);
                    $data['bpmn_xml'] = $decompressed ?: '';
                }
            }
            
            $templates[] = [
                'id' => $data['id'],
                'name' => $data['name'],
                'comment' => $data['comment'],
                'bpmn_xml' => $data['bpmn_xml'],
                'is_public' => $data['is_public'],
                'owner_id' => $data['users_id']
            ];
        }
        
        return $templates;
    }
}
