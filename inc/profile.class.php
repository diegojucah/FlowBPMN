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
     * Helper function to get translated text from $LANG array
     */
    private static function _t($key) {
        global $LANG;
        
        // Check if translations are loaded, if not, load them
        if (!isset($LANG['plugin_flowbpmn'])) {
            self::loadTranslations();
        }
        
        // Try to get from $LANG array first
        if (isset($LANG['plugin_flowbpmn'][$key])) {
            return $LANG['plugin_flowbpmn'][$key];
        }
        
        // Fallback to __() function
        $translated = __($key, 'flowbpmn');
        
        // If translation failed, return the key itself
        return ($translated === $key) ? $key : $translated;
    }
    
    /**
     * Load translations for current language
     */
    private static function loadTranslations() {
        global $LANG;
        
        // Get current language from session
        $locale = $_SESSION['glpilanguage'] ?? 'pt_BR';
        
        // Get plugin directory
        $plugin_dir = GLPI_ROOT . '/plugins/flowbpmn';
        
        // Try to load locale file
        $locale_file = $plugin_dir . '/locales/' . $locale . '.php';
        
        if (file_exists($locale_file)) {
            include $locale_file;
        } else {
            // Fallback to pt_BR
            $locale_file = $plugin_dir . '/locales/pt_BR.php';
            if (file_exists($locale_file)) {
                include $locale_file;
            }
        }
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

    /**
     * Update rights on Profile update
     * Hook: item_update
     */
    public static function updateProfileRight(CommonDBTM $item) {
        // DEBUG: Trace execution - FORCE LOG to /tmp
        $log_file = '/tmp/HOOK_DEBUG.log';
        if (!file_exists($log_file)) { touch($log_file); chmod($log_file, 0777); }
        error_log(date('Y-m-d H:i:s') . " - FlowBPMN Hook Triggered for " . $item->getType() . " ID " . $item->getID() . "\n", 3, $log_file);

        if ($item->getType() !== 'Profile') {
            error_log("- Not a Profile. Aborting.\n", 3, $log_file);
            return;
        }

        $profile_id = $item->getID();
        
        // Prepare input from $_POST
        // The fields are posted directly with the main form
        $input = [
            'profiles_id' => $profile_id
        ];

        // Debug POST
        error_log(date('Y-m-d H:i:s') . " - POST Data keys: " . implode(',', array_keys($_POST)) . "\n", 3, $log_file);

        // List of all rights we manage
        $types = ['ticket', 'problem', 'change'];
        $actions = ['view', 'edit', 'delete', 'restore'];
        
        // Check for our specific fields in POST
        // Note: Checkboxes not checked are not sent in POST, so we must assume 0 if missing?
        // But only if we are actually saving the form?
        // Hook receives the item after update. $_POST should be available.
        
        $has_updates = false;

        foreach ($types as $type) {
            foreach ($actions as $action) {
                $col = 'can_' . $action . '_' . $type;
                if (isset($_POST[$col])) {
                    $input[$col] = $_POST[$col];
                    $has_updates = true;
                }
            }
        }
        
        // Handling Unchecked Checkboxes:
        // A naive approach resets everything to 0 if not set.
        // A better approach checks if the user *saw* the form.
        // We can check if one of our known fields is present.
        // If at least one presence check passes, we assume the tab was active/submitted.
        // OR we just verify if this is a standard update.
        
        // Let's assume if 'can_view_ticket' key exists (even if 0/1) or check for a marker.
        // Since checkboxes don't send 0, we can add a hidden field in the form?
        if (isset($_POST['_glpi_plugin_flowbpmn_marker'])) {
             Toolbox::logInFile('php-errors', "FlowBPMN Hook: Marker found. Processing updates.\n");
             
             // Reset all to 0 first, then overwrite with POST
             foreach ($types as $type) {
                foreach ($actions as $action) {
                    $col = 'can_' . $action . '_' . $type;
                    $input[$col] = isset($_POST[$col]) ? 1 : 0;
                }
            }
            
            $profile = new self();
            
            // Check if record exists
            $existing = self::getProfileRights($profile_id);
            
            if (isset($existing['id'])) {
                $input['id'] = $existing['id'];
                $updated = $profile->update($input);
                Toolbox::logInFile('php-errors', "FlowBPMN Hook: Update Result: " . ($updated ? 'Success' : 'Fail') . "\n");
            } else {
                $added = $profile->add($input);
                Toolbox::logInFile('php-errors', "FlowBPMN Hook: Add Result: " . ($added ? 'SuccessId:'.$added : 'Fail') . "\n");
            }
        } else {
             Toolbox::logInFile('php-errors', "FlowBPMN Hook: Marker NOT found. Skipping.\n");
        }
    }

    static function showProfileForm($profile) {
        global $DB, $CFG_GLPI;
        
        $profile_id = $profile->getID();
        $rights = self::getProfileRights($profile_id);
        
        // Use standard GLPI form pattern (like Metabase plugin)
        echo '<form method="post" action="' . self::getFormURL() . '">';
        echo Html::hidden('profiles_id', ['value' => $profile_id]);
        echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);
        
        echo "<div class='spaced'>";
        echo "<table class='tab_cadre_fixe'>";
        echo "<tr class='tab_bg_1'><th colspan='4'>" . self::_t('Permissions') . "</th></tr>";
        
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
            'view' => self::_t('View'),
            'edit' => self::_t('Edit'),
            'delete' => self::_t('Delete'),
            'restore' => self::_t('Restore')
        ];
        
        foreach ($actions as $action => $label) {
            echo "<tr class='tab_bg_2'>";
            echo "<td><b>$label</b></td>";
            
            foreach (array_keys($types) as $type) {
                $col = 'can_' . $action . '_' . strtolower($type);
                $val = $rights[$col] ?? 0;
                
                echo "<td class='center'>";
                Html::showCheckbox([
                    'name'    => $col,
                    'checked' => $val,
                    'value'   => 1
                ]);
                echo "</td>";
            }
            echo "</tr>";
        }
        
        echo "</table>";
        echo "</div>";
        
        if (Session::haveRight('profile', UPDATE)) {
            echo "<div class='center' style='margin-top:10px;'>";
            echo Html::submit(_sx('button', 'Save'), [
                'name'  => 'update',
                'class' => 'btn btn-primary',
            ]);
            echo "</div>";
        }
        
        Html::closeForm();
    }
}
