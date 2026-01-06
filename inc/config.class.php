<?php
if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginFlowbpmnConfig extends CommonDBTM {

    static $rightname = 'config';

    static function getTable($classname = null) {
        return 'glpi_plugin_flowbpmn_configs';
    }

    static function getTypeName($nb = 0) {
        return __('BPMN Flow Configuration', 'flowbpmn');
    }

    static function canCreate() {
        return Session::haveRight(self::$rightname, UPDATE);
    }

    static function canView() {
        return Session::haveRight(self::$rightname, READ);
    }

    static function canUpdate() {
        return Session::haveRight(self::$rightname, UPDATE);
    }

    function showForm($ID, $options = []) {
        global $CFG_GLPI;

        if (!self::canView()) {
            return false;
        }

        // Get current config
        $this->getFromDB(1);

        echo "<form id='config-form-flowbpmn' name='form' action='" . $CFG_GLPI['root_doc'] . "/plugins/flowbpmn/front/config.form.php' method='post'>";
        echo "<div class='center' id='tabsbody'>";
        echo "<table class='tab_cadre_fixe'>";

        echo "<tr class='tab_bg_1'>";
        echo "<th colspan='4'>" . __('BPMN Flow Configuration', 'flowbpmn') . "</th>";
        echo "</tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td>" . __('Automatically attach BPMN diagram to item', 'flowbpmn') . "</td>";
        echo "<td>";
        Dropdown::showYesNo('enable_auto_attach_image', $this->fields['enable_auto_attach_image']);
        echo "</td>";
        echo "<td>" . __('Example: Attach PNG to Ticket timeline', 'flowbpmn') . "</td>";
        echo "<td></td>";
        echo "</tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td>" . __('Maximum versions per flow', 'flowbpmn') . "</td>";
        echo "<td>";
        Dropdown::showNumber('max_versions_per_item', [
            'value' => $this->fields['max_versions_per_item'],
            'min' => 0,
            'max' => 100,
            'step' => 1,
            'toadd' => [0 => __('(0 = unlimited)', 'flowbpmn')]
        ]);
        echo "</td>";
        echo "<td></td>";
        echo "<td></td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<th colspan='4'>" . __('Export Options', 'flowbpmn') . "</th>";
        echo "</tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td>" . __('Enable BPMN export', 'flowbpmn') . "</td>";
        echo "<td>";
        Dropdown::showYesNo('enable_export_bpmn', $this->fields['enable_export_bpmn']);
        echo "</td>";
        echo "<td></td>";
        echo "<td></td>";
        echo "</tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td>" . __('Enable SVG export', 'flowbpmn') . "</td>";
        echo "<td>";
        Dropdown::showYesNo('enable_export_svg', $this->fields['enable_export_svg']);
        echo "</td>";
        echo "<td></td>";
        echo "<td></td>";
        echo "</tr>";
        
        echo "<tr class='tab_bg_2'>";
        echo "<td>" . __('Enable PNG export', 'flowbpmn') . "</td>";
        echo "<td>";
        Dropdown::showYesNo('enable_export_png', $this->fields['enable_export_png']);
        echo "</td>";
        echo "<td></td>";
        echo "<td></td>";
        echo "</tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td colspan='4' class='center'>";
        echo "<input type='hidden' name='id' value='1'>";
        echo "<input type='submit' name='update' class='btn btn-primary' value='" . _sx('button', 'Save') . "'>";
        echo "</td>";
        echo "</tr>";

        echo "</table>";
        echo "</div>";
        Html::closeForm();
        return true;
    }

    static function updateConfig(array $input) {
        $config = new self();
        return $config->update($input);
    }
}
