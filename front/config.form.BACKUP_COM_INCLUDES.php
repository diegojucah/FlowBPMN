<?php

/**
 * -------------------------------------------------------------------------
 * flowBPMN Plugin for GLPI - Configuration Form
 * -------------------------------------------------------------------------
 */

require '../../../vendor/autoload.php';
include '../../../inc/includes.php';

Session::checkRight('config', UPDATE);

// Handle form submission
if (isset($_POST['update'])) {
    Session::checkRight('config', UPDATE);
    
    $config = new PluginFlowbpmnConfig();
    
    if ($config->updateConfig($_POST)) {
        Session::addMessageAfterRedirect(
            'Configuração do flowBPMN atualizada com sucesso',
            false,
            INFO
        );
    } else {
        Session::addMessageAfterRedirect(
            'Erro ao atualizar configuração do flowBPMN',
            false,
            ERROR
        );
    }
    
    Html::back();
}

// Display page
Html::header(
    'Configuração do flowBPMN',
    $_SERVER['PHP_SELF'],
    'config',
    'PluginFlowbpmnConfig'
);

$config = new PluginFlowbpmnConfig();
$config->showForm(1);

Html::footer();
