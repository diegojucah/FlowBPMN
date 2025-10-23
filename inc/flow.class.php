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
 * Main flow class - Manages BPMN flows
 */
class PluginFlowbpmnFlow extends CommonDBTM {
    
    static $rightname = 'plugin_flowbpmn';
    
    static function getTypeName($nb = 0) {
        return 'FlowBPMN';
    }
    
    /**
     * Get tab name for an item
     */
    function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
        
        if (!$withtemplate) {
            $itemtype = $item->getType();
            
            if (in_array($itemtype, ['Ticket', 'Problem', 'Change'])) {
                
                // Check permissions
                if (!PluginFlowbpmnProfile::canViewFlow($itemtype)) {
                    return '';
                }
                
                $nb = countElementsInTable(
                    self::getTable(),
                    ['itemtype' => $itemtype, 'items_id' => $item->getID(), 'is_active' => 1]
                );
                
                $icon = '<i class="ti ti-git-fork"></i>';
                return self::createTabEntry('FlowBPMN', $nb, '', $icon);
            }
        }
        
        return '';
    }
    
    /**
     * Display tab content for an item
     */
    static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
        
        $itemtype = $item->getType();
        
        if (in_array($itemtype, ['Ticket', 'Problem', 'Change'])) {
            $flow = new self();
            $flow->showForItem($item);
            return true;
        }
        
        return false;
    }
    
    /**
     * Show BPMN flow editor for an item
     */
    function showForItem(CommonDBTM $item) {
        global $CFG_GLPI;
        
        $itemtype = $item->getType();
        $items_id = $item->getID();
        
        // Check if user can edit
        $canEdit = PluginFlowbpmnProfile::canEditFlow($itemtype);
        
        // Get existing flow
        $existing = $this->getForItem($itemtype, $items_id);
        
        echo "<div class='flowbpmn-container'>";
        
        // Toolbar
        echo "<div class='flowbpmn-toolbar' style='display: flex; justify-content: space-between; align-items: center; padding: 15px; background: #f8f9fa; border-bottom: 1px solid #dee2e6; margin-bottom: 10px;'>";
        echo "<div class='flowbpmn-toolbar-left'>";
        echo "<h3 style='margin: 0;'><i class='ti ti-git-fork'></i> Editor flowBPMN</h3>";
        echo "</div>";
        
        if ($canEdit) {
            echo "<div class='flowbpmn-toolbar-right'>";
            echo "<button type='button' class='btn btn-primary' id='bpmn-save-btn'>";
            echo "<i class='ti ti-device-floppy'></i> " . __('Salvar', 'flowbpmn');
            echo "</button>";
            
            echo "<button type='button' class='btn btn-secondary ms-2' id='bpmn-export-btn'>";
            echo "<i class='ti ti-download'></i> " . __('Exportar', 'flowbpmn');
            echo "</button>";
            
            if ($existing) {
                echo "<button type='button' class='btn btn-secondary ms-2' id='bpmn-versions-btn'>";
                echo "<i class='ti ti-history'></i> " . __('Versões', 'flowbpmn');
                echo "</button>";
            }
            
            echo "</div>";
        }
        echo "</div>";
        
        // BPMN Canvas - Aumentado para 800px
        echo "<div id='bpmn-canvas' class='flowbpmn-canvas' 
              style='height: 800px; width: 100%; border: 1px solid #dee2e6; background: white;'
              data-itemtype='" . $itemtype . "' 
              data-items-id='" . $items_id . "'
              data-can-edit='" . ($canEdit ? '1' : '0') . "'>";
        
        if (!$canEdit) {
            echo "<div class='alert alert-info' style='margin: 20px;'>";
            echo "<i class='ti ti-info-circle'></i> ";
            echo __('Você não tem permissão para editar fluxos BPMN.', 'flowbpmn');
            echo "</div>";
        }
        
        echo "</div>";
        
        // Properties panel
        if ($existing && $canEdit) {
            echo "<div class='flowbpmn-properties'>";
            echo "<h4>" . __('Flow Information', 'flowbpmn') . "</h4>";
            
            echo "<div class='form-group'>";
            echo "<label>" . __('Name', 'flowbpmn') . "</label>";
            echo "<input type='text' class='form-control' id='flow-name' value='" . 
                 htmlspecialchars($existing['name'] ?? '') . "'>";
            echo "</div>";
            
            echo "<div class='form-group'>";
            echo "<label>" . __('Last modified', 'flowbpmn') . "</label>";
            echo "<p>" . Html::convDateTime($existing['date_mod']) . "</p>";
            echo "</div>";
            
            echo "<div class='form-group'>";
            echo "<label>" . __('Created by', 'flowbpmn') . "</label>";
            echo "<p>" . getUserName($existing['users_id']) . "</p>";
            echo "</div>";
            
            echo "</div>";
        }
        
        echo "</div>"; // End container
        
        // Load bpmn-js and initialize editor
        $this->loadBpmnEditor($existing);
    }
    
    /**
     * Load BPMN editor scripts and initialize
     */
    private function loadBpmnEditor($existingFlow = null) {
        
        $pluginDir = Plugin::getWebDir('flowbpmn');
        $bpmnXml = $existingFlow['bpmn_xml'] ?? null;
        
        // Encode XML for JavaScript
        $bpmnXmlJson = json_encode($bpmnXml);
        
        echo Html::scriptBlock("
            $(document).ready(function() {
                // Initialize BPMN editor
                if (typeof BpmnFlowEditor !== 'undefined') {
                    window.bpmnEditor = new BpmnFlowEditor({
                        container: '#bpmn-canvas',
                        existingXml: {$bpmnXmlJson},
                        pluginUrl: '{$pluginDir}'
                    });
                }
            });
        ");
    }
    
    /**
     * Get flow for an item
     */
    function getForItem($itemtype, $items_id) {
        global $DB;
        
        $iterator = $DB->request([
            'FROM'  => self::getTable(),
            'WHERE' => [
                'itemtype' => $itemtype,
                'items_id' => $items_id,
                'is_active' => 1
            ],
            'ORDER' => 'date_mod DESC',
            'LIMIT' => 1
        ]);
        
        if (count($iterator)) {
            return $iterator->current();
        }
        
        return null;
    }
    
    /**
     * Save or update flow
     */
    function saveFlow($itemtype, $items_id, $bpmn_xml, $svg_content, $name = '') {
        global $DB;
        
        if (!isset($_SESSION['glpi_currenttime'])) {
            $_SESSION['glpi_currenttime'] = date('Y-m-d H:i:s');
        }
        
        $existing = $this->getForItem($itemtype, $items_id);
        $users_id = Session::getLoginUserID();
        
        $input = [
            'itemtype' => $itemtype,
            'items_id' => $items_id,
            'name' => $name,
            'bpmn_xml' => $bpmn_xml,
            'svg_content' => $svg_content,
            'users_id' => $users_id
        ];
        
        if ($existing) {
            // Update existing flow
            $input['id'] = $existing['id'];
            $input['date_mod'] = $_SESSION['glpi_currenttime'];
            
            // Save current version before updating
            PluginFlowbpmnVersion::createVersion($existing['id'], $existing);
            
            if ($this->update($input)) {
                // Update item description with SVG if configured
                $this->updateItemWithSvg($itemtype, $items_id, $svg_content);
                return $input['id'];
            }
        } else {
            // Create new flow
            $input['date_creation'] = $_SESSION['glpi_currenttime'];
            $input['date_mod'] = $_SESSION['glpi_currenttime'];
            
            if ($id = $this->add($input)) {
                // Update item description with SVG if configured
                $this->updateItemWithSvg($itemtype, $items_id, $svg_content);
                return $id;
            }
        }
        
        return false;
    }
    
    /**
     * Update item description with SVG image
     */
    private function updateItemWithSvg($itemtype, $items_id, $svg_content) {
        
        // Check if auto-attach is enabled
        $config = new PluginFlowbpmnConfig();
        if (!$config->getConfig('enable_auto_attach_image')) {
            return;
        }
        
        $item = new $itemtype();
        if ($item->getFromDB($items_id)) {
            
            $content = $item->fields['content'];
            
            // Create BPMN section marker
            $bpmnSection = "\n\n<!-- BPMN Flow Diagram -->\n" . $svg_content . "\n<!-- End BPMN Flow -->\n";
            
            // Check if there's already a BPMN section
            if (preg_match('/<!-- BPMN Flow Diagram -->.*<!-- End BPMN Flow -->/s', $content)) {
                // Replace existing
                $content = preg_replace(
                    '/<!-- BPMN Flow Diagram -->.*<!-- End BPMN Flow -->/s',
                    $bpmnSection,
                    $content
                );
            } else {
                // Append new
                $content .= $bpmnSection;
            }
            
            $item->update([
                'id' => $items_id,
                'content' => $content
            ]);
        }
    }
    
    /**
     * Delete flow
     */
    function deleteFlow($id) {
        
        // Mark as inactive instead of deleting
        return $this->update([
            'id' => $id,
            'is_active' => 0,
            'date_mod' => $_SESSION['glpi_currenttime']
        ]);
    }
    
    /**
     * Get history of a flow
     */
    function getHistory($itemtype, $items_id) {
        global $DB;
        
        $flow = $this->getForItem($itemtype, $items_id);
        if (!$flow) {
            return [];
        }
        
        return PluginFlowbpmnVersion::getVersions($flow['id']);
    }
}
