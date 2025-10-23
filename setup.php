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

define('PLUGIN_FLOWBPMN_VERSION', '1.0.0');
define('PLUGIN_FLOWBPMN_MIN_GLPI', '11.0.0');
define('PLUGIN_FLOWBPMN_MAX_GLPI', '11.99.99');

/**
 * Initialize plugin
 */
function plugin_init_flowbpmn() {
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS['csrf_compliant']['flowbpmn'] = true;
    
    $plugin = new Plugin();
    if ($plugin->isInstalled('flowbpmn') && $plugin->isActivated('flowbpmn')) {
        
        // Register plugin classes
        Plugin::registerClass('PluginFlowbpmnFlow', [
            'addtabon' => ['Ticket', 'Problem', 'Change']
        ]);
        
        Plugin::registerClass('PluginFlowbpmnProfile', [
            'addtabon' => ['Profile']
        ]);
        
        Plugin::registerClass('PluginFlowbpmnConfig');
        
        // Configuration page
        $PLUGIN_HOOKS['config_page']['flowbpmn'] = 'front/config.form.php';
        
        // Add CSS and JS
        $PLUGIN_HOOKS['add_css']['flowbpmn'] = [
            'css/flowbpmn.css'
        ];
        
        $PLUGIN_HOOKS['add_javascript']['flowbpmn'] = [
            'js/flowbpmn.js'
        ];
        
        // Menu entry
        if (Session::haveRight('config', UPDATE)) {
            $PLUGIN_HOOKS['menu_toadd']['flowbpmn'] = ['config' => 'PluginFlowbpmnConfig'];
        }
    }
}

/**
 * Get plugin version
 */
function plugin_version_flowbpmn() {
    return [
        'name'           => 'BPMN Flow',
        'version'        => PLUGIN_FLOWBPMN_VERSION,
        'author'         => '<a href="https://github.com/diegojucah">KactuX</a>',
        'license'        => 'GPLv3+',
        'homepage'       => 'https://github.com/diegojucah/pluginBPMN',
        'requirements'   => [
            'glpi' => [
                'min' => PLUGIN_FLOWBPMN_MIN_GLPI,
                'max' => PLUGIN_FLOWBPMN_MAX_GLPI,
            ],
            'php' => [
                'min' => '8.1',
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
    
    // Check PHP version
    if (version_compare(PHP_VERSION, '8.1', '<')) {
        echo 'This plugin requires PHP 8.1 or higher.';
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
    
    $migration = new Migration(PLUGIN_FLOWBPMN_VERSION);
    
    // Create flows table
    if (!$DB->tableExists('glpi_plugin_flowbpmn_flows')) {
        $query = "CREATE TABLE `glpi_plugin_flowbpmn_flows` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `items_id` int unsigned NOT NULL DEFAULT '0',
            `itemtype` varchar(100) NOT NULL,
            `name` varchar(255) DEFAULT NULL,
            `bpmn_xml` longtext,
            `svg_content` longtext,
            `is_active` tinyint NOT NULL DEFAULT '1',
            `users_id` int unsigned NOT NULL DEFAULT '0',
            `date_creation` timestamp NULL DEFAULT NULL,
            `date_mod` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `item` (`itemtype`, `items_id`),
            KEY `users_id` (`users_id`),
            KEY `is_active` (`is_active`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC";
        
        $DB->queryOrDie($query, $DB->error());
    }
    
    // Create versions table
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
            KEY `users_id` (`users_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC";
        
        $DB->queryOrDie($query, $DB->error());
    }
    
    // Create config table
    if (!$DB->tableExists('glpi_plugin_flowbpmn_configs')) {
        $query = "CREATE TABLE `glpi_plugin_flowbpmn_configs` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `enable_auto_attach_image` tinyint NOT NULL DEFAULT '1',
            `max_versions_per_item` int unsigned NOT NULL DEFAULT '10',
            `enable_export_bpmn` tinyint NOT NULL DEFAULT '1',
            `enable_export_svg` tinyint NOT NULL DEFAULT '1',
            `enable_export_png` tinyint NOT NULL DEFAULT '1',
            `date_mod` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC";
        
        $DB->queryOrDie($query, $DB->error());
        
        // Insert default config
        $DB->insert('glpi_plugin_flowbpmn_configs', [
            'id' => 1,
            'enable_auto_attach_image' => 1,
            'max_versions_per_item' => 10,
            'enable_export_bpmn' => 1,
            'enable_export_svg' => 1,
            'enable_export_png' => 1,
            'date_mod' => $_SESSION['glpi_currenttime']
        ]);
    }
    
    // Create profiles table
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
            UNIQUE KEY `profiles_id` (`profiles_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC";
        
        $DB->queryOrDie($query, $DB->error());
        
        // Set default rights for existing profiles
        PluginFlowbpmnProfile::initProfile();
    }
    
    $migration->executeMigration();
    
    return true;
}

/**
 * Uninstall plugin
 */
function plugin_flowbpmn_uninstall() {
    global $DB;
    
    $tables = [
        'glpi_plugin_flowbpmn_flows',
        'glpi_plugin_flowbpmn_versions',
        'glpi_plugin_flowbpmn_configs',
        'glpi_plugin_flowbpmn_profiles'
    ];
    
    foreach ($tables as $table) {
        if ($DB->tableExists($table)) {
            $DB->queryOrDie("DROP TABLE IF EXISTS `$table`", $DB->error());
        }
    }
    
    return true;
}
