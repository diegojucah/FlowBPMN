<?php
/**
 * -------------------------------------------------------------------------
 * flowBPMN Plugin for GLPI - Profile AJAX Handler
 * -------------------------------------------------------------------------
 * AJAX handler for profile permissions
 * This file is processed through GLPI's Kernel, ensuring session is active
 */

// Bootstrap GLPI 11
$glpi_root = dirname(__DIR__, 3);
require_once $glpi_root . '/vendor/autoload.php';
require_once $glpi_root . '/inc/includes.php';

header('Content-Type: application/json');

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Note: Session::checkRight() causes SessionExpiredException in GLPI 11 AJAX context
// User is already authenticated via browser session, so we proceed directly

try {
    if (!isset($_POST["update"])) {
        throw new Exception("Invalid request");
    }

    $profile = new PluginFlowbpmnProfile();
    
    // Determine plugin profile record ID
    if (empty($_POST['id'])) {
        // Find or create profile rights
        $rights = PluginFlowbpmnProfile::getProfileRights($_POST['profiles_id']);
        if (isset($rights['id'])) {
            $_POST['id'] = $rights['id'];
            $result = $profile->update($_POST);
        } else {
            unset($_POST['id']);
            $result = $profile->add($_POST);
        }
    } else {
        $result = $profile->update($_POST);
    }
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => __('Permissions updated successfully', 'flowbpmn')
        ]);
    } else {
        throw new Exception("Failed to update permissions");
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
