<?php
/**
 * -------------------------------------------------------------------------
 * flowBPMN Plugin for GLPI - Config AJAX Handler
 * -------------------------------------------------------------------------
 * AJAX handler for plugin configuration
 * This file is processed through GLPI's Kernel, ensuring session is active
 */

// Bootstrap GLPI 11
$glpi_root = dirname(__DIR__, 3);
require_once $glpi_root . '/vendor/autoload.php';
require_once $glpi_root . '/inc/includes.php';

header('Content-Type: application/json');

// Check rights
Session::checkRight('config', UPDATE);

try {
    if (!isset($_POST['update'])) {
        throw new Exception("Invalid request");
    }

    // Verify CSRF token
    Session::checkCSRF($_POST);

    if (PluginFlowbpmnConfig::updateConfig($_POST)) {
        Session::addMessageAfterRedirect(
            __('flowBPMN configuration updated successfully', 'flowbpmn'),
            false,
            INFO
        );
        
        echo json_encode([
            'success' => true,
            'message' => __('Configuration updated successfully', 'flowbpmn')
        ]);
    } else {
        throw new Exception("Failed to update configuration");
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
