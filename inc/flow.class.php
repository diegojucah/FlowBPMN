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
 * Main flow class - Manages BPMN flows
 */
class PluginFlowbpmnFlow extends CommonDBTM {

    static $rightname = 'plugin_flowbpmn';

    static function getTable($classname = null) {
        return 'glpi_plugin_flowbpmn_flows';
    }

    static function getTypeName($nb = 0) {
        return _n('BPMN Flow', 'BPMN Flows', $nb, 'flowbpmn');
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
     * Prepare input for add - GLPI Standard Method
     * Validates and sanitizes input before creating a new flow
     */
    function prepareInputForAdd($input) {
        // Validate itemtype (whitelist)
        $validTypes = ['Ticket', 'Problem', 'Change'];
        if (!isset($input['itemtype']) || !in_array($input['itemtype'], $validTypes, true)) {
            Session::addMessageAfterRedirect(
                __('Invalid item type', 'flowbpmn'),
                false,
                ERROR
            );
            return false;
        }
        
        // Validate items_id
        if (!isset($input['items_id']) || (int)$input['items_id'] <= 0) {
            Session::addMessageAfterRedirect(
                __('Invalid item ID', 'flowbpmn'),
                false,
                ERROR
            );
            return false;
        }
        
        // Set default values
        $input['users_id'] = Session::getLoginUserID();
        $input['is_active'] = 1;
        $input['is_deleted'] = 0;
        
        // Set timestamps
        if (!isset($_SESSION['glpi_currenttime'])) {
            $_SESSION['glpi_currenttime'] = date('Y-m-d H:i:s');
        }
        $input['date_creation'] = $_SESSION['glpi_currenttime'];
        $input['date_mod'] = $_SESSION['glpi_currenttime'];
        
        // Compression for large diagrams (Priority 2)
        if (isset($input['bpmn_xml']) && strlen($input['bpmn_xml']) > 10240) { // > 10KB
            // Use level 6 compression (default balance)
            $compressed = gzcompress($input['bpmn_xml'], 6);
            if ($compressed !== false) {
                // Store as Base64 with prefix to identify compressed content
                $input['bpmn_xml'] = 'COMPRESSED::' . base64_encode($compressed);
            }
        }
        
        return parent::prepareInputForAdd($input);
    }
    
    /**
     * Prepare input for update - GLPI Standard Method
     * Validates and sanitizes input before updating a flow
     */
    function prepareInputForUpdate($input) {
        // Update modification date
        if (!isset($_SESSION['glpi_currenttime'])) {
            $_SESSION['glpi_currenttime'] = date('Y-m-d H:i:s');
        }
        $input['date_mod'] = $_SESSION['glpi_currenttime'];
        $input['users_id'] = Session::getLoginUserID();
        
        // Compression for large diagrams (Priority 2)
        if (isset($input['bpmn_xml']) && strlen($input['bpmn_xml']) > 10240) { // > 10KB
            $compressed = gzcompress($input['bpmn_xml'], 6);
            if ($compressed !== false) {
                $input['bpmn_xml'] = 'COMPRESSED::' . base64_encode($compressed);
            }
        }
        
        return parent::prepareInputForUpdate($input);
    }
    
    /**
     * Actions after adding an item - GLPI Standard Method
     * Automatically adds timeline entry and saves PNG if provided
     */
    function post_addItem() {
        global $DB;
        
        parent::post_addItem();
        
        // Add timeline entry
        if (isset($this->fields['itemtype']) && isset($this->fields['items_id'])) {
            $itemtype = $this->fields['itemtype'];
            $items_id = $this->fields['items_id'];
            $name = $this->fields['name'] ?? 'BPMN Diagram';
            
            Log::history(
                $items_id,
                $itemtype,
                [0, '', sprintf(__('BPMN diagram created: %s', 'flowbpmn'), $name)],
                '',
                Log::HISTORY_LOG_SIMPLE_MESSAGE
            );
        }
        
        // Create initial version (v1)
        error_log("flowBPMN DEBUG: post_addItem called, flow_id=" . ($this->fields['id'] ?? 'NULL'));
        error_log("flowBPMN DEBUG: PluginFlowbpmnVersion exists: " . (class_exists('PluginFlowbpmnVersion') ? 'YES' : 'NO'));
        
        if (class_exists('PluginFlowbpmnVersion') && isset($this->fields['id'])) {
            try {
                // Ensure SVG content is passed
                $flowData = $this->fields;
                if (empty($flowData['svg_content']) && !empty($this->input['svg_content'])) {
                    $flowData['svg_content'] = $this->input['svg_content'];
                }

                error_log("flowBPMN DEBUG: Calling createVersion for flow_id=" . $this->fields['id']);
                PluginFlowbpmnVersion::createVersion($this->fields['id'], $flowData);
                error_log("flowBPMN DEBUG: Version created successfully");
            } catch (Exception $e) {
                error_log("flowBPMN ERROR: Failed to create initial version - " . $e->getMessage());
            }
        } else {
            error_log("flowBPMN DEBUG: Skipping version creation - class_exists=" . (class_exists('PluginFlowbpmnVersion') ? 'YES' : 'NO') . ", id_isset=" . (isset($this->fields['id']) ? 'YES' : 'NO'));
        }
        
        // Save PNG if provided (stored temporarily in input)
        if (isset($this->input['_png_data']) && !empty($this->input['_png_data'])) {
            $this->savePNGAsDocument(
                $this->fields['itemtype'],
                $this->fields['items_id'],
                $this->input['_png_data'],
                $this->fields['name'] ?? 'BPMN Diagram'
            );
        }
    }
    
    /**
     * Actions after updating an item - GLPI Standard Method
     * Automatically creates version and adds timeline entry
     */
    function post_updateItem($history = true) {
        global $DB;
        
        parent::post_updateItem($history);
        
        // Invalidate Cache
        if (isset($this->fields['itemtype']) && isset($this->fields['items_id'])) {
            /* CACHE DISABLED TEMPORARILY
            // $cache = \Glpi\Cache\CacheManager::getInstance()->getCache('core');
            // $cacheKey = "plugin_flowbpmn_flow_{$this->fields['itemtype']}_{$this->fields['items_id']}";
            // $cache->delete($cacheKey);
            */
        }
        
        if (!$history) {
            return;
        }
        
        // Create version automatically
        error_log("flowBPMN DEBUG: post_updateItem called, flow_id=" . ($this->fields['id'] ?? 'NULL'));
        
        if (class_exists('PluginFlowbpmnVersion')) {
            try {
                // Ensure SVG content is passed (fallback to input if fields missing)
                $flowData = $this->fields;
                if (empty($flowData['svg_content']) && !empty($this->input['svg_content'])) {
                    $flowData['svg_content'] = $this->input['svg_content'];
                }
                
                error_log("flowBPMN DEBUG: Calling createVersion for flow_id=" . $this->fields['id']);
                PluginFlowbpmnVersion::createVersion($this->fields['id'], $flowData);
                error_log("flowBPMN DEBUG: Version created successfully");
            } catch (Exception $e) {
                error_log("flowBPMN ERROR: Failed to create version - " . $e->getMessage());
            }
        }
        
        // Add timeline entry
        if (isset($this->fields['itemtype']) && isset($this->fields['items_id'])) {
            $itemtype = $this->fields['itemtype'];
            $items_id = $this->fields['items_id'];
            $name = $this->fields['name'] ?? 'BPMN Diagram';
            
            // Get version number
            $versionCount = 0;
            if (class_exists('PluginFlowbpmnVersion')) {
                $versionCount = PluginFlowbpmnVersion::countVersions($this->fields['id']);
            }
            
            Log::history(
                $items_id,
                $itemtype,
                [0, '', sprintf(__('BPMN diagram updated: %s (v%d)', 'flowbpmn'), $name, $versionCount)],
                '',
                Log::HISTORY_LOG_SIMPLE_MESSAGE
            );
        }
        
        // Save PNG if provided
        if (isset($this->input['_png_data']) && !empty($this->input['_png_data'])) {
            $this->savePNGAsDocument(
                $this->fields['itemtype'],
                $this->fields['items_id'],
                $this->input['_png_data'],
                $this->fields['name'] ?? 'BPMN Diagram'
            );
        }
    }
    
    /**
     * Actions after deleting an item - GLPI Standard Method
     * Cleans up related versions and adds timeline entry
     */
    function post_deleteItem() {
        global $DB;
        
        parent::post_deleteItem();
        
        // Invalidate Cache
        if (isset($this->fields['itemtype']) && isset($this->fields['items_id'])) {
            /* CACHE DISABLED TEMPORARILY
            // $cache = \Glpi\Cache\CacheManager::getInstance()->getCache('core');
            // $cacheKey = "plugin_flowbpmn_flow_{$this->fields['itemtype']}_{$this->fields['items_id']}";
            // $cache->delete($cacheKey);
            */
        }
        
        // Versions are automatically deleted via CASCADE foreign key
        // Just log the deletion
        if (isset($this->fields['itemtype']) && isset($this->fields['items_id'])) {
            $itemtype = $this->fields['itemtype'];
            $items_id = $this->fields['items_id'];
            $name = $this->fields['name'] ?? 'BPMN Diagram';
            
            Log::history(
                $items_id,
                $itemtype,
                [0, '', sprintf(__('BPMN diagram deleted: %s', 'flowbpmn'), $name)],
                '',
                Log::HISTORY_LOG_SIMPLE_MESSAGE
            );
        }
    }

    /**
     * Define search options for the item
     *
     * @return array Search options
     */
    function getSearchOptions() {
        $tab = parent::getSearchOptions();

        $tab[] = [
            'id'                 => '2',
            'table'              => $this->getTable(),
            'field'              => 'id',
            'name'               => __('ID'),
            'massiveaction'      => false,
            'datatype'           => 'number'
        ];

        $tab[] = [
            'id'                 => '3',
            'table'              => $this->getTable(),
            'field'              => 'name',
            'name'               => __('Name'),
            'datatype'           => 'itemlink',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '4',
            'table'              => $this->getTable(),
            'field'              => 'comment',
            'name'               => __('Comments'),
            'datatype'           => 'text'
        ];

        $tab[] = [
            'id'                 => '5',
            'table'              => $this->getTable(),
            'field'              => 'itemtype',
            'name'               => __('Item type', 'flowbpmn'),
            'datatype'           => 'itemtypename',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '6',
            'table'              => $this->getTable(),
            'field'              => 'items_id',
            'name'               => __('Associated item ID', 'flowbpmn'),
            'datatype'           => 'integer',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '7',
            'table'              => $this->getTable(),
            'field'              => 'is_active',
            'name'               => __('Active'),
            'datatype'           => 'bool'
        ];

        $tab[] = [
            'id'                 => '8',
            'table'              => 'glpi_entities',
            'field'              => 'completename',
            'name'               => __('Entity'),
            'datatype'           => 'dropdown'
        ];

        $tab[] = [
            'id'                 => '9',
            'table'              => 'glpi_users',
            'field'              => 'name',
            'linkfield'          => 'users_id',
            'name'               => __('Creator'),
            'datatype'           => 'dropdown'
        ];

        $tab[] = [
            'id'                 => '10',
            'table'              => 'glpi_users',
            'field'              => 'name',
            'linkfield'          => 'users_id_tech',
            'name'               => __('Technician in charge', 'flowbpmn'),
            'datatype'           => 'dropdown'
        ];

        $tab[] = [
            'id'                 => '11',
            'table'              => 'glpi_groups',
            'field'              => 'completename',
            'linkfield'          => 'groups_id_tech',
            'name'               => __('Group in charge', 'flowbpmn'),
            'datatype'           => 'dropdown'
        ];

        $tab[] = [
            'id'                 => '12',
            'table'              => $this->getTable(),
            'field'              => 'date_creation',
            'name'               => __('Creation date'),
            'datatype'           => 'datetime',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '13',
            'table'              => $this->getTable(),
            'field'              => 'date_mod',
            'name'               => __('Last update'),
            'datatype'           => 'datetime',
            'massiveaction'      => false
        ];

        return $tab;
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

                // GLPI 11+ - uses icon parameter
                return self::createTabEntry(
                    'FlowBPMN',
                    0,
                    $item::getType(),
                    'ti ti-git-fork'
                );
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

        // GLPI 11+ icons
        $iconClass = 'ti ti-git-fork';
        $saveIcon = 'ti ti-device-floppy';
        $uploadIcon = 'ti ti-upload';
        $downloadIcon = 'ti ti-download';
        $historyIcon = 'ti ti-history';
        $photoIcon = 'ti ti-photo';
        $codeIcon = 'ti ti-code';
        $codeIcon = 'ti ti-code';
        $fileIcon = 'ti ti-file-code';

        // Inject Translations for JS
        $jsTranslations = [
            'Current Version' => self::_t('Current Version'),
            'Version History' => self::_t('Version History'),
            'Template Gallery' => self::_t('Template Gallery'),
            'Restore' => self::_t('Restore'),
            'Apply' => self::_t('Apply'),
            'View' => self::_t('View'),
            'Delete' => self::_t('Delete'),
            'Close' => self::_t('Close'),
            'No preview available' => self::_t('No preview available'),
            'Public' => self::_t('Public'),
            'Private' => self::_t('Private'),
            'System' => self::_t('System'),
            'Search templates by name...' => self::_t('Search templates by name...'),
            'No templates found' => self::_t('No templates found'),
            'Error loading template' => self::_t('Error loading template'),
            'Template loaded successfully!' => self::_t('Template loaded successfully!'),
            'This will overwrite the current diagram. Continue?' => self::_t('This will overwrite the current diagram. Continue?'),
            'Delete this template permanently?' => self::_t('Delete this template permanently?'),
            'Template deleted' => self::_t('Template deleted'),
            'Error deleting template' => self::_t('Error deleting template'),
            'No previous versions available' => self::_t('No previous versions available'),
            'Modification Date' => self::_t('Modification Date'),
            'Author' => self::_t('Author'),
            'Responsible User' => self::_t('Responsible User'),
            
            // Import dropdown
            'Import from Ticket' => self::_t('Import from Ticket'),
            'Import from Problem' => self::_t('Import from Problem'),
            'Import from Change' => self::_t('Import from Change'),
            'Import from External File' => self::_t('Import from External File'),
            'Select Ticket' => self::_t('Select Ticket'),
            'Select Problem' => self::_t('Select Problem'),
            'Select Change' => self::_t('Select Change'),
            'Type to search...' => self::_t('Type to search...'),
            'Selected diagram:' => self::_t('Selected diagram:'),
            'No diagram found' => self::_t('No diagram found'),
            'Diagram imported successfully from %s' => self::_t('Diagram imported successfully from %s'),
            'Failed to import diagram' => self::_t('Failed to import diagram'),
            'Import diagram from %s?' => self::_t('Import diagram from %s?'),
            'This will replace your current diagram' => self::_t('This will replace your current diagram')
        ];
        
        echo "<script>
            window.FLOWBPMN_I18N = " . json_encode($jsTranslations) . ";
        </script>";

        echo "<div class='flowbpmn-container'>";

        // Toolbar
        echo "<div class='flowbpmn-toolbar' style='display: flex; justify-content: space-between; align-items: center; padding: 15px; background: #f8f9fa; border-bottom: 1px solid #dee2e6; margin-bottom: 10px;'>";
        echo "<div class='flowbpmn-toolbar-left'>";
        echo "<h3 style='margin: 0;'><i class='{$iconClass}'></i> " . self::_t('BPMN Flow Editor') . "</h3>";
        echo "</div>";

        if ($canEdit) {
            echo "<div class='flowbpmn-toolbar-right'>";
            
            // Botão Salvar (Split Button)
            echo "<div class='btn-group'>";
            echo "<button type='button' class='btn' id='bpmn-save-btn' style='background-color: #FFC107; color: #212529; border-color: #FFC107;'>";
            echo "<i class='{$saveIcon}'></i> " . self::_t('Save');
            echo "</button>";
            echo "<button type='button' class='btn dropdown-toggle dropdown-toggle-split' data-bs-toggle='dropdown' aria-expanded='false' style='background-color: #FFC107; color: #212529; border-color: #FFC107; border-left: 1px solid rgba(0,0,0,0.1);'>";
            echo "<span class='visually-hidden'>Toggle Dropdown</span>";
            echo "</button>";
            echo "<ul class='dropdown-menu'>";
            echo "<li><a class='dropdown-item' href='#' id='bpmn-save-template-btn'><i class='ti ti-template'></i> " . self::_t('Save as Template') . "</a></li>";
            echo "</ul>";
            echo "</div>";

            // Botão Importar (Dropdown)
            echo "<div class='btn-group ms-2' role='group'>";
            echo "<button type='button' class='btn btn-success dropdown-toggle' data-bs-toggle='dropdown' aria-expanded='false'>";
            echo "<i class='{$uploadIcon}'></i> " . self::_t('Import');
            echo "</button>";
            echo "<ul class='dropdown-menu'>";
            echo "<li><a class='dropdown-item' href='#' id='import-from-ticket-option'>";
            echo "<i class='ti ti-ticket'></i> " . self::_t('Import from Ticket') . "</a></li>";
            echo "<li><a class='dropdown-item' href='#' id='import-from-problem-option'>";
            echo "<i class='ti ti-alert-triangle'></i> " . self::_t('Import from Problem') . "</a></li>";
            echo "<li><a class='dropdown-item' href='#' id='import-from-change-option'>";
            echo "<i class='ti ti-git-branch'></i> " . self::_t('Import from Change') . "</a></li>";
            echo "<li><hr class='dropdown-divider'></li>";
            echo "<li><a class='dropdown-item' href='#' id='import-from-file-option'>";
            echo "<i class='ti ti-file-upload'></i> " . self::_t('Import from External File') . "</a></li>";
            echo "</ul>";
            echo "</div>";
            echo "<input type='file' id='bpmn-file-input' accept='.bpmn,.xml' style='display: none;'>";

            // Botão Modelos (Simplified - Opens Gallery Directly)
            echo "<button type='button' class='btn btn-outline-secondary ms-2' id='bpmn-load-template-btn'>";
            echo "<i class='ti ti-template'></i> " . self::_t('Templates');
            echo "</button>";

            echo "<div class='btn-group ms-2' role='group'>";
            echo "<button type='button' class='btn btn-outline-secondary dropdown-toggle' data-bs-toggle='dropdown' aria-expanded='false'>";
            echo "<i class='{$downloadIcon}'></i> " . self::_t('Export');
            echo "</button>";
            echo "<ul class='dropdown-menu'>";
            echo "<li><a class='dropdown-item' href='#' id='export-png-option'>";
            echo "<i class='{$photoIcon}'></i> PNG</a></li>";
            echo "<li><a class='dropdown-item' href='#' id='export-svg-option'>";
            echo "<i class='{$codeIcon}'></i> SVG</a></li>";
            echo "<li><a class='dropdown-item' href='#' id='export-bpmn-option'>";
            echo "<i class='{$fileIcon}'></i> BPMN</a></li>";
            echo "<li><hr class='dropdown-divider'></li>";
            echo "<li><a class='dropdown-item' href='#' id='export-pdf-option'>";
            echo "<i class='ti ti-file-type-pdf try-1'></i> PDF</a></li>";
            echo "</ul>";
            echo "</div>";

            // Botão Versões - só aparece quando houver 2+ versões
            if ($existing) {
                // Get version count - Force include class
                $versionCount = 0;
                if (!class_exists('PluginFlowbpmnVersion')) {
                     include_once(GLPI_ROOT . '/plugins/flowbpmn/inc/version.class.php');
                }
                
                if (class_exists('PluginFlowbpmnVersion')) {
                    $versionCount = PluginFlowbpmnVersion::countVersions($existing['id']);
                }
                
                // Só renderizar botão se houver 1+ versões
                if ($versionCount >= 1) {
                    echo "<button type='button' class='btn btn-secondary ms-2' id='bpmn-versions-btn'>";
                    echo "<i class='{$historyIcon}'></i> " . self::_t('Versions');
                    echo " <span class='badge bg-light text-dark ms-1'>{$versionCount}</span>";
                    echo "</button>";
                }
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



        echo "</div>"; // End container

        // Load bpmn-js and initialize editor
        $this->loadBpmnEditor($existing);
    }
    
    /**
     * Load BPMN editor scripts and initialize
     */
    private function loadBpmnEditor($existingFlow = null) {
        
        $pluginDir = Plugin::getWebDir('flowbpmn');
        
        // Load CSS
        echo Html::css('plugins/flowbpmn/css/flowbpmn.css');
        
        // NOTE: flowbpmn.js is already loaded globally via setup.php $PLUGIN_HOOKS['add_javascript']
        // Do NOT load it again here to avoid "identifier already declared" errors
        
        $bpmnXml = $existingFlow['bpmn_xml'] ?? null;
        
        // DEBUG: Log what XML we're loading
        error_log("FlowBPMN loadBpmnEditor: existingFlow ID=" . ($existingFlow['id'] ?? 'null'));
        error_log("FlowBPMN loadBpmnEditor: XML length=" . strlen($bpmnXml ?? ''));
        error_log("FlowBPMN loadBpmnEditor: XML preview=" . substr($bpmnXml ?? '', 0, 100));
        
        // Encode XML for JavaScript
        $bpmnXmlJson = json_encode($bpmnXml);
        $dateMod = $existingFlow['date_mod'] ?? '';
        
        echo Html::scriptBlock("
            $(document).ready(function() {
                // Initialize BPMN editor
                if (typeof BpmnFlowEditor !== 'undefined') {
                    window.bpmnEditor = new BpmnFlowEditor({
                        container: '#bpmn-canvas',
                        existingXml: {$bpmnXmlJson},
                        pluginUrl: '{$pluginDir}',
                        dateMod: '{$dateMod}'
                    });
                } else {
                    console.error('BpmnFlowEditor class not found. Check if flowbpmn.js is loaded correctly.');
                }
            });
        ");
    }
    
    /**
     * Get flow for an item
     */
    function getForItem($itemtype, $items_id) {
        global $DB;
        
        // CACHE DISABLED TEMPORARILY TO FIX 500 ERROR
        // Use GLPI Cache - Priority 2 Optimization
        /*
        $cache = \Glpi\Cache\CacheManager::getInstance()->getCache('core');
        $cacheKey = "plugin_flowbpmn_flow_{$itemtype}_{$items_id}";
        
        $flow = $cache->get($cacheKey);
        
        if ($flow === null) {
        */
            $iterator = $DB->request([
                'FROM'  => self::getTable(),
                'WHERE' => [
                    'itemtype' => $itemtype,
                    'items_id' => $items_id,
                    'is_active' => 1
                ],
                // Add entity restriction just in case relevant in future
                // 'entities_id' => $_SESSION['glpiactive_entity'] ?? 0, 
                'ORDER' => 'date_mod DESC',
                'LIMIT' => 1
            ]);
            
            if (count($iterator)) {
                $flow = $iterator->current();
                
                // Decompression logic (Priority 2)
                if (isset($flow['bpmn_xml']) && strpos($flow['bpmn_xml'], 'COMPRESSED::') === 0) {
                    $encoded = substr($flow['bpmn_xml'], 12); // Remove prefix
                    $compressed = base64_decode($encoded);
                    if ($compressed) {
                        $decompressed = gzuncompress($compressed);
                        if ($decompressed) {
                            $flow['bpmn_xml'] = $decompressed;
                        }
                    }
                }
                
                // $cache->set($cacheKey, $flow, 3600); // Cache for 1 hour
            } else {
                // $cache->set($cacheKey, false, 60); // 1 minute
                return null;
            }
        /*
        } elseif ($flow === false) {
            return null;
        }
        */
        
        return $flow;
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

        // Auto-attach habilitado por padrão (classe PluginFlowbpmnConfig não existe)
        // TODO: Implementar classe de configuração se necessário
        $enable_auto_attach = true;

        try {
            // Decode base64 PNG data
            if (strpos($png_data, 'data:image/png;base64,') === 0) {
                $png_data = substr($png_data, strlen('data:image/png;base64,'));
            }
            $png_binary = base64_decode($png_data);

            if ($png_binary === false) {
                return false;
            }

            // Generate unique filename with timestamp
            $timestamp = date('Y-m-d H-i-s');
            $filename = 'diagrama-' . $timestamp . '.png';

            // GLPI 11 - Use document upload system with proper file upload array
            $filepath = GLPI_TMP_DIR . '/' . $filename;

            // Save temporary file
            if (file_put_contents($filepath, $png_binary) === false) {
                error_log('flowBPMN: Failed to write temp file: ' . $filepath);
                return false;
            }

            // Create document
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
                'name' => 'Diagrama BPMN - ' . date('d/m/Y H:i:s'),
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
                $users_id = Session::getLoginUserID();
                $docItem->add([
                    'documents_id' => $doc_id,
                    'itemtype' => $itemtype,
                    'items_id' => $items_id,
                    'users_id' => $users_id,
                    'entities_id' => $_SESSION['glpiactive_entity'] ?? 0
                ]);

                // Clean up temp file
                @unlink($filepath);

                return $doc_id;
            }

            // Clean up on failure
            @unlink($filepath);

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
