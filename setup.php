<?php

/**
 * -------------------------------------------------------------------------
 * FlowBPMN Plugin for GLPI
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of FlowBPMN.
 *
 * FlowBPMN is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * FlowBPMN is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with FlowBPMN. If not, see <http://www.gnu.org/licenses/>.
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2024 by Kactux Tecnologia
 * @license   GPLv3 https://www.gnu.org/licenses/gpl-3.0.html
 * @link      https://github.com/diegojucah/FlowBPMN
 * -------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

define('PLUGIN_FLOWBPMN_VERSION', '1.0.0');
define('PLUGIN_FLOWBPMN_MIN_GLPI', '11.0.0');
define('PLUGIN_FLOWBPMN_MAX_GLPI', '11.99.99');

/**
 * Standard GLPI Hook for profile update
 * Recovers data from the standard profile form
 */
// This logic is now handled in PluginFlowbpmnProfile::updateProfileRight via hook


/**
 * Initialize plugin
 */
function plugin_init_flowbpmn() {
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS['csrf_compliant']['flowbpmn'] = true;

    $plugin = new Plugin();
    if ($plugin->isInstalled('flowbpmn') && $plugin->isActivated('flowbpmn')) {

        // Load translations - simplified approach
        if (isset($_SESSION['glpilanguage'])) {
            global $LANG; // Declare $LANG as global
            
            $plugin_dir = Plugin::getPhpDir('flowbpmn', false);
            $locale = $_SESSION['glpilanguage'];

            // Try to load locale file
            $locale_file = $plugin_dir . '/locales/' . $locale . '.php';
            if (file_exists($locale_file)) {
                include_once($locale_file);
            } else {
                // Fallback to pt_BR
                $locale_file = $plugin_dir . '/locales/pt_BR.php';
                if (file_exists($locale_file)) {
                    include_once($locale_file);
                }
            }
        }

        // Register plugin classes
        Plugin::registerClass('PluginFlowbpmnFlow', [
            'addtabon' => ['Ticket', 'Problem', 'Change']
        ]);

        Plugin::registerClass('PluginFlowbpmnProfile', [
            'addtabon' => ['Profile']
        ]);

        Plugin::registerClass('PluginFlowbpmnVersion');
        Plugin::registerClass('PluginFlowbpmnTemplate');

        // Add CSS
        $PLUGIN_HOOKS['add_css']['flowbpmn'] = ['css/flowbpmn.css'];

        // Add JavaScript with cache buster
        // JS
        $PLUGIN_HOOKS['add_javascript']['flowbpmn'] = ['js/flowbpmn.js?v=' . time()]; // Force cache refresh during dev

        // Hook for saving Profile rights
        // GLOBAL LISTENER: Catch ALL updates (Fallback)
        $PLUGIN_HOOKS['item_update']['flowbpmn'] = 'PluginFlowbpmnProfile::updateProfileRight';

    }
    
    // FORCE SAVE ON INIT
    // If the Hook system fails (common in some envs), we catch the POST manually here.
    if (isset($_POST['_glpi_plugin_flowbpmn_marker']) && isset($_POST['profiles_id'])) {
        $log_file = '/tmp/MANUAL_DEBUG.log';
        if (!file_exists($log_file)) { touch($log_file); chmod($log_file, 0777); }
        error_log(date('Y-m-d H:i:s') . " - FlowBPMN: Manual Init Detection! Profile ID: " . $_POST['profiles_id'] . "\n", 3, $log_file);
        
        // Ensure class is loaded
        if (!class_exists('PluginFlowbpmnProfile')) {
             // Try to load it manually if autoloader missed it
             // Assume standard path
             $cls_file = __DIR__ . '/inc/profile.class.php';
             if (file_exists($cls_file)) {
                 include_once($cls_file);
             }
        }
        
        if (class_exists('PluginFlowbpmnProfile')) {
            $prof = new Profile();
            $prof->fields['id'] = $_POST['profiles_id'];
            // We spoof the object to satisfy the type hint
            PluginFlowbpmnProfile::updateProfileRight($prof);
        } else {
             error_log("FlowBPMN: FATAL - Class PluginFlowbpmnProfile not found in Init.\n", 3, $log_file);
        }
    }
}

