<?php
/**
 * AJAX handler for saving profile permissions
 * FlowBPMN Plugin - Robust Version
 */

// Prevent any output before JSON and capture all errors
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Start output buffering to catch any stray output
ob_start();

try {
    // Bootstrap GLPI using chdir (proven method from flow.php)
    chdir(dirname(dirname(dirname(dirname(__FILE__)))));
    
    if (!defined('GLPI_ROOT')) {
        define('GLPI_ROOT', getcwd());
    }
    
    // Include main GLPI
    $includesFile = GLPI_ROOT . '/inc/includes.php';
    if (!file_exists($includesFile)) {
        throw new Exception('GLPI includes.php not found at: ' . $includesFile);
    }
    
    include_once($includesFile);
    
    // Clean any output from includes
    ob_end_clean();
    ob_start();
    
    // Set JSON header
    header('Content-Type: application/json; charset=utf-8');
    
    // Verify CSRF - GLPI style
    Session::checkCSRF($_POST);
    
    // Check permissions
    if (!Session::haveRight('profile', UPDATE)) {
        throw new Exception('Permission denied');
    }
    
    $profiles_id = intval($_POST['profiles_id'] ?? 0);
    if (!$profiles_id) {
        throw new Exception('No profile ID provided');
    }
    
    // Load plugin class
    $classFile = GLPI_ROOT . '/plugins/flowbpmn/inc/profile.class.php';
    if (!class_exists('PluginFlowbpmnProfile')) {
        if (file_exists($classFile)) {
            include_once($classFile);
        } else {
            throw new Exception('Plugin class file not found');
        }
    }
    
    if (!class_exists('PluginFlowbpmnProfile')) {
        throw new Exception('PluginFlowbpmnProfile class could not be loaded');
    }
    
    // Get existing rights
    $existing = PluginFlowbpmnProfile::getProfileRights($profiles_id);
    
    // Build input array
    $input = ['profiles_id' => $profiles_id];
    
    // Collect all permission fields
    $types = ['ticket', 'problem', 'change'];
    $actions = ['view', 'edit', 'delete', 'restore'];
    
    foreach ($types as $type) {
        foreach ($actions as $action) {
            $col = 'can_' . $action . '_' . $type;
            $input[$col] = (isset($_POST[$col]) && $_POST[$col]) ? 1 : 0;
        }
    }
    
    // Save
    $profile = new PluginFlowbpmnProfile();
    
    if (isset($existing['id']) && $existing['id'] > 0) {
        $input['id'] = $existing['id'];
        $result = $profile->update($input);
    } else {
        $result = $profile->add($input);
    }
    
    // Clean output buffer
    $unexpectedOutput = ob_get_clean();
    
    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Database operation failed']);
    }
    
} catch (Exception $e) {
    // Clean output buffer
    ob_end_clean();
    
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(200); // Send 200 so JS can parse
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    
} catch (Error $e) {
    // Catch PHP 7+ Errors
    ob_end_clean();
    
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(200);
    echo json_encode(['success' => false, 'error' => 'PHP Error: ' . $e->getMessage()]);
}
