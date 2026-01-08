<?php
declare(strict_types=1);

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

    static function getTable($classname = null) {
        return 'glpi_plugin_flowbpmn_versions';
    }

    static function getTypeName($nb = 0) {
        return _n('Flow Version', 'Flow Versions', $nb, 'flowbpmn');
    }
    
    /**
     * Create a new version from current flow
     */
    static function createVersion($flow_id, $flowData) {
        global $DB;
        
        error_log("flowBPMN DEBUG: createVersion START - flow_id=$flow_id");
        
        if (!isset($_SESSION['glpi_currenttime'])) {
            $_SESSION['glpi_currenttime'] = date('Y-m-d H:i:s');
        }
        
        // Get next version number - Use QueryExpression for MAX()
        error_log("flowBPMN DEBUG: Getting max version number");
        // Get next version number - Use raw query for reliability
        error_log("flowBPMN DEBUG: Getting max version number");
        $query = "SELECT MAX(version_number) AS max_version FROM " . self::getTable() . " WHERE plugin_flowbpmn_flows_id = $flow_id";
        $result = $DB->query($query);
        
        $maxVersion = 0;
        if ($result && $DB->numrows($result)) {
            $row = $DB->fetchAssoc($result);
            $maxVersion = $row['max_version'] ?? 0;
        }
        
        // $maxVersion calculated above
        
        $nextVersion = $maxVersion + 1;
        error_log("flowBPMN DEBUG: Next version will be v$nextVersion");
        
        
        $bpmn_xml = $flowData['bpmn_xml'] ?? '';
        error_log("flowBPMN DEBUG: BPMN XML length: " . strlen($bpmn_xml));
        
        // Compression for versions (Priority 2)
        // If not already compressed and large, compress it
        if (!empty($bpmn_xml) && strpos($bpmn_xml, 'COMPRESSED::') === false && strlen($bpmn_xml) > 10240) {
            $compressed = gzcompress($bpmn_xml, 6);
            if ($compressed !== false) {
                $bpmn_xml = 'COMPRESSED::' . base64_encode($compressed);
                error_log("flowBPMN DEBUG: XML compressed");
            }
        }
        
        // Create version record
        error_log("flowBPMN DEBUG: Creating version record");
        $version = new self();
        $input = [
            'plugin_flowbpmn_flows_id' => $flow_id,
            'version_number' => $nextVersion,
            'name' => $flowData['name'] ?? '',
            'comment' => sprintf(__('Version %d', 'flowbpmn'), $nextVersion),
            'bpmn_xml' => $bpmn_xml,
            'svg_content' => $flowData['svg_content'] ?? '',
            'users_id' => Session::getLoginUserID(),
            'date_creation' => $_SESSION['glpi_currenttime']
        ];
        
        error_log("flowBPMN DEBUG: Calling version->add()");
        $versionId = $version->add($input);
        error_log("flowBPMN DEBUG: version->add() returned: " . ($versionId ? $versionId : 'FALSE'));
        
        // Clean old versions if needed
        if ($versionId) {
            error_log("flowBPMN DEBUG: Cleaning old versions");
            self::cleanOldVersions($flow_id);
            error_log("flowBPMN DEBUG: createVersion SUCCESS - versionId=$versionId");
        } else {
            error_log("flowBPMN ERROR: version->add() failed!");
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
            // Decompression (Priority 2)
            if (isset($data['bpmn_xml']) && strpos($data['bpmn_xml'], 'COMPRESSED::') === 0) {
                $encoded = substr($data['bpmn_xml'], 12);
                $compressed = base64_decode($encoded);
                if ($compressed) {
                    $decompressed = gzuncompress($compressed);
                    if ($decompressed) {
                        $data['bpmn_xml'] = $decompressed;
                    }
                }
            }
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
            $data = $iterator->current();
            
            // Decompression (Priority 2)
            if (isset($data['bpmn_xml']) && strpos($data['bpmn_xml'], 'COMPRESSED::') === 0) {
                $encoded = substr($data['bpmn_xml'], 12);
                $compressed = base64_decode($encoded);
                if ($compressed) {
                    $decompressed = gzuncompress($compressed);
                    if ($decompressed) {
                        $data['bpmn_xml'] = $decompressed;
                    }
                }
            }
            
            return $data;
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
        
        // Fixed limit: keep max 50 versions per flow
        $maxVersions = 50;
        
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
