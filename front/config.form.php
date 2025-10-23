<?php

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

include('../../../inc/includes.php');

Session::checkRight('config', UPDATE);

// Handle form submission
if (isset($_POST['update'])) {
    Session::checkRight('config', UPDATE);
    
    if (PluginFlowbpmnConfig::updateConfig($_POST)) {
        Session::addMessageAfterRedirect(
            __('Configuration successfully updated', 'flowbpmn'),
            false,
            INFO
        );
    } else {
        Session::addMessageAfterRedirect(
            __('Error updating configuration', 'flowbpmn'),
            false,
            ERROR
        );
    }
    
    Html::back();
}

// Display page
Html::header(
    __('BPMN Flow Configuration', 'flowbpmn'),
    $_SERVER['PHP_SELF'],
    'config',
    'PluginFlowbpmnConfig'
);

$config = new PluginFlowbpmnConfig();
$config->showForm();

Html::footer();
