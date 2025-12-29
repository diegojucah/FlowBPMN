<?php
declare(strict_types=1);

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

/**
 * Profile class for FlowBPMN
 */
class PluginFlowbpmnProfile extends CommonDBTM {

    static $rightname = 'plugin_flowbpmn';

    static function getTable($classname = null) {
        return 'glpi_plugin_flowbpmn_profiles';
    }

    static function getTypeName($nb = 0) {
        return _n('FlowBPMN Profile', 'FlowBPMN Profiles', $nb, 'flowbpmn');
    }

    static function getIcon() {
        return 'ti ti-sitemap';
    }

    /**
     * Get rights for specific profile
     */
    static function getProfileRights($profiles_id) {
        global $DB;
        
        $iterator = $DB->request([
            'FROM'  => self::getTable(),
            'WHERE' => ['profiles_id' => $profiles_id],
            'LIMIT' => 1
        ]);
        
        if (count($iterator)) {
            return $iterator->current();
        }
        
        return [];
    }

    /**
     * Check if user can view flow
     */
    static function canViewFlow($itemtype) {
        if (Session::haveRight('config', UPDATE)) return true;
        
        $rights = self::getProfileRights($_SESSION['glpiactiveprofile']['id']);
        $col = 'can_view_' . strtolower($itemtype);
        
        return isset($rights[$col]) && $rights[$col];
    }

    /**
     * Check if user can edit flow
     */
    static function canEditFlow($itemtype) {
        if (Session::haveRight('config', UPDATE)) return true;
        
        $rights = self::getProfileRights($_SESSION['glpiactiveprofile']['id']);
        $col = 'can_edit_' . strtolower($itemtype);
        
        return isset($rights[$col]) && $rights[$col];
    }

    /**
     * Check if user can delete flow/versions
     */
    static function canDeleteFlow($itemtype) {
        if (Session::haveRight('config', UPDATE)) return true;
        
        $rights = self::getProfileRights($_SESSION['glpiactiveprofile']['id']);
        $col = 'can_delete_' . strtolower($itemtype);
        
        return isset($rights[$col]) && $rights[$col];
    }

    /**
     * Check if user can restore flow versions
     */
    static function canRestoreFlow($itemtype) {
        if (Session::haveRight('config', UPDATE)) return true;
        
        $rights = self::getProfileRights($_SESSION['glpiactiveprofile']['id']);
        $col = 'can_restore_' . strtolower($itemtype);
        
        return isset($rights[$col]) && $rights[$col];
    }

    /**
     * Hooks for Profile form
     */
    function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
        if ($item->getType() == 'Profile') {
            return self::createTabEntry(
                'FlowBPMN',
                0,
                $item::getType(),
                'ti ti-sitemap'
            );
        }
        return '';
    }

    static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
        if ($item->getType() == 'Profile') {
            self::showProfileForm($item);
            return true;
        }
        return false;
    }

    static function showProfileForm($profile) {
        global $DB, $CFG_GLPI;
        
        $rights = self::getProfileRights($profile->getID());
        $plugin_profile_id = $rights['id'] ?? 0;
        
        if (empty($rights)) {
             // Should not happen if installed correctly, but handle gracefully
             // ... defaults ...
        }
        
        echo "<form name='form_flowbpmn_profile' action='" . $CFG_GLPI["root_doc"] . "/plugins/flowbpmn/front/profile.form.php' method='post'>";
        echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);
        echo "<input type='hidden' name='profiles_id' value='" . $profile->getID() . "'>";
        echo Html::hidden('id', ['value' => $plugin_profile_id]); // ID of the plugin profile record
        
        echo "<div class='spaced'>";
        echo "<table class='tab_cadre_fixe'>";
        echo "<tr class='tab_bg_1'><th colspan='5'>" . __('Permissões', 'flowbpmn') . "</th></tr>";
        
        $types = [
            'Ticket' => __('Ticket'),
            'Problem' => __('Problem'),
            'Change' => __('Change')
        ];
        
        echo "<tr class='tab_bg_2'><td></td>";
        foreach ($types as $type => $typeLabel) {
            echo "<th>$typeLabel</th>";
        }
        echo "</tr>";
        
        $actions = [
            'view' => _x('action', 'Visualizar', 'flowbpmn'),
            'edit' => _x('action', 'Editar', 'flowbpmn'),
            'delete' => _x('action', 'Excluir', 'flowbpmn'),
            'restore' => _x('action', 'Restaurar', 'flowbpmn')
        ];
        
        foreach ($actions as $action => $label) {
            echo "<tr class='tab_bg_2'>";
            echo "<td><b>$label</b></td>";
            
            foreach (array_keys($types) as $type) {
                $col = 'can_' . $action . '_' . strtolower($type);
                $val = $rights[$col] ?? 0;
                
                echo "<td class='center'>";
                Html::showCheckbox([
                    'name' => $col,
                    'checked' => $val,
                    'value' => 1
                ]);
                echo "</td>";
            }
            echo "</tr>";
        }
        
        echo "</table>";
        echo "</div>";
        
        if (Session::haveRight('profile', UPDATE)) {
            echo "<div class='center'>";
            echo Html::submit(_sx('button', 'Save'), ['name' => 'update', 'class' => 'btn btn-primary']);
            echo "</div>";
        }
        
        echo Html::closeForm();
    }
}
