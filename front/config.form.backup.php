<?php

/**
 * -------------------------------------------------------------------------
 * flowBPMN Plugin for GLPI
 * -------------------------------------------------------------------------
 */

include ('../../../inc/includes.php');

Session::checkRight('config', UPDATE);

// Handle form submission
if (isset($_POST['update'])) {
    Session::checkRight('config', UPDATE);
    
    $config = new PluginFlowbpmnConfig();
    
    if ($config->updateConfig($_POST)) {
        Session::addMessageAfterRedirect(
            __('Configuração atualizada com sucesso', 'flowbpmn'),
            false,
            INFO
        );
    } else {
        Session::addMessageAfterRedirect(
            __('Erro ao atualizar configuração', 'flowbpmn'),
            false,
            ERROR
        );
    }
    
    Html::back();
}

// Display page
Html::header(
    __('Configuração do Fluxo BPMN', 'flowbpmn'),
    $_SERVER['PHP_SELF'],
    'config',
    'PluginFlowbpmnConfig'
);

$config = new PluginFlowbpmnConfig();
$config->showForm(1);

Html::footer();
