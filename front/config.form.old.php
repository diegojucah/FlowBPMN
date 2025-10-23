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

// Start the page
Html::header(
    PluginMeuBpmn::getTypeName(2),
    $_SERVER['PHP_SELF'],
    'config',
    'PluginMeuBpmn',
    'config'
);

// Handle form submission
if (isset($_POST['update'])) {
    // Check CSRF token
    Session::checkRight('config', UPDATE);
    
    // Save notification settings
    $enable_notifications = isset($_POST['enable_notifications']) ? 1 : 0;
    PluginMeuBpmn::setConfigValue('enable_notifications', $enable_notifications);
    
    // Save export settings
    $export_formats = [
        'enable_export_pdf' => 0,
        'enable_export_png' => 0,
        'enable_export_svg' => 0,
        'enable_export_bpmn' => 0
    ];
    
    foreach ($export_formats as $format => $default) {
        $value = isset($_POST[$format]) ? 1 : 0;
        PluginMeuBpmn::setConfigValue($format, $value);
    }
    
    // Save other settings
    $settings = [
        'default_view' => $_POST['default_view'] ?? 'diagram',
        'show_bpmn_preview' => isset($_POST['show_bpmn_preview']) ? 1 : 0,
        'max_versions' => min(100, max(1, (int)($_POST['max_versions'] ?? 10))),
        'enable_auto_save' => isset($_POST['enable_auto_save']) ? 1 : 0,
        'auto_save_interval' => min(3600, max(30, (int)($_POST['auto_save_interval'] ?? 300))),
        'enable_keyboard_shortcuts' => isset($_POST['enable_keyboard_shortcuts']) ? 1 : 0
    ];
    
    foreach ($settings as $key => $value) {
        PluginMeuBpmn::setConfigValue($key, $value);
    }
    
    // Display success message
    Session::addMessageAfterRedirect(__('Configuration updated successfully', 'flowBPMN'), true, INFO);
    Html::back();
}

// Display the form
$config = [
    'enable_notifications' => (int)PluginMeuBpmn::getConfigValue('enable_notifications', 0),
    'enable_export_pdf' => (int)PluginMeuBpmn::getConfigValue('enable_export_pdf', 1),
    'enable_export_png' => (int)PluginMeuBpmn::getConfigValue('enable_export_png', 1),
    'enable_export_svg' => (int)PluginMeuBpmn::getConfigValue('enable_export_svg', 1),
    'enable_export_bpmn' => (int)PluginMeuBpmn::getConfigValue('enable_export_bpmn', 1),
    'default_view' => PluginMeuBpmn::getConfigValue('default_view', 'diagram'),
    'show_bpmn_preview' => (int)PluginMeuBpmn::getConfigValue('show_bpmn_preview', 1),
    'max_versions' => (int)PluginMeuBpmn::getConfigValue('max_versions', 10),
    'enable_auto_save' => (int)PluginMeuBpmn::getConfigValue('enable_auto_save', 1),
    'auto_save_interval' => (int)PluginMeuBpmn::getConfigValue('auto_save_interval', 300),
    'enable_keyboard_shortcuts' => (int)PluginMeuBpmn::getConfigValue('enable_keyboard_shortcuts', 1)
];

$form = [
    'action' => Plugin::getWebDir('flowBPMN') . "/front/config.form.php",
    'buttons' => [
        [
            'type' => 'submit',
            'name' => 'update',
            'value' => _sx('button', 'Save')
        ]
    ],
    'content' => [
        'notifications' => [
            'title' => __('Notifications', 'flowBPMN'),
            'fields' => [
                'enable_notifications' => [
                    'label' => __('Enable notifications', 'flowBPMN'),
                    'type' => 'checkbox',
                    'value' => $config['enable_notifications']
                ]
            ]
        ],
        'export' => [
            'title' => __('Export Settings', 'flowBPMN'),
            'fields' => [
                'enable_export_pdf' => [
                    'label' => __('Enable PDF export', 'flowBPMN'),
                    'type' => 'checkbox',
                    'value' => $config['enable_export_pdf']
                ],
                'enable_export_png' => [
                    'label' => __('Enable PNG export', 'flowBPMN'),
                    'type' => 'checkbox',
                    'value' => $config['enable_export_png']
                ],
                'enable_export_svg' => [
                    'label' => __('Enable SVG export', 'flowBPMN'),
                    'type' => 'checkbox',
                    'value' => $config['enable_export_svg']
                ],
                'enable_export_bpmn' => [
                    'label' => __('Enable BPMN export', 'flowBPMN'),
                    'type' => 'checkbox',
                    'value' => $config['enable_export_bpmn']
                ]
            ]
        ],
        'display' => [
            'title' => __('Display Settings', 'flowBPMN'),
            'fields' => [
                'default_view' => [
                    'label' => __('Default view', 'flowBPMN'),
                    'type' => 'select',
                    'values' => [
                        'diagram' => __('Diagram', 'flowBPMN'),
                        'xml' => __('XML', 'flowBPMN')
                    ],
                    'value' => $config['default_view']
                ],
                'show_bpmn_preview' => [
                    'label' => __('Show BPMN preview in items', 'flowBPMN'),
                    'type' => 'checkbox',
                    'value' => $config['show_bpmn_preview']
                ]
            ]
        ],
        'versioning' => [
            'title' => __('Versioning', 'flowBPMN'),
            'fields' => [
                'max_versions' => [
                    'label' => __('Maximum number of versions to keep', 'flowBPMN'),
                    'type' => 'number',
                    'min' => 1,
                    'max' => 100,
                    'value' => $config['max_versions']
                ]
            ]
        ],
        'autosave' => [
            'title' => __('Auto-save', 'flowBPMN'),
            'fields' => [
                'enable_auto_save' => [
                    'label' => __('Enable auto-save', 'flowBPMN'),
                    'type' => 'checkbox',
                    'value' => $config['enable_auto_save'],
                    'onchange' => "document.getElementById('auto_save_interval').disabled = !this.checked;"
                ],
                'auto_save_interval' => [
                    'label' => __('Auto-save interval (seconds)', 'flowBPMN'),
                    'type' => 'number',
                    'min' => 30,
                    'max' => 3600,
                    'value' => $config['auto_save_interval'],
                    'id' => 'auto_save_interval',
                    'disabled' => !$config['enable_auto_save']
                ]
            ]
        ],
        'keyboard' => [
            'title' => __('Keyboard Shortcuts', 'flowBPMN'),
            'fields' => [
                'enable_keyboard_shortcuts' => [
                    'label' => __('Enable keyboard shortcuts', 'flowBPMN'),
                    'type' => 'checkbox',
                    'value' => $config['enable_keyboard_shortcuts']
                ]
            ]
        ]
    ]
];

