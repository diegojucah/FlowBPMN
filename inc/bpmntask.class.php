<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

/**
 * Class PluginFlowbpmnTask
 * 
 * Handles background tasks for the BPMN plugin
 */
class PluginFlowbpmnTask extends CommonDBTM {
    
    /**
     * @var int $dohistory Maintain history
     */
    public $dohistory = false;
    
    /**
     * Get the name of the type
     */
    static function getTypeName($nb = 0) {
        return __('BPMN Tasks', 'flowbpmn');
    }
    
    /**
     * Clean up old BPMN versions
     * 
     * @param CronTask $task The cron task object
     * @return int 0 (nothing to do), 1 (success), -1 (error)
     */
    public static function cronBpmnCleanup(CronTask $task) {
        global $DB;
        
        // Get the number of days to keep versions from config or use default (30 days)
        $days_to_keep = (int)$task->fields['param'] ?: 30;
        $config = new PluginFlowbpmnConfig();
        $max_versions = (int)$config->getConfig('max_versions_per_item') ?: 10;
        
        // Calculate the cutoff date
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-$days_to_keep days"));
        
        // First, clean up versions older than the cutoff date
        $result = $DB->delete(
            'glpi_plugin_flowbpmn_versions',
            ['date_creation' => ['<', $cutoff_date]]
        );
        
        if ($result === false) {
            $task->log("Error cleaning up old BPMN versions: " . $DB->error());
            return -1;
        }
        
        $deleted = $DB->affected_rows();
        
        // Next, limit the number of versions per flow to the configured maximum
        if ($max_versions > 0) {
            // Find flows with more than the maximum number of versions
            $flows = $DB->request([
                'SELECT' => ['plugin_flowbpmn_flows_id', 'COUNT' => 'count'],
                'FROM'   => 'glpi_plugin_flowbpmn_versions',
                'GROUP'  => 'plugin_flowbpmn_flows_id',
                'HAVING' => ['count' => ['>', $max_versions]]
            ]);
            
            $extra_deleted = 0;
            
            foreach ($flows as $flow) {
                // Get the IDs of the oldest versions to delete
                $versions_to_keep = $DB->request([
                    'SELECT' => 'id',
                    'FROM'   => 'glpi_plugin_flowbpmn_versions',
                    'WHERE'  => ['plugin_flowbpmn_flows_id' => $flow['plugin_flowbpmn_flows_id']],
                    'ORDER'  => 'date_creation DESC',
                    'START'  => $max_versions,
                    'LIMIT'  => 1000 // Safety limit
                ]);
                
                $ids_to_delete = [];
                foreach ($versions_to_keep as $version) {
                    $ids_to_delete[] = $version['id'];
                }
                
                if (!empty($ids_to_delete)) {
                    $result = $DB->delete(
                        'glpi_plugin_flowbpmn_versions',
                        ['id' => $ids_to_delete]
                    );
                    
                    if ($result === false) {
                        $task->log(sprintf(
                            "Error cleaning up versions for flow %s: %s",
                            $flow['plugin_flowbpmn_flows_id'],
                            $DB->error()
                        ));
                        continue;
                    }
                    
                    $extra_deleted += $DB->affected_rows();
                }
            }
            
            $deleted += $extra_deleted;
        }
        
        if ($deleted > 0) {
            $task->log(sprintf(
                "Cleaned up %d old BPMN versions (older than %d days and limited to %d versions per flow)",
                $deleted,
                $days_to_keep,
                $max_versions
            ));
            return 1;
        }
        
        $task->log("No old BPMN versions to clean up");
        return 0;
    }
    
    /**
     * Get cron task information
     */
    public static function cronInfo($name) {
        switch ($name) {
            case 'bpmnCleanup':
                return [
                    'description' => __('Clean up old BPMN versions', 'flowbpmn'),
                    'parameter'   => __('Maximum age in days (0 to disable)', 'flowbpmn')
                ];
        }
        
        return [];
    }
}
