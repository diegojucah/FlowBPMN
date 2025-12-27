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
        return 'ti ti-lock';
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
            return 'FlowBPMN';
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
        global $DB;
        
        $rights = self::getProfileRights($profile->getID());
        
        if (empty($rights)) {
            $rights = [
                'can_view_ticket' => 0, 'can_edit_ticket' => 0, 'can_delete_ticket' => 0, 'can_restore_ticket' => 0,
                'can_view_problem' => 0, 'can_edit_problem' => 0, 'can_delete_problem' => 0, 'can_restore_problem' => 0,
                'can_view_change' => 0, 'can_edit_change' => 0, 'can_delete_change' => 0, 'can_restore_change' => 0
            ];
        }
        
        echo "<div class='spaced'>";
        echo "<table class='tab_cadre_fixe'>";
        echo "<tr class='tab_bg_1'><th colspan='5'>FlowBPMN Rights</th></tr>";
        
        $types = ['Ticket', 'Problem', 'Change'];
        echo "<tr class='tab_bg_2'><td></td>";
        foreach ($types as $type) {
            echo "<th>$type</th>";
        }
        echo "</tr>";
        
        $actions = [
            'view' => 'View',
            'edit' => 'Edit',
            'delete' => 'Delete',
            'restore' => 'Restore'
        ];
        
        foreach ($actions as $action => $label) {
            echo "<tr class='tab_bg_2'>";
            echo "<td><b>$label</b></td>";
            
            foreach ($types as $type) {
                $col = 'can_' . $action . '_' . strtolower($type);
                $val = $rights[$col] ?? 0;
                echo "<td class='center'>";
                echo ($val ? "<i class='ti ti-check text-success'></i>" : "<i class='ti ti-x text-danger'></i>");
                echo "</td>";
            }
            echo "</tr>";
        }
        
        echo "</table>";
        echo "</div>";
    }
}
