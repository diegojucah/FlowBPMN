<?php

/**
 * -------------------------------------------------------------------------
 * flowBPMN Plugin for GLPI
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of flowBPMN.
 *
 * flowBPMN is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * flowBPMN is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with flowBPMN. If not, see <http://www.gnu.org/licenses/>.
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2024 by KactuX
 * @license   GPLv3 https://www.gnu.org/licenses/gpl-3.0.html
 * @link      https://github.com/diegojucah/pluginBPMN
 * -------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Profile class - Manages permissions for BPMN flows
 */
class PluginFlowbpmnProfile extends CommonDBTM {
    
    static $rightname = 'profile';
    
    static function getTypeName($nb = 0) {
        return __('BPMN Flow Rights', 'flowbpmn');
    }
    
    /**
     * Initialize profiles with default rights
     */
    static function initProfile() {
        global $DB;
        
        $profile = new Profile();
        
        // Get all profiles
        $profiles = $DB->request([
            'FROM' => Profile::getTable()
        ]);
        
        foreach ($profiles as $profileData) {
            
            $profileId = $profileData['id'];
            
            // Check if rights already exist
            $existing = countElementsInTable(
                self::getTable(),
                ['profiles_id' => $profileId]
            );
            
            if ($existing > 0) {
                continue;
            }
            
            // Default rights based on profile name
            $rights = self::getDefaultRights($profileData['name']);
            
            $DB->insert(self::getTable(), [
                'profiles_id' => $profileId,
                'can_view_ticket' => $rights['view'],
                'can_edit_ticket' => $rights['edit'],
                'can_delete_ticket' => $rights['delete'],
                'can_restore_ticket' => $rights['restore'],
                'can_view_problem' => $rights['view'],
                'can_edit_problem' => $rights['edit'],
                'can_delete_problem' => $rights['delete'],
                'can_restore_problem' => $rights['restore'],
                'can_view_change' => $rights['view'],
                'can_edit_change' => $rights['edit'],
                'can_delete_change' => $rights['delete'],
                'can_restore_change' => $rights['restore']
            ]);
        }
    }
    
    /**
     * Get default rights for a profile
     */
    static function getDefaultRights($profileName) {
        
        $profileName = strtolower($profileName);
        
        // Super-Admin has all rights
        if ($profileName == 'super-admin') {
            return [
                'view' => 1,
                'edit' => 1,
                'delete' => 1,
                'restore' => 1
            ];
        }
        
        // Admin has all rights except super operations
        if ($profileName == 'admin') {
            return [
                'view' => 1,
                'edit' => 1,
                'delete' => 1,
                'restore' => 1
            ];
        }
        
        // Technician can view and edit
        if ($profileName == 'technician') {
            return [
                'view' => 1,
                'edit' => 1,
                'delete' => 0,
                'restore' => 0
            ];
        }
        
        // Default: only view
        return [
            'view' => 1,
            'edit' => 0,
            'delete' => 0,
            'restore' => 0
        ];
    }
    
    /**
     * Get tab name for Profile item
     */
    function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
        
        if ($item->getType() == 'Profile') {
            return self::createTabEntry(self::getTypeName());
        }
        
