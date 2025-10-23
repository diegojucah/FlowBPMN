<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginFlowBPMN extends PluginClassImport {
    
    const NAME = 'flowBPMN';
    const VERSION = '1.0.0';
    const MIN_GLPI = '11.0.0';
    const MAX_GLPI = '11.99.99';
    
    /**
     * @var bool $usenotification Enable notifications for this plugin
     */
    protected $usenotification = false;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->table = 'glpi_plugin_flowBPMN_config';
    }
    
    /**
     * Get the name of the plugin
     * 
     * @param int $nb Number of items
     * @return string
     */
    public static function getTypeName($nb = 0) {
        return __('BPMN Workflow', 'flowBPMN');
    }
    
    /**
     * Install the plugin
     * 
     * @return boolean
     */
    public function install(Migration $migration) {
        global $DB;
        
        // Create the main table for BPMN flows
        $table = 'glpi_plugin_flowBPMN_flows';
        
        if (!$DB->tableExists($table)) {
            $query = "CREATE TABLE `$table` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `itemtype` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
                `items_id` int(11) NOT NULL,
                `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                `description` text COLLATE utf8mb4_unicode_ci,
                `bpmn` longtext COLLATE utf8mb4_unicode_ci,
                `svg` longtext COLLATE utf8mb4_unicode_ci,
                `users_id` int(11) NOT NULL,
                `date_creation` timestamp NULL DEFAULT NULL,
                `date_mod` timestamp NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `item` (`itemtype`,`items_id`),
                KEY `users_id` (`users_id`),
                KEY `date_creation` (`date_creation`),
                KEY `date_mod` (`date_mod`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
            
            $DB->queryOrDie($query, $DB->error());
        }
        
        // Create configuration table
        $config_table = 'glpi_plugin_flowBPMN_config';
        
        if (!$DB->tableExists($config_table)) {
            $query = "CREATE TABLE `$config_table` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
                `value` text COLLATE utf8mb4_unicode_ci,
                `date_mod` timestamp NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `name` (`name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
            
            $DB->queryOrDie($query, $DB->error());
            
            // Insert default configuration
            $default_config = [
                'enable_notifications' => '0',
                'enable_export_pdf' => '1',
                'enable_export_png' => '1',
                'enable_export_svg' => '1',
                'enable_export_bpmn' => '1',
                'default_view' => 'diagram',
                'show_bpmn_preview' => '1',
                'max_versions' => '10',
                'enable_auto_save' => '1',
                'auto_save_interval' => '300',
                'enable_keyboard_shortcuts' => '1'
            ];
            
            foreach ($default_config as $name => $value) {
                $DB->insert($config_table, [
                    'name' => $name,
                    'value' => $value,
                    'date_mod' => $_SESSION['glpi_currenttime']
                ]);
            }
        }
        
        // Create version table
        $version_table = 'glpi_plugin_flowBPMN_versions';
        
        if (!$DB->tableExists($version_table)) {
            $query = "CREATE TABLE `$version_table` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `plugin_flowBPMN_flows_id` int(11) NOT NULL,
                `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                `comment` text COLLATE utf8mb4_unicode_ci,
                `bpmn` longtext COLLATE utf8mb4_unicode_ci,
                `svg` longtext COLLATE utf8mb4_unicode_ci,
                `users_id` int(11) NOT NULL,
                `date_creation` timestamp NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `plugin_flowBPMN_flows_id` (`plugin_flowBPMN_flows_id`),
                KEY `users_id` (`users_id`),
                KEY `date_creation` (`date_creation`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
            
            $DB->queryOrDie($query, $DB->error());
        }
        
        // Add a cron task for cleanup
        $cron = new CronTask();
        if (!$cron->getFromDBbyName('PluginFlowBPMNTask', 'bpmnCleanup')) {
            $cron->add([
                'name' => 'bpmnCleanup',
                'itemtype' => 'PluginFlowBPMNTask',
                'state' => 0,
                'mode' => 2, // MODE_EXTERNAL
                'allowmode' => 3, // MODE_EXTERNAL | MODE_INTERNAL
                'hourmin' => 0,
                'hourmax' => 24,
                'frequency' => 86400, // Daily
                'param' => 30, // Keep versions for 30 days
                'comment' => 'Clean up old BPMN versions',
                'status' => 1 // Active
            ]);
        }
        
        return true;
    }
    
    /**
     * Uninstall the plugin
     * 
     * @return boolean
     */
    public function uninstall() {
        global $DB;
        
        $tables = [
            'glpi_plugin_flowBPMN_flows',
            'glpi_plugin_flowBPMN_config',
            'glpi_plugin_flowBPMN_versions'
        ];
        
        foreach ($tables as $table) {
            if ($DB->tableExists($table)) {
                $DB->queryOrDie("DROP TABLE IF EXISTS `$table`");
            }
        }
        
        // Remove cron tasks
        $cron = new CronTask();
        $cron->deleteByCriteria(['itemtype' => 'PluginFlowBPMNTask']);
        
        // Remove configuration
        $config = new Config();
        $config->deleteByCriteria(['context' => 'plugin:flowBPMN']);
        
        return true;
    }
    
    /**
     * Define tabs to display
     */
    public function defineTabs($options = []) {
        $ong = [];
        
        $this->addStandardTab(__CLASS__, $ong, $options);
        
        return $ong;
    }
    
    /**
     * Get the tab name for an item
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
        $valid_itemtypes = ['Ticket', 'Change', 'Problem'];
        
        if (in_array($item->getType(), $valid_itemtypes)) {
            return [
                self::createTabEntry(
                    'BPMN',
                    $this->countForItem($item),
                    $this->getType()
                )
            ];
        }
        
        return '';
    }
    
    /**
     * Display tab content for an item
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
        $valid_itemtypes = ['Ticket', 'Change', 'Problem'];
        
        if (in_array($item->getType(), $valid_itemtypes)) {
            self::showForItem($item);
        }
        
        return true;
    }
    
    /**
     * Show BPMN editor for an item
     */
    public static function showForItem(CommonGLPI $item) {
        global $CFG_GLPI;
        
        // Check permissions
        if (!$item->can($item->getID(), READ)) {
            return false;
        }
        
        // Load required CSS and JS
        echo Html::css(Plugin::getWebDir('flowBPMN') . "/css/bpmn.css");
        
        // Add BPMN editor container
        echo "<div id='bpmn-editor' data-itemtype='" . $item->getType() . "' data-items-id='" . $item->getID() . "'></div>";
        
        // Add BPMN editor script
        $js_path = Plugin::getWebDir('flowBPMN') . "/front/bpmn-js/editor.js";
        echo Html::script($js_path);
        
        // Add Font Awesome for icons
        echo Html::css("https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css");
        
        // Add CSRF token for AJAX requests
        echo "<script>
            const BPMN_AJAX_URL = '" . Plugin::getWebDir('flowBPMN') . "/ajax/bpmn_save.php';
            const BPMN_CSRF_TOKEN = '" . Session::getNewCSRFToken() . "';
        </script>";
    }
    
    /**
     * Count BPMN diagrams for an item
     */
    public function countForItem(CommonGLPI $item) {
        global $DB;
        
        $count = $DB->request([
            'COUNT' => 'cpt',
            'FROM'  => 'glpi_plugin_flowBPMN_flows',
            'WHERE' => [
                'itemtype' => $item->getType(),
                'items_id' => $item->getID()
            ]
        ])->current();
        
        return $count['cpt'];
    }
    
    /**
     * Get the configuration value for a specific key
     */
    public static function getConfigValue($name, $default = '') {
        global $DB;
        
        $config = $DB->request([
            'SELECT' => 'value',
            'FROM'   => 'glpi_plugin_flowBPMN_config',
            'WHERE'  => ['name' => $name],
            'LIMIT'  => 1
        ]);
        
        if ($config->count() > 0) {
            return $config->current()['value'];
        }
        
        return $default;
    }
    
    /**
     * Set a configuration value
     */
    public static function setConfigValue($name, $value) {
        global $DB;
        
        return $DB->updateOrInsert('glpi_plugin_flowBPMN_config', [
            'name' => $name,
            'value' => $value,
            'date_mod' => $_SESSION['glpi_currenttime']
        ], [
            'name' => $name
        ]);
    }
}

/**
 * Plugin installation hook
 */
function plugin_init_flowBPMN() {
    global $PLUGIN_HOOKS;
    
    $plugin = new Plugin();
    
    // Only proceed if plugin is installed and activated
    if (!$plugin->isInstalled('flowBPMN') || !$plugin->isActivated('flowBPMN')) {
        return false;
    }
    
    // Register plugin classes
    Plugin::registerClass('PluginFlowBPMN', ['addtabon' => ['Ticket', 'Change', 'Problem']]);
    
    // Add menu entry if needed
    $PLUGIN_HOOKS['menu_toadd']['flowBPMN'] = [];
    
    // Add CSRF compliance
    $PLUGIN_HOOKS['csrf_compliant']['flowBPMN'] = true;
    
    // Add custom CSS and JS
    $PLUGIN_HOOKS['add_css']['flowBPMN'] = 'css/bpmn.css';
    $PLUGIN_HOOKS['add_javascript']['flowBPMN'] = ['front/bpmn-js/editor.js'];
    
    // Add config page
    $PLUGIN_HOOKS['config_page']['flowBPMN'] = 'front/config.form.php';
    
    // Add search options
    $PLUGIN_HOOKS['item_get_searchoptions']['flowBPMN'] = [
        'Ticket' => ['PluginFlowBPMN', 'getSearchOptions']
    ];
    
    // Add custom fields if needed
    $PLUGIN_HOOKS['item_add']['flowBPMN'] = [
        'Ticket' => ['PluginFlowBPMN', 'itemAdded']
    ];
    
    $PLUGIN_HOOKS['item_update']['flowBPMN'] = [
        'Ticket' => ['PluginFlowBPMN', 'itemUpdated']
    ];
    
    $PLUGIN_HOOKS['item_delete']['flowBPMN'] = [
        'Ticket' => ['PluginFlowBPMN', 'itemDeleted']
    ];
    
    // Add cron tasks
    $PLUGIN_HOOKS['cron']['flowBPMN'] = [
        'PluginFlowBPMNTask' => [
            'class' => 'PluginFlowBPMNTask',
            'method' => 'cronBpmnCleanup',
            'name' => 'BPMN Cleanup',
            'param' => 30, // Keep versions for 30 days
            'state' => 1, // Active
            'mode' => 2, // MODE_EXTERNAL
            'frequency' => 86400 // Daily
        ]
    ];
}

/**
 * Plugin version information
 */
function plugin_version_flowBPMN() {
    return [
        'name'           => 'BPMN Workflow',
        'version'        => '1.0.0',
        'author'         => 'Your Name',
        'license'        => 'GPLv3+',
        'homepage'       => 'https://github.com/yourusername/glpi-bpmn-plugin',
        'requirements'   => [
            'glpi' => [
                'min' => '11.0.0',
                'max' => '11.99.99',
                'dev' => false
            ],
            'php' => [
                'min' => '7.4',
                'max' => '8.2'
            ]
        ]
    ];
}

/**
 * Check plugin prerequisites
 */
function plugin_flowBPMN_check_prerequisites() {
    // Check GLPI version
    if (version_compare(GLPI_VERSION, '11.0.0', '<') || version_compare(GLPI_VERSION, '11.99.99', '>')) {
        echo "This plugin requires GLPI version between 11.0.0 and 11.99.99";
        return false;
    }
    
    // Check PHP version
    if (version_compare(PHP_VERSION, '7.4', '<')) {
        echo "This plugin requires PHP 7.4 or higher";
        return false;
    }
    
    return true;
}

/**
 * Check plugin configuration
 */
function plugin_flowBPMN_check_config($verbose = false) {
    // Add any configuration checks here
    return true;
}
