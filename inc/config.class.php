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
 * Config class - Manages plugin configuration
 */
class PluginFlowbpmnConfig extends CommonDBTM {
    
    static $rightname = 'config';
    
    static function getTypeName($nb = 0) {
        return 'Configuração do flowBPMN';
    }
    
    static function getMenuName() {
        return 'flowBPMN';
    }
    
    static function getMenuContent() {
        $menu = [];

        $menu['title'] = self::getMenuName();
        $menu['page']  = '/plugins/flowbpmn/front/config.form.php';

        // Compatibility with both GLPI 10.x and 11.x
        if (version_compare(GLPI_VERSION, '11.0', 'ge')) {
            $menu['icon'] = 'ti ti-git-fork';
        } else {
            $menu['icon'] = 'fas fa-project-diagram';
        }

        return $menu;
    }
    
    /**
     * Get configuration
     */
    static function getConfigValues() {
        global $DB;
        
        $iterator = $DB->request([
            'FROM'  => 'glpi_plugin_flowbpmn_configs',
            'WHERE' => ['id' => 1],
            'LIMIT' => 1
        ]);
        
        if (count($iterator)) {
            return $iterator->current();
        }
        
        // Return defaults if not found
        return [
            'enable_auto_attach_image' => 1,
            'max_versions_per_item' => 10,
            'enable_export_bpmn' => 1,
            'enable_export_svg' => 1,
            'enable_export_png' => 1
        ];
    }
    
    /**
     * Get specific config value
     */
    function getConfig($key) {
        $config = self::getConfigValues();
        return $config[$key] ?? null;
    }
    
    /**
     * Update configuration
     */
    static function updateConfig($input) {
        global $DB;
        
        if (!isset($_SESSION['glpi_currenttime'])) {
            $_SESSION['glpi_currenttime'] = date('Y-m-d H:i:s');
        }
        
        $data = [
            'id' => 1,
            'enable_auto_attach_image' => isset($input['enable_auto_attach_image']) ? 1 : 0,
            'max_versions_per_item' => (int)($input['max_versions_per_item'] ?? 10),
            'enable_export_bpmn' => isset($input['enable_export_bpmn']) ? 1 : 0,
            'enable_export_svg' => isset($input['enable_export_svg']) ? 1 : 0,
            'enable_export_png' => isset($input['enable_export_png']) ? 1 : 0,
            'date_mod' => $_SESSION['glpi_currenttime']
        ];
        
        return $DB->update('glpi_plugin_flowbpmn_configs', $data, ['id' => 1]);
    }
    
    /**
     * Show configuration form
     */
    function showForm($ID = 0, array $options = []) {

        if (!Session::haveRight('config', UPDATE)) {
            return false;
        }

        $config = self::getConfigValues();

        // Detect GLPI version for proper URL handling
        $plugin_dir = Plugin::getWebDir('flowbpmn', false);

        echo "<div class='center'>";
        echo "<form name='form' action='{$plugin_dir}/front/config.form.php' method='post'>";
        echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);

        echo "<table class='tab_cadre_fixe'>";
        echo "<tr><th colspan='2'><i class='ti ti-git-fork'></i> " . __('BPMN Flow Configuration', 'flowbpmn') . "</th></tr>";

        // Auto attach image
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Automatically attach BPMN diagram to item', 'flowbpmn') . "</td>";
        echo "<td>";
        Dropdown::showYesNo('enable_auto_attach_image', $config['enable_auto_attach_image']);
        echo "</td></tr>";

        // Max versions
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Maximum versions per flow', 'flowbpmn') . "</td>";
        echo "<td>";
        echo "<input type='number' name='max_versions_per_item' class='form-control' value='" .
             $config['max_versions_per_item'] . "' min='0' max='100' style='width: 100px; display: inline-block;'>";
        echo " <span class='text-muted'>" . __('(0 = unlimited)', 'flowbpmn') . "</span>";
        echo "</td></tr>";

        // Export options
        echo "<tr><th colspan='2'>" . __('Export Options', 'flowbpmn') . "</th></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Enable BPMN export', 'flowbpmn') . "</td>";
        echo "<td>";
        Dropdown::showYesNo('enable_export_bpmn', $config['enable_export_bpmn']);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Enable SVG export', 'flowbpmn') . "</td>";
        echo "<td>";
        Dropdown::showYesNo('enable_export_svg', $config['enable_export_svg']);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Enable PNG export', 'flowbpmn') . "</td>";
        echo "<td>";
        Dropdown::showYesNo('enable_export_png', $config['enable_export_png']);
        echo "</td></tr>";

        // Submit button
        echo "<tr class='tab_bg_2'>";
        echo "<td class='center' colspan='2'>";
        echo "<input type='submit' name='update' class='btn btn-primary' value='" . _sx('button', 'Save') . "'>";
        echo "</td>";
        echo "</tr>";

        echo "</table>";

        Html::closeForm();
        echo "</div>";
    }
}