        return '';
    }
    
    /**
     * Display tab content for Profile
     */
    static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
        
        if ($item->getType() == 'Profile') {
            $profile = new self();
            $profile->showForm($item->getID());
        }
        
        return true;
    }
    
    /**
     * Show rights form for a profile
     */
    function showForm($ID, array $options = []) {
        
        if (!Session::haveRight('profile', READ)) {
            return false;
        }
        
        $profiles_id = $ID;
        $canEdit = Session::haveRight('profile', UPDATE);
        
        $rights = self::getProfileRights($profiles_id);
        
        echo "<form method='post' action='" . Plugin::getPhpDir('flowbpmn') . "/front/profile.form.php'>";
        echo "<input type='hidden' name='profiles_id' value='$profiles_id'>";
        echo "<input type='hidden' name='_glpi_csrf_token' value='" . Session::getNewCSRFToken() . "'>";
        
        echo "<div class='spaced'>";
        echo "<table class='tab_cadre_fixe'>";
        
        echo "<tr><th colspan='5'>" . __('BPMN Flow Rights Management', 'flowbpmn') . "</th></tr>";
        
        echo "<tr>";
        echo "<th>" . __('Item Type', 'flowbpmn') . "</th>";
        echo "<th>" . __('View', 'flowbpmn') . "</th>";
        echo "<th>" . __('Edit', 'flowbpmn') . "</th>";
        echo "<th>" . __('Delete', 'flowbpmn') . "</th>";
        echo "<th>" . __('Restore', 'flowbpmn') . "</th>";
        echo "</tr>";
        
        // Ticket rights
        self::showRightRow('Ticket', 'ticket', $rights, $canEdit);
        
        // Problem rights
        self::showRightRow('Problem', 'problem', $rights, $canEdit);
        
        // Change rights
        self::showRightRow('Change', 'change', $rights, $canEdit);
        
        if ($canEdit) {
            echo "<tr>";
            echo "<td class='tab_bg_2 center' colspan='5'>";
            echo "<input type='submit' name='update' class='btn btn-primary' value='" . _sx('button', 'Save') . "'>";
            echo "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        echo "</div>";
        
        Html::closeForm();
    }
    
    /**
     * Update profile rights
     */
    static function updateProfileRights($input) {
        global $DB;
        
        if (!isset($input['profiles_id'])) {
            return false;
        }
        
        $profileId = (int)$input['profiles_id'];
        
        // Check if profile rights already exist
        $existing = countElementsInTable(
            self::getTable(),
            ['profiles_id' => $profileId]
        );
        
        $data = [
            'profiles_id' => $profileId,
            'can_view_ticket' => isset($input['can_view_ticket']) ? 1 : 0,
            'can_edit_ticket' => isset($input['can_edit_ticket']) ? 1 : 0,
            'can_delete_ticket' => isset($input['can_delete_ticket']) ? 1 : 0,
            'can_restore_ticket' => isset($input['can_restore_ticket']) ? 1 : 0,
            'can_view_problem' => isset($input['can_view_problem']) ? 1 : 0,
            'can_edit_problem' => isset($input['can_edit_problem']) ? 1 : 0,
            'can_delete_problem' => isset($input['can_delete_problem']) ? 1 : 0,
            'can_restore_problem' => isset($input['can_restore_problem']) ? 1 : 0,
            'can_view_change' => isset($input['can_view_change']) ? 1 : 0,
            'can_edit_change' => isset($input['can_edit_change']) ? 1 : 0,
            'can_delete_change' => isset($input['can_delete_change']) ? 1 : 0,
            'can_restore_change' => isset($input['can_restore_change']) ? 1 : 0
        ];
        
        if ($existing > 0) {
            // Update existing
            return $DB->update(
                self::getTable(),
                $data,
                ['profiles_id' => $profileId]
            );
        } else {
            // Insert new
            return $DB->insert(self::getTable(), $data);
        }
    }
    
    /**
     * Show a single right row
     */
    private static function showRightRow($label, $type, $rights, $canEdit) {
        
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __($label) . "</td>";
        
        foreach (['view', 'edit', 'delete', 'restore'] as $action) {
            $fieldName = "can_{$action}_{$type}";
            $checked = $rights[$fieldName] ?? 0;
            
            echo "<td class='center'>";
            if ($canEdit) {
                echo "<input type='checkbox' name='{$fieldName}' value='1' " . 
                     ($checked ? 'checked' : '') . ">";
            } else {
                echo ($checked ? __('Yes') : __('No'));
            }
            echo "</td>";
        }
        
        echo "</tr>";
    }
    
    /**
     * Get rights for a profile
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
     * Check if current user can view flows for itemtype
     */
    static function canView($itemtype) {
        
        if (!isset($_SESSION['glpiactiveprofile']['id'])) {
            return false;
        }
        
        $profileId = $_SESSION['glpiactiveprofile']['id'];
        $rights = self::getProfileRights($profileId);
        
        $type = strtolower($itemtype);
        $field = "can_view_{$type}";
        
        return isset($rights[$field]) && $rights[$field];
    }
    
    /**
     * Check if current user can edit flows for itemtype
     */
    static function canEdit($itemtype) {
        
        if (!isset($_SESSION['glpiactiveprofile']['id'])) {
            return false;
        }
        
        $profileId = $_SESSION['glpiactiveprofile']['id'];
        $rights = self::getProfileRights($profileId);
        
        $type = strtolower($itemtype);
        $field = "can_edit_{$type}";
        
        return isset($rights[$field]) && $rights[$field];
    }
    
    /**
     * Check if current user can delete flows for itemtype
     */
    static function canDelete($itemtype) {
        
        if (!isset($_SESSION['glpiactiveprofile']['id'])) {
            return false;
        }
        
        $profileId = $_SESSION['glpiactiveprofile']['id'];
        $rights = self::getProfileRights($profileId);
        
        $type = strtolower($itemtype);
        $field = "can_delete_{$type}";
        
        return isset($rights[$field]) && $rights[$field];
    }
    
    /**
     * Check if current user can restore flow versions for itemtype
     */
    static function canRestore($itemtype) {
        
        if (!isset($_SESSION['glpiactiveprofile']['id'])) {
            return false;
        }
        
        $profileId = $_SESSION['glpiactiveprofile']['id'];
        $rights = self::getProfileRights($profileId);
        
        $type = strtolower($itemtype);
        $field = "can_restore_{$type}";
        
        return isset($rights[$field]) && $rights[$field];
    }
}
