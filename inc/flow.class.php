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

                // Compatibility with both GLPI 10.x and 11.x
                if (version_compare(GLPI_VERSION, '11.0', 'ge')) {
                    // GLPI 11.x - uses icon parameter
                    return self::createTabEntry(
                        'flowBPMN',
                        $nb,
                        $item::getType(),
                        'ti ti-git-fork'
                    );
                } else {
                    // GLPI 10.x - icon in label
                    $icon = '<i class="fas fa-project-diagram"></i> ';
                    return self::createTabEntry($icon . 'flowBPMN', $nb);
                }
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

        // Detect GLPI version for icon compatibility
        $isGLPI11 = version_compare(GLPI_VERSION, '11.0', 'ge');
        $iconClass = $isGLPI11 ? 'ti ti-git-fork' : 'fas fa-project-diagram';
        $saveIcon = $isGLPI11 ? 'ti ti-device-floppy' : 'fas fa-save';
        $historyIcon = $isGLPI11 ? 'ti ti-history' : 'fas fa-history';
        $photoIcon = $isGLPI11 ? 'ti ti-photo' : 'fas fa-image';
        $codeIcon = $isGLPI11 ? 'ti ti-code' : 'fas fa-code';
        $fileIcon = $isGLPI11 ? 'ti ti-file-code' : 'fas fa-file-code';
        $infoIcon = $isGLPI11 ? 'ti ti-info-circle' : 'fas fa-info-circle';

        echo "<div class='flowbpmn-container'>";

        // Toolbar
        echo "<div class='flowbpmn-toolbar' style='display: flex; justify-content: space-between; align-items: center; padding: 15px; background: #f8f9fa; border-bottom: 1px solid #dee2e6; margin-bottom: 10px;'>";
        echo "<div class='flowbpmn-toolbar-left'>";
        echo "<h3 style='margin: 0;'><i class='{$iconClass}'></i> Editor flowBPMN</h3>";
        echo "</div>";

        if ($canEdit) {
            echo "<div class='flowbpmn-toolbar-right'>";
            echo "<button type='button' class='btn btn-primary' id='bpmn-save-btn'>";
            echo "<i class='{$saveIcon}'></i> " . __('Save', 'flowbpmn');
            echo "</button>";

            // Export buttons group
            echo "<div class='btn-group ms-2' role='group'>";

            echo "<button type='button' class='btn btn-sm btn-outline-secondary' id='bpmn-export-png-btn' title='" . __('Export as PNG', 'flowbpmn') . "'>";
            echo "<i class='{$photoIcon}'></i> PNG";
            echo "</button>";

            echo "<button type='button' class='btn btn-sm btn-outline-secondary' id='bpmn-export-svg-btn' title='" . __('Export as SVG', 'flowbpmn') . "'>";
            echo "<i class='{$codeIcon}'></i> SVG";
            echo "</button>";

            echo "<button type='button' class='btn btn-sm btn-outline-secondary' id='bpmn-export-bpmn-btn' title='" . __('Export as BPMN XML', 'flowbpmn') . "'>";
            echo "<i class='{$fileIcon}'></i> BPMN";
            echo "</button>";

            echo "</div>";

            if ($existing) {
                echo "<button type='button' class='btn btn-secondary ms-2' id='bpmn-versions-btn'>";
                echo "<i class='{$historyIcon}'></i> " . __('Versions', 'flowbpmn');
                echo "</button>";
            }

            echo "</div>";
        }
        echo "</div>";

        // BPMN Canvas - Increased to 800px
        echo "<div id='bpmn-canvas' class='flowbpmn-canvas'
              style='height: 800px; width: 100%; border: 1px solid #dee2e6; background: white;'
              data-itemtype='" . $itemtype . "'
              data-items-id='" . $items_id . "'
              data-can-edit='" . ($canEdit ? '1' : '0') . "'>";

        if (!$canEdit) {
            echo "<div class='alert alert-info' style='margin: 20px;'>";
            echo "<i class='{$infoIcon}'></i> ";
            echo __('You do not have permission to edit BPMN flows', 'flowbpmn');
            echo "</div>";
        }

        echo "</div>";

        // Properties panel
        if ($existing && $canEdit) {
            echo "<div class='flowbpmn-properties' style='margin-top: 15px;'>";
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
    function saveFlow($itemtype, $items_id, $bpmn_xml, $svg_content, $name = '', $png_data = '') {
        global $DB;

        error_log("flowBPMN saveFlow: Starting - itemtype=$itemtype, items_id=$items_id");

        if (!isset($_SESSION['glpi_currenttime'])) {
            $_SESSION['glpi_currenttime'] = date('Y-m-d H:i:s');
        }

        $existing = $this->getForItem($itemtype, $items_id);
        $users_id = Session::getLoginUserID();

        error_log("flowBPMN saveFlow: Existing flow: " . ($existing ? 'yes (id=' . $existing['id'] . ')' : 'no'));
        
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

            error_log("flowBPMN saveFlow: Updating existing flow");

            // Save current version before updating
            try {
                if (class_exists('PluginFlowbpmnVersion')) {
                    PluginFlowbpmnVersion::createVersion($existing['id'], $existing);
                    error_log("flowBPMN saveFlow: Version created");
                }
            } catch (Exception $e) {
                error_log("flowBPMN saveFlow: Version creation failed: " . $e->getMessage());
            }

            if ($this->update($input)) {
                error_log("flowBPMN saveFlow: Flow updated successfully");

                // Update item description with SVG if configured
                $this->updateItemWithSvg($itemtype, $items_id, $svg_content);

                // Save PNG as document if provided
                if (!empty($png_data)) {
                    error_log("flowBPMN saveFlow: Saving PNG document");
                    $this->savePNGAsDocument($itemtype, $items_id, $png_data, $name);
                }

                return $input['id'];
            } else {
                error_log("flowBPMN saveFlow: Failed to update flow in database");
            }
        } else {
            // Create new flow
            $input['date_creation'] = $_SESSION['glpi_currenttime'];
            $input['date_mod'] = $_SESSION['glpi_currenttime'];

            error_log("flowBPMN saveFlow: Creating new flow");

            if ($id = $this->add($input)) {
                error_log("flowBPMN saveFlow: Flow created with ID: " . $id);

                // Update item description with SVG if configured
                $this->updateItemWithSvg($itemtype, $items_id, $svg_content);

                // Save PNG as document if provided
                if (!empty($png_data)) {
                    error_log("flowBPMN saveFlow: Saving PNG document");
                    $this->savePNGAsDocument($itemtype, $items_id, $png_data, $name);
                }

                return $id;
            } else {
                error_log("flowBPMN saveFlow: Failed to create flow in database");
            }
        }

        return false;
    }
    
    /**
     * Save PNG as document attachment
     */
    private function savePNGAsDocument($itemtype, $items_id, $png_data, $name) {

        // Check if auto-attach is enabled
        $config = new PluginFlowbpmnConfig();
        if (!$config->getConfig('enable_auto_attach_image')) {
            return false;
        }

        try {
            // Decode base64 PNG data
            if (strpos($png_data, 'data:image/png;base64,') === 0) {
                $png_data = substr($png_data, strlen('data:image/png;base64,'));
            }
            $png_binary = base64_decode($png_data);

            if ($png_binary === false) {
                return false;
            }

            // Generate unique filename
            $filename = 'flowBPMN_' . $itemtype . '_' . $items_id . '_' . date('Ymd_His') . '.png';

            // Compatibility with both GLPI 10.x and 11.x
            // In GLPI 11.x, documents are managed differently
            if (version_compare(GLPI_VERSION, '11.0', 'ge')) {
                // GLPI 11.x - Use document upload system with proper file upload array
                $filepath = GLPI_TMP_DIR . '/' . $filename;

                // Save temporary file
                if (file_put_contents($filepath, $png_binary) === false) {
                    error_log('flowBPMN: Failed to write temp file: ' . $filepath);
                    return false;
                }

                // Create document using GLPI 11 document system
                $document = new Document();

                // Simulate file upload array structure for GLPI 11
                $_FILES['filename'] = [
                    'name' => $filename,
                    'tmp_name' => $filepath,
                    'size' => filesize($filepath),
                    'type' => 'image/png',
                    'error' => 0
                ];

                $input = [
                    '_filename' => [$filename],
                    '_only_if_upload_succeed' => true,
                    'name' => !empty($name) ? $name : __('flowBPMN Diagram', 'flowbpmn'),
                    'users_id' => Session::getLoginUserID(),
                    'entities_id' => $_SESSION['glpiactive_entity'] ?? 0,
                ];

                // Add the document
                $doc_id = $document->add($input);

                // Clean the simulated upload
                unset($_FILES['filename']);

                if ($doc_id) {
                    // Link document to item
                    $docItem = new Document_Item();
                    $docItem->add([
                        'documents_id' => $doc_id,
                        'itemtype' => $itemtype,
                        'items_id' => $items_id,
                        'users_id' => Session::getLoginUserID(),
                        'entities_id' => $_SESSION['glpiactive_entity'] ?? 0
                    ]);

                    // Clean up temp file
                    @unlink($filepath);

                    return $doc_id;
                }

                // Clean up on failure
                @unlink($filepath);

            } else {
                // GLPI 10.x - Legacy method
                $filepath = GLPI_TMP_DIR . '/' . $filename;

                // Save temporary file
                if (file_put_contents($filepath, $png_binary) === false) {
                    return false;
                }

                // Create document
                $document = new Document();
                $input = [
                    'itemtype' => $itemtype,
                    'items_id' => $items_id,
                    'name' => !empty($name) ? $name : __('flowBPMN Diagram', 'flowbpmn'),
                    'filename' => $filename,
                    'filepath' => $filepath,
                    'mime' => 'image/png',
                    'users_id' => Session::getLoginUserID(),
                ];

                // For tickets in GLPI 10.x
                if ($itemtype == 'Ticket') {
                    $input['tickets_id'] = $items_id;
                }

                $doc_id = $document->add($input);

                if ($doc_id) {
                    // Link document to item
                    $docItem = new Document_Item();
                    $docItem->add([
                        'documents_id' => $doc_id,
                        'itemtype' => $itemtype,
                        'items_id' => $items_id,
                        'users_id' => Session::getLoginUserID()
                    ]);

                    // Clean up temp file
                    @unlink($filepath);

                    return $doc_id;
                }

                // Clean up on failure
                @unlink($filepath);
            }

        } catch (Exception $e) {
            error_log('flowBPMN PNG save error: ' . $e->getMessage());
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
