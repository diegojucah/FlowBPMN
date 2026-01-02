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
    
    static function canCreate(): bool {
        // Allow if has specific right OR is admin (config update)
        return Session::haveRight('plugin_flowbpmn_profile', CREATE) || Session::haveRight('config', UPDATE);
    }

    static function canView(): bool {
        return Session::haveRight('plugin_flowbpmn_profile', READ) || Session::haveRight('config', READ);
    }

    static function canUpdate(): bool {
        return Session::haveRight('plugin_flowbpmn_profile', UPDATE) || Session::haveRight('config', UPDATE);
    }

    static function canDelete(): bool {
        return Session::haveRight('plugin_flowbpmn_profile', DELETE) || Session::haveRight('config', UPDATE);
    }

    static function canPurge(): bool {
        return Session::haveRight('plugin_flowbpmn_profile', PURGE) || Session::haveRight('config', UPDATE);
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
        
        // General rights
        $can_purge_global = self::canPurge();

        // Get public templates from current entity, OR private templates owned by user
        $iterator = $DB->request([
            'SELECT' => [
                't.*',
                'u.name AS username',
                'u.realname',
                'u.firstname'
            ],
            'FROM'  => self::getTable() . ' AS t',
            'LEFT JOIN' => [
                'glpi_users AS u' => ['ON' => ['t' => 'users_id', 'u' => 'id']]
            ],
            'WHERE' => [
                't.is_active' => 1,
                'OR' => [
                    ['t.is_public' => 1, 't.entities_id' => $eid],
                    ['t.users_id' => $uid]
                ]
            ],
            'ORDER' => 't.name ASC'
        ]);
        
        foreach ($iterator as $data) {
            // Decompress BPMN XML if needed
            if (isset($data['bpmn_xml']) && strpos($data['bpmn_xml'], 'COMPRESSED::') === 0) {
                $encoded = substr($data['bpmn_xml'], 12);
                $compressed = base64_decode($encoded);
                if ($compressed) {
                    $decompressed = gzuncompress($compressed);
                    $data['bpmn_xml'] = $decompressed ?: '';
                }
            }
            
            // Decompress SVG Content if needed (future proofing)
            if (isset($data['svg_content']) && strpos($data['svg_content'], 'COMPRESSED::') === 0) {
                $encoded = substr($data['svg_content'], 12);
                $compressed = base64_decode($encoded);
                if ($compressed) {
                    $decompressed = gzuncompress($compressed);
                    $data['svg_content'] = $decompressed ?: '';
                }
            }

            // Format Author Name
            $authorName = $data['username'];
            if (!empty($data['realname'])) {
                $authorName = $data['realname'];
                if (!empty($data['firstname'])) {
                    $authorName = $data['firstname'] . ' ' . $authorName;
                }
            }
            
            // Determine permission to delete this specific template
            // Owner can delete their own private templates? Or strictly use canPurge?
            // Usually: Global Purge Right OR (Owner AND Template is Private)
            // But strict GLPI: Global Purge Right. 
            // Let's allow Owners to delete their own templates if they have CREATE right?
            // For now, simplify: Admin (canPurge) OR Owner (users_id == uid)
            $is_owner = ($data['users_id'] == $uid);
            $can_delete_item = $can_purge_global || $is_owner;

            $templates[] = [
                'id' => $data['id'],
                'name' => $data['name'],
                'comment' => $data['comment'],
                'bpmn_xml' => $data['bpmn_xml'],
                'svg_content' => $data['svg_content'] ?? '',
                'is_public' => $data['is_public'],
                'owner_id' => $data['users_id'],
                'author_name' => $authorName,
                'date_mod' => $data['date_mod'],
                'can_delete' => $can_delete_item
            ];
        }
        
        return $templates;
    }
}
