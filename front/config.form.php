<?php

/**
 * -------------------------------------------------------------------------
 * flowBPMN Plugin for GLPI - Configuration Form
 * -------------------------------------------------------------------------
 */

// Define GLPI root for proper includes
if (!defined('GLPI_ROOT')) {
    define('GLPI_ROOT', dirname(__DIR__, 3));
}

include (GLPI_ROOT . '/inc/includes.php');

Session::checkRight('config', UPDATE);

// Handle form submission
if (isset($_POST['update'])) {
    Session::checkRight('config', UPDATE);

    // Verify CSRF token
    Session::checkCSRF($_POST);

    $config = new PluginFlowbpmnConfig();

    if (PluginFlowbpmnConfig::updateConfig($_POST)) {
        Session::addMessageAfterRedirect(
            __('flowBPMN configuration updated successfully', 'flowbpmn'),
            false,
            INFO
        );
    } else {
        Session::addMessageAfterRedirect(
            __('Error updating flowBPMN configuration', 'flowbpmn'),
            false,
            ERROR
        );
    }

    Html::back();
}

// Display page
Html::header(
    __('flowBPMN Configuration', 'flowbpmn'),
    $_SERVER['PHP_SELF'],
    'config',
    'PluginFlowbpmnConfig'
);

$config = new PluginFlowbpmnConfig();
$config->showForm(1);

Html::footer();
