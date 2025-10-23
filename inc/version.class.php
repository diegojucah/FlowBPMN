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
 * Version class - Manages BPMN flow versions
 */
class PluginFlowbpmnVersion extends CommonDBTM {
    
    static function getTypeName($nb = 0) {
        return _n('Flow Version', 'Flow Versions', $nb, 'flowbpmn');
    }
    
    /**
     * Create a new version from current flow
     */
    static function createVersion($flow_id, $flowData) {
        global $DB;
        
        if (!isset($_SESSION['glpi_currenttime'])) {
            $_SESSION['glpi_currenttime'] = date('Y-m-d H:i:s');
        }
        
        // Get next version number
        $iterator = $DB->request([
            'SELECT' => 'MAX(version_number) as max_version',
            'FROM'   => self::getTable(),
            'WHERE'  => ['plugin_flowbpmn_flows_id' => $flow_id]
        ]);
        
        $maxVersion = 0;
        if (count($iterator)) {
            $result = $iterator->current();
            $maxVersion = $result['max_version'] ?? 0;
        }
        
        $nextVersion = $maxVersion + 1;
        
        // Create version record
        $version = new self();
        $input = [
            'plugin_flowbpmn_flows_id' => $flow_id,
            'version_number' => $nextVersion,
            'name' => $flowData['name'] ?? '',
            'comment' => sprintf(__('Version %d', 'flowbpmn'), $nextVersion),
            'bpmn_xml' => $flowData['bpmn_xml'] ?? '',
            'svg_content' => $flowData['svg_content'] ?? '',
            'users_id' => Session::getLoginUserID(),
            'date_creation' => $_SESSION['glpi_currenttime']
        ];
        
        $versionId = $version->add($input);
        
        // Clean old versions if needed
        if ($versionId) {
            self::cleanOldVersions($flow_id);
        }
        
        return $versionId;
    }
    
    /**
     * Get all versions of a flow
     */
    static function getVersions($flow_id) {
        global $DB;
        
        $versions = [];
        $iterator = $DB->request([
            'FROM'  => self::getTable(),
            'WHERE' => ['plugin_flowbpmn_flows_id' => $flow_id],
            'ORDER' => 'version_number DESC'
        ]);
        
        foreach ($iterator as $data) {
            $versions[] = $data;
        }
        
        return $versions;
    }
    
    /**
     * Get specific version
     */
    function getVersion($flow_id, $version_number) {
        global $DB;
        
        $iterator = $DB->request([
            'FROM'  => self::getTable(),
            'WHERE' => [
                'plugin_flowbpmn_flows_id' => $flow_id,
                'version_number' => $version_number
            ],
            'LIMIT' => 1
        ]);
        
        if (count($iterator)) {
            return $iterator->current();
        }
        
        return null;
    }
    
    /**
     * Restore a specific version
     */
    static function restoreVersion($flow_id, $version_id) {
        
        $version = new self();
        if (!$version->getFromDB($version_id)) {
            return false;
        }
        
        // Verify version belongs to flow
        if ($version->fields['plugin_flowbpmn_flows_id'] != $flow_id) {
            return false;
        }
        
        // Update flow with version data
        $flow = new PluginFlowbpmnFlow();
        if (!$flow->getFromDB($flow_id)) {
            return false;
        }
        
        // Save current state as version before restoring
        self::createVersion($flow_id, $flow->fields);
        
        // Restore version
        return $flow->update([
            'id' => $flow_id,
            'name' => $version->fields['name'],
            'bpmn_xml' => $version->fields['bpmn_xml'],
            'svg_content' => $version->fields['svg_content'],
            'date_mod' => $_SESSION['glpi_currenttime']
        ]);
    }
    
    /**
     * Clean old versions based on config
     */
    static function cleanOldVersions($flow_id) {
        global $DB;
        
        $config = new PluginFlowbpmnConfig();
        $maxVersions = $config->getConfig('max_versions_per_item');
        
        if ($maxVersions <= 0) {
            return; // No limit
        }
        
        // Count current versions
        $count = countElementsInTable(
            self::getTable(),
            ['plugin_flowbpmn_flows_id' => $flow_id]
        );
        
        if ($count <= $maxVersions) {
            return; // Under limit
        }
        
        // Delete old versions
        $toDelete = $count - $maxVersions;
        
        $iterator = $DB->request([
            'SELECT' => 'id',
            'FROM'   => self::getTable(),
            'WHERE'  => ['plugin_flowbpmn_flows_id' => $flow_id],
            'ORDER'  => 'version_number ASC',
            'LIMIT'  => $toDelete
        ]);
        
        $version = new self();
        foreach ($iterator as $data) {
            $version->delete(['id' => $data['id']]);
        }
    }
    
    /**
     * Get version count for a flow
     */
    static function countVersions($flow_id) {
        return countElementsInTable(
            self::getTable(),
            ['plugin_flowbpmn_flows_id' => $flow_id]
        );
    }
}
