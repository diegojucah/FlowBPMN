<?php

/**
 * -------------------------------------------------------------------------
 * flowBPMN Plugin for GLPI - Brazilian Portuguese Translation
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2024 by KactuX
 * @license   GPLv3 https://www.gnu.org/licenses/gpl-3.0.html
 * -------------------------------------------------------------------------
 */

// Register translations globally for __() function
global $TRANSLATE;
if (!isset($TRANSLATE)) {
    $TRANSLATE = [];
}

$plugin_translations = [
    // General
    'flowBPMN' => 'flowBPMN',
    'BPMN Flow' => 'Fluxo BPMN',
    'Fluxo BPMN' => 'Fluxo BPMN',
    'Flow Version' => 'Versão do Fluxo',
    'Flow Versions' => 'Versões do Fluxo',

    // Editor
    'BPMN Flow Editor' => 'Editor de Fluxo BPMN',
    'Editor de Fluxo BPMN' => 'Editor de Fluxo BPMN',
    'Save' => 'Salvar',
    'Salvar' => 'Salvar',
    'Export' => 'Exportar',
    'Exportar' => 'Exportar',
    'Versions' => 'Versões',
    'Versões' => 'Versões',
    'Loading BPMN Editor...' => 'Carregando Editor BPMN...',
    'Carregando Editor BPMN...' => 'Carregando Editor BPMN...',
    'You do not have permission to edit BPMN flows.' => 'Você não tem permissão para editar fluxos BPMN.',
    'Você não tem permissão para editar fluxos BPMN.' => 'Você não tem permissão para editar fluxos BPMN.',

    // Flow Information
    'Flow Information' => 'Informações do Fluxo',
    'Name' => 'Nome',
    'Last modified' => 'Última modificação',
    'Created by' => 'Criado por',

    // Configuration
    'BPMN Flow Configuration' => 'Configuração do Fluxo BPMN',
    'Configuração do Fluxo BPMN' => 'Configuração do Fluxo BPMN',
    'Automatically attach BPMN diagram to item' => 'Anexar diagrama BPMN automaticamente ao item',
    'Anexar diagrama BPMN automaticamente ao item' => 'Anexar diagrama BPMN automaticamente ao item',
    'Maximum versions per flow' => 'Máximo de versões por fluxo',
    'Máximo de versões por fluxo' => 'Máximo de versões por fluxo',
    '(0 = unlimited)' => '(0 = ilimitado)',
    '(0 = ilimitado)' => '(0 = ilimitado)',
    'Export Options' => 'Opções de Exportação',
    'Opções de Exportação' => 'Opções de Exportação',
    'Enable BPMN export' => 'Habilitar exportação BPMN',
    'Habilitar exportação BPMN' => 'Habilitar exportação BPMN',
    'Enable SVG export' => 'Habilitar exportação SVG',
    'Habilitar exportação SVG' => 'Habilitar exportação SVG',
    'Enable PNG export' => 'Habilitar exportação PNG',
    'Habilitar exportação PNG' => 'Habilitar exportação PNG',
    'Configuration successfully updated' => 'Configuração atualizada com sucesso',
    'Configuração atualizada com sucesso' => 'Configuração atualizada com sucesso',
    'Error updating configuration' => 'Erro ao atualizar configuração',
    'Erro ao atualizar configuração' => 'Erro ao atualizar configuração',

    // Permissions
    'BPMN Flow Rights' => 'Direitos do Fluxo BPMN',
    'BPMN Flow Rights Management' => 'Gerenciamento de Permissões flowBPMN',
    'Item Type' => 'Tipo de Item',
    'View' => 'Visualizar',
    'Edit' => 'Editar',
    'Delete' => 'Excluir',
    'Restore' => 'Restaurar',
    'Profile rights successfully updated' => 'Direitos de perfil atualizados com sucesso',
    'Error updating profile rights' => 'Erro ao atualizar direitos de perfil',

    // Versions
    'Version %d' => 'Versão %d',
    'Flow Version' => 'Versão do Fluxo',
    'Flow Versions' => 'Versões do Fluxo',
    'Version restored successfully' => 'Versão restaurada com sucesso',
    'Failed to restore version' => 'Falha ao restaurar versão',
    'Version not found' => 'Versão não encontrada',
    'Flow not found' => 'Fluxo não encontrado',
    'Version does not belong to this flow' => 'Versão não pertence a este fluxo',
    'Invalid item type' => 'Tipo de item inválido',

    // Export
    'Export as PNG' => 'Exportar como PNG',
    'Export as SVG' => 'Exportar como SVG',
    'Export as BPMN XML' => 'Exportar como BPMN XML',

    // Document
    'flowBPMN Diagram' => 'Diagrama flowBPMN',

    // Config updates
    'flowBPMN configuration updated successfully' => 'Configuração do flowBPMN atualizada com sucesso',
    'Error updating flowBPMN configuration' => 'Erro ao atualizar configuração do flowBPMN',
    'flowBPMN Configuration' => 'Configuração do flowBPMN',
    'flowBPMN Rights' => 'Permissões do flowBPMN',
    'You do not have permission to edit BPMN flows' => 'Você não tem permissão para editar fluxos BPMN',

    // Messages
    'BPMN diagram saved successfully!' => 'Diagrama BPMN salvo com sucesso!',
    'Error saving diagram' => 'Erro ao salvar diagrama',
    'Missing required parameters' => 'Parâmetros obrigatórios ausentes',
    'Permission denied' => 'Permissão negada',
    'Failed to save flow' => 'Falha ao salvar fluxo',
    'Failed to delete flow' => 'Falha ao excluir fluxo',
    'Invalid action' => 'Ação inválida'
];

// Register translations for __() function
foreach ($plugin_translations as $key => $value) {
    $TRANSLATE['flowbpmn'][$key] = $value;
}

// Keep backward compatibility with old LANG array
$LANG['plugin_flowbpmn'] = $plugin_translations;

return $plugin_translations;