// Add CSRF token
$form['_glpi_csrf_token'] = Session::getNewCSRFToken();

// Render the form
Html::requireJs('tinymce');
Html::requireJs('jquery');
Html::requireJs('jquery-ui');
Html::requireJs('tabprogress');

// Add custom CSS
$css = "
    .plugin_flowBPMN_config_section {
        margin-bottom: 30px;
        padding: 15px;
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    .plugin_flowBPMN_config_section h2 {
        margin-top: 0;
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
        color: #4a6ea9;
    }
    .form-field {
        margin-bottom: 15px;
    }
    .form-field label {
        display: inline-block;
        width: 300px;
        font-weight: bold;
        margin-right: 15px;
    }
    .form-actions {
        margin-top: 20px;
        padding-top: 15px;
        border-top: 1px solid #eee;
        text-align: right;
    }
";

// Output the page
echo "<div class='plugin_flowBPMN_config'>";
echo "<h1>" . PluginMeuBpmn::getTypeName(2) . " - " . __("Configuration") . "</h1>";

echo "<form method='post' action='{$form['action']}'>";

// Output each section
foreach ($form['content'] as $section_id => $section) {
    echo "<div class='plugin_flowBPMN_config_section' id='section_{$section_id}'>";
    echo "<h2>{$section['title']}</h2>";
    
    // Output each field in the section
    foreach ($section['fields'] as $field_name => $field) {
        echo "<div class='form-field'>";
        
        // Output the label
        echo "<label for='{$field_name}'>{$field['label']}</label>";
        
        // Output the appropriate input type
        switch ($field['type']) {
            case 'checkbox':
                $checked = $field['value'] ? ' checked' : '';
                $onchange = isset($field['onchange']) ? " onchange=\"{$field['onchange']}\"" : '';
                echo "<input type='checkbox' id='{$field_name}' name='{$field_name}' value='1'{$checked}{$onchange}>";
                break;
                
            case 'select':
                echo "<select id='{$field_name}' name='{$field_name}'>";
                foreach ($field['values'] as $value => $label) {
                    $selected = ($value == $field['value']) ? ' selected' : '';
                    echo "<option value='{$value}'{$selected}>{$label}</option>";
                }
                echo "</select>";
                break;
                
            case 'number':
                $min = isset($field['min']) ? " min='{$field['min']}'" : '';
                $max = isset($field['max']) ? " max='{$field['max']}'" : '';
                $disabled = isset($field['disabled']) && $field['disabled'] ? ' disabled' : '';
                echo "<input type='number' id='{$field_name}' name='{$field_name}' value='{$field['value']}'{$min}{$max}{$disabled}>";
                break;
                
            default:
                echo "<input type='text' id='{$field_name}' name='{$field_name}' value='{$field['value']}'>";
        }
        
        // Add help text if available
        if (isset($field['help'])) {
            echo "<span class='help-block'>{$field['help']}</span>";
        }
        
        echo "</div>";
    }
    
    echo "</div>";
}

// Add form actions
echo "<div class='form-actions'>";
foreach ($form['buttons'] as $button) {
    echo "<button type='{$button['type']}' name='{$button['name']}' class='submit btn btn-primary'>";
    echo $button['value'];
    echo "</button> ";
}
echo "</div>";

// Add CSRF token
echo "<input type='hidden' name='_glpi_csrf_token' value='{$form['_glpi_csrf_token']}'>";

echo "</form>";
echo "</div>";

// Add custom CSS
echo "<style>{$css}</style>";

// Add JavaScript to handle form interactions
echo "
<script type='text/javascript'>
$(function() {
    // Initialize tabs if needed
    if ($.ui && $.ui.tabs) {
        $('.plugin_flowBPMN_config').tabs();
    }
    
    // Handle form submission
    $('form').on('submit', function(e) {
        // Add any client-side validation here
        return true;
    });
});
</script>
";

// Display the page footer
Html::footer();