/**
 * Get plugin version
 */
function plugin_version_flowbpmn() {
    return [
        'name'           => 'FlowBPMN',
        'version'        => PLUGIN_FLOWBPMN_VERSION,
        'author'         => '<a href="https://github.com/diegojucah/FlowBPMN">Kactux Tecnologia</a>',
        'license'        => 'GPLv3+',
        'homepage'       => 'https://github.com/diegojucah/FlowBPMN',
        'requirements'   => [
            'glpi' => [
                'min' => PLUGIN_FLOWBPMN_MIN_GLPI,
                'max' => PLUGIN_FLOWBPMN_MAX_GLPI,
            ],
            'php' => [
                'min' => '7.4',
            ]
        ]
    ];
}

/**
 * Check prerequisites before installation
 */
function plugin_flowbpmn_check_prerequisites() {
    // Check GLPI version
    if (!method_exists('Plugin', 'checkGlpiVersion')) {
        $version = rtrim(GLPI_VERSION, '-dev');
    } else {
        $version = GLPI_VERSION;
    }
    
    if (version_compare($version, PLUGIN_FLOWBPMN_MIN_GLPI, 'lt') 
        || version_compare($version, PLUGIN_FLOWBPMN_MAX_GLPI, 'gt')) {
        echo sprintf(
            'This plugin requires GLPI version between %s and %s.',
            PLUGIN_FLOWBPMN_MIN_GLPI,
            PLUGIN_FLOWBPMN_MAX_GLPI
        );
        return false;
    }
    
    // Check PHP version - Compatible with GLPI 10.x and 11.x
    if (version_compare(PHP_VERSION, '7.4', '<')) {
        echo 'This plugin requires PHP 7.4 or higher.';
        return false;
    }
    
    return true;
}

/**
 * Check plugin configuration
 */
function plugin_flowbpmn_check_config($verbose = false) {
    return true;
}

/**
 * Install plugin
 */
function plugin_flowbpmn_install() {
    global $DB;
    
    try {
        // Create flows table with all mandatory GLPI fields
        if (!$DB->tableExists('glpi_plugin_flowbpmn_flows')) {
        $query = "CREATE TABLE `glpi_plugin_flowbpmn_flows` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `entities_id` int unsigned NOT NULL DEFAULT '0',
            `is_recursive` tinyint NOT NULL DEFAULT '0',
            `items_id` int unsigned NOT NULL DEFAULT '0',
            `itemtype` varchar(100) NOT NULL,
            `name` varchar(255) DEFAULT NULL,
            `comment` text,
            `bpmn_xml` longtext,
            `svg_content` longtext,
            `is_active` tinyint NOT NULL DEFAULT '1',
            `is_deleted` tinyint NOT NULL DEFAULT '0',
            `users_id` int unsigned NOT NULL DEFAULT '0',
            `users_id_tech` int unsigned NOT NULL DEFAULT '0',
            `groups_id_tech` int unsigned NOT NULL DEFAULT '0',
            `date_creation` timestamp NULL DEFAULT NULL,
            `date_mod` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `item` (`itemtype`, `items_id`),
            KEY `entities_id` (`entities_id`),
            KEY `is_recursive` (`is_recursive`),
            KEY `is_active` (`is_active`),
            KEY `is_deleted` (`is_deleted`),
            KEY `users_id` (`users_id`),
            KEY `users_id_tech` (`users_id_tech`),
            KEY `groups_id_tech` (`groups_id_tech`),
            KEY `date_creation` (`date_creation`),
            KEY `date_mod` (`date_mod`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC";

        $DB->doQuery($query) or die($DB->error());
    }
    
    // Create versions table with foreign key
    if (!$DB->tableExists('glpi_plugin_flowbpmn_versions')) {
        $query = "CREATE TABLE `glpi_plugin_flowbpmn_versions` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `plugin_flowbpmn_flows_id` int unsigned NOT NULL,
            `version_number` int unsigned NOT NULL DEFAULT '1',
            `name` varchar(255) DEFAULT NULL,
            `comment` text,
            `bpmn_xml` longtext,
            `svg_content` longtext,
            `users_id` int unsigned NOT NULL DEFAULT '0',
            `date_creation` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `plugin_flowbpmn_flows_id` (`plugin_flowbpmn_flows_id`),
            KEY `version_number` (`version_number`),
            KEY `users_id` (`users_id`),
            KEY `date_creation` (`date_creation`),
            CONSTRAINT `glpi_plugin_flowbpmn_versions_ibfk_1`
                FOREIGN KEY (`plugin_flowbpmn_flows_id`)
                REFERENCES `glpi_plugin_flowbpmn_flows` (`id`)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC";

        $DB->doQuery($query) or die($DB->error());
    }
    
    // Create config table with enhanced settings
    if (!$DB->tableExists('glpi_plugin_flowbpmn_configs')) {
        $query = "CREATE TABLE `glpi_plugin_flowbpmn_configs` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `enable_auto_attach_image` tinyint NOT NULL DEFAULT '1',
            `enable_auto_attach_xml` tinyint NOT NULL DEFAULT '0',
            `max_versions_per_item` int unsigned NOT NULL DEFAULT '10',
            `enable_version_cleanup` tinyint NOT NULL DEFAULT '1',
            `enable_export_bpmn` tinyint NOT NULL DEFAULT '1',
            `enable_export_svg` tinyint NOT NULL DEFAULT '1',
            `enable_export_png` tinyint NOT NULL DEFAULT '1',
            `default_canvas_height` int unsigned NOT NULL DEFAULT '800',
            `enable_grid` tinyint NOT NULL DEFAULT '1',
            `grid_size` int unsigned NOT NULL DEFAULT '10',
            `enable_notifications` tinyint NOT NULL DEFAULT '0',
            `date_mod` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC";

        $DB->doQuery($query) or die($DB->error());

        // Insert default config
        if (!isset($_SESSION['glpi_currenttime'])) {
            $_SESSION['glpi_currenttime'] = date('Y-m-d H:i:s');
        }

        $DB->insert('glpi_plugin_flowbpmn_configs', [
            'id' => 1,
            'enable_auto_attach_image' => 1,
            'enable_auto_attach_xml' => 0,
            'max_versions_per_item' => 10,
            'enable_version_cleanup' => 1,
            'enable_export_bpmn' => 1,
            'enable_export_svg' => 1,
            'enable_export_png' => 1,
            'default_canvas_height' => 800,
            'enable_grid' => 1,
            'grid_size' => 10,
            'enable_notifications' => 0,
            'date_mod' => $_SESSION['glpi_currenttime']
        ]);
    }

    // Create templates table (Priority 4.1)
    if (!$DB->tableExists('glpi_plugin_flowbpmn_templates')) {
        $query = "CREATE TABLE `glpi_plugin_flowbpmn_templates` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `entities_id` int unsigned NOT NULL DEFAULT '0',
            `is_recursive` tinyint NOT NULL DEFAULT '0',
            `name` varchar(255) DEFAULT NULL,
            `comment` text,
            `bpmn_xml` longtext,
            `svg_content` longtext,
            `is_active` tinyint NOT NULL DEFAULT '1',
            `is_public` tinyint NOT NULL DEFAULT '0',
            `users_id` int unsigned NOT NULL DEFAULT '0',
            `date_creation` timestamp NULL DEFAULT NULL,
            `date_mod` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `entities_id` (`entities_id`),
            KEY `is_recursive` (`is_recursive`),
            KEY `is_public` (`is_public`),
            KEY `users_id` (`users_id`),
            KEY `date_mod` (`date_mod`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC";

        $DB->doQuery($query) or die($DB->error());
    } else {
        // Migration: Add svg_content if missing
        if (!$DB->fieldExists('glpi_plugin_flowbpmn_templates', 'svg_content')) {
            $query = "ALTER TABLE `glpi_plugin_flowbpmn_templates` ADD COLUMN `svg_content` longtext";
            $DB->doQuery($query) or die($DB->error());
        }
    }
    
    // Create profiles table with foreign key
    if (!$DB->tableExists('glpi_plugin_flowbpmn_profiles')) {
        $query = "CREATE TABLE `glpi_plugin_flowbpmn_profiles` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `profiles_id` int unsigned NOT NULL DEFAULT '0',
            `can_view_ticket` tinyint NOT NULL DEFAULT '0',
            `can_edit_ticket` tinyint NOT NULL DEFAULT '0',
            `can_delete_ticket` tinyint NOT NULL DEFAULT '0',
            `can_restore_ticket` tinyint NOT NULL DEFAULT '0',
            `can_view_problem` tinyint NOT NULL DEFAULT '0',
            `can_edit_problem` tinyint NOT NULL DEFAULT '0',
            `can_delete_problem` tinyint NOT NULL DEFAULT '0',
            `can_restore_problem` tinyint NOT NULL DEFAULT '0',
            `can_view_change` tinyint NOT NULL DEFAULT '0',
            `can_edit_change` tinyint NOT NULL DEFAULT '0',
            `can_delete_change` tinyint NOT NULL DEFAULT '0',
            `can_restore_change` tinyint NOT NULL DEFAULT '0',
            PRIMARY KEY (`id`),
            UNIQUE KEY `profiles_id` (`profiles_id`),
            CONSTRAINT `glpi_plugin_flowbpmn_profiles_ibfk_1`
                FOREIGN KEY (`profiles_id`)
                REFERENCES `glpi_profiles` (`id`)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC";

        $DB->doQuery($query) or die($DB->error());
        
        // Set default rights for existing profiles - Initialize directly to avoid class loading issues
        $profiles = $DB->request(['FROM' => 'glpi_profiles']);
        foreach ($profiles as $profile) {
            $profileName = strtolower($profile['name']);
            
            // Determine default rights based on profile
            if ($profileName == 'super-admin' || $profileName == 'admin') {
                $rights = ['view' => 1, 'edit' => 1, 'delete' => 1, 'restore' => 1];
            } elseif ($profileName == 'technician') {
                $rights = ['view' => 1, 'edit' => 1, 'delete' => 0, 'restore' => 0];
            } else {
                $rights = ['view' => 1, 'edit' => 0, 'delete' => 0, 'restore' => 0];
            }
            
            $DB->insert('glpi_plugin_flowbpmn_profiles', [
                'profiles_id' => $profile['id'],
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
        
        return true;
        
    } catch (Exception $e) {
        error_log("flowBPMN install error: " . $e->getMessage());
        return false;
    }
}

/**
 * Uninstall plugin
 */
function plugin_flowbpmn_uninstall() {
    global $DB;
    
    try {
        // Disable foreign key checks temporarily
        $DB->doQuery("SET FOREIGN_KEY_CHECKS = 0");
        
        $tables = [
            'glpi_plugin_flowbpmn_versions',
            'glpi_plugin_flowbpmn_flows',
            'glpi_plugin_flowbpmn_templates',
            'glpi_plugin_flowbpmn_profiles',
            'glpi_plugin_flowbpmn_configs'
        ];
        
        foreach ($tables as $table) {
            if ($DB->tableExists($table)) {
                $DB->doQuery("DROP TABLE IF EXISTS `$table`");
            }
        }
        
        // Re-enable foreign key checks
        $DB->doQuery("SET FOREIGN_KEY_CHECKS = 1");
        
        return true;
        
    } catch (Exception $e) {
        error_log("flowBPMN uninstall error: " . $e->getMessage());
        // Force re-enable FK checks
        try {
            $DB->doQuery("SET FOREIGN_KEY_CHECKS = 1");
        } catch (Exception $e2) {
            // Ignore
        }
        return false;
    }
}
