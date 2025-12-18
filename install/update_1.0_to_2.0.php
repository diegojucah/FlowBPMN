<?php
/**
 * -------------------------------------------------------------------------
 * flowBPMN Plugin for GLPI
 * Migration Script: 1.0.0 to 2.0.0
 * -------------------------------------------------------------------------
 *
 * This script migrates existing flowBPMN 1.0.0 installations to 2.0.0
 * Adding mandatory GLPI fields and improving database structure
 *
 * @copyright Copyright (C) 2024 by KactuX
 * @license   GPLv3 https://www.gnu.org/licenses/gpl-3.0.html
 * -------------------------------------------------------------------------
 */

/**
 * Update flowBPMN from 1.0.0 to 2.0.0
 *
 * @param Migration $migration Migration object
 *
 * @return bool True on success
 */
function plugin_flowbpmn_update_1_0_to_2_0(Migration $migration) {
    global $DB;

    $migration->displayTitle(__('Upgrading flowBPMN from 1.0.0 to 2.0.0', 'flowbpmn'));

    $table_flows = 'glpi_plugin_flowbpmn_flows';
    $table_configs = 'glpi_plugin_flowbpmn_configs';
    $table_versions = 'glpi_plugin_flowbpmn_versions';
    $table_profiles = 'glpi_plugin_flowbpmn_profiles';

    // ========================================
    // UPDATE FLOWS TABLE
    // ========================================
    $migration->displayMessage(__('Updating flows table structure', 'flowbpmn'));

    // Add entities_id
    if (!$DB->fieldExists($table_flows, 'entities_id')) {
        $migration->addField($table_flows, 'entities_id', 'int unsigned', [
            'value' => 0,
            'after' => 'id'
        ]);
        $migration->addKey($table_flows, 'entities_id');
        $migration->displayMessage("  ✓ Added entities_id field");
    }

    // Add is_recursive
    if (!$DB->fieldExists($table_flows, 'is_recursive')) {
        $migration->addField($table_flows, 'is_recursive', 'tinyint', [
            'value' => 0,
            'after' => 'entities_id'
        ]);
        $migration->addKey($table_flows, 'is_recursive');
        $migration->displayMessage("  ✓ Added is_recursive field");
    }

    // Add is_deleted
    if (!$DB->fieldExists($table_flows, 'is_deleted')) {
        $migration->addField($table_flows, 'is_deleted', 'tinyint', [
            'value' => 0,
            'after' => 'is_active'
        ]);
        $migration->addKey($table_flows, 'is_deleted');
        $migration->displayMessage("  ✓ Added is_deleted field");
    }

    // Add comment
    if (!$DB->fieldExists($table_flows, 'comment')) {
        $migration->addField($table_flows, 'comment', 'text', [
            'after' => 'name'
        ]);
        $migration->displayMessage("  ✓ Added comment field");
    }

    // Add users_id_tech
    if (!$DB->fieldExists($table_flows, 'users_id_tech')) {
        $migration->addField($table_flows, 'users_id_tech', 'int unsigned', [
            'value' => 0,
            'after' => 'users_id'
        ]);
        $migration->addKey($table_flows, 'users_id_tech');
        $migration->displayMessage("  ✓ Added users_id_tech field");
    }

    // Add groups_id_tech
    if (!$DB->fieldExists($table_flows, 'groups_id_tech')) {
        $migration->addField($table_flows, 'groups_id_tech', 'int unsigned', [
            'value' => 0,
            'after' => 'users_id_tech'
        ]);
        $migration->addKey($table_flows, 'groups_id_tech');
        $migration->displayMessage("  ✓ Added groups_id_tech field");
    }

    // Add indexes for date fields
    if (!$DB->indexExists($table_flows, 'date_creation')) {
        $migration->addKey($table_flows, 'date_creation');
        $migration->displayMessage("  ✓ Added index on date_creation");
    }

    if (!$DB->indexExists($table_flows, 'date_mod')) {
        $migration->addKey($table_flows, 'date_mod');
        $migration->displayMessage("  ✓ Added index on date_mod");
    }

    // Migrate existing data to default entity
    $migration->displayMessage(__('Migrating existing flows to default entity', 'flowbpmn'));

    $DB->updateOrDie($table_flows, [
        'entities_id' => 0
    ], [
        'OR' => [
            'entities_id' => null,
            'entities_id' => ['<', 0]
        ]
    ]);

    // ========================================
    // UPDATE VERSIONS TABLE
    // ========================================
    $migration->displayMessage(__('Updating versions table structure', 'flowbpmn'));

    // Add indexes
    if (!$DB->indexExists($table_versions, 'version_number')) {
        $migration->addKey($table_versions, 'version_number');
        $migration->displayMessage("  ✓ Added index on version_number");
    }

    if (!$DB->indexExists($table_versions, 'date_creation')) {
        $migration->addKey($table_versions, 'date_creation');
        $migration->displayMessage("  ✓ Added index on date_creation");
    }

    // Add foreign key constraint
    $fk_name = 'glpi_plugin_flowbpmn_versions_ibfk_1';
    if (!$DB->tableHasForeignKey($table_versions, $fk_name)) {
        try {
            $DB->doQuery("ALTER TABLE `{$table_versions}`
                ADD CONSTRAINT `{$fk_name}`
                FOREIGN KEY (`plugin_flowbpmn_flows_id`)
                REFERENCES `{$table_flows}` (`id`)
                ON DELETE CASCADE");
            $migration->displayMessage("  ✓ Added foreign key constraint");
        } catch (Exception $e) {
            $migration->displayWarning("  ⚠ Could not add foreign key: " . $e->getMessage());
        }
    }

    // ========================================
    // UPDATE CONFIG TABLE
    // ========================================
    $migration->displayMessage(__('Updating config table structure', 'flowbpmn'));

    // Add enable_auto_attach_xml
    if (!$DB->fieldExists($table_configs, 'enable_auto_attach_xml')) {
        $migration->addField($table_configs, 'enable_auto_attach_xml', 'tinyint', [
            'value' => 0,
            'after' => 'enable_auto_attach_image'
        ]);
        $migration->displayMessage("  ✓ Added enable_auto_attach_xml field");
    }

    // Add enable_version_cleanup
    if (!$DB->fieldExists($table_configs, 'enable_version_cleanup')) {
        $migration->addField($table_configs, 'enable_version_cleanup', 'tinyint', [
            'value' => 1,
            'after' => 'max_versions_per_item'
        ]);
        $migration->displayMessage("  ✓ Added enable_version_cleanup field");
    }

    // Add default_canvas_height
    if (!$DB->fieldExists($table_configs, 'default_canvas_height')) {
        $migration->addField($table_configs, 'default_canvas_height', 'int unsigned', [
            'value' => 800,
            'after' => 'enable_export_png'
        ]);
        $migration->displayMessage("  ✓ Added default_canvas_height field");
    }

    // Add enable_grid
    if (!$DB->fieldExists($table_configs, 'enable_grid')) {
        $migration->addField($table_configs, 'enable_grid', 'tinyint', [
            'value' => 1,
            'after' => 'default_canvas_height'
        ]);
        $migration->displayMessage("  ✓ Added enable_grid field");
    }

    // Add grid_size
    if (!$DB->fieldExists($table_configs, 'grid_size')) {
        $migration->addField($table_configs, 'grid_size', 'int unsigned', [
            'value' => 10,
            'after' => 'enable_grid'
        ]);
        $migration->displayMessage("  ✓ Added grid_size field");
    }

    // Add enable_notifications
    if (!$DB->fieldExists($table_configs, 'enable_notifications')) {
        $migration->addField($table_configs, 'enable_notifications', 'tinyint', [
            'value' => 0,
            'after' => 'grid_size'
        ]);
        $migration->displayMessage("  ✓ Added enable_notifications field");
    }

    // ========================================
    // UPDATE PROFILES TABLE
    // ========================================
    $migration->displayMessage(__('Updating profiles table structure', 'flowbpmn'));

    // Add foreign key constraint
    $fk_name = 'glpi_plugin_flowbpmn_profiles_ibfk_1';
    if (!$DB->tableHasForeignKey($table_profiles, $fk_name)) {
        try {
            $DB->doQuery("ALTER TABLE `{$table_profiles}`
                ADD CONSTRAINT `{$fk_name}`
                FOREIGN KEY (`profiles_id`)
                REFERENCES `glpi_profiles` (`id`)
                ON DELETE CASCADE");
            $migration->displayMessage("  ✓ Added foreign key constraint");
        } catch (Exception $e) {
            $migration->displayWarning("  ⚠ Could not add foreign key: " . $e->getMessage());
        }
    }

    // ========================================
    // FINAL MESSAGE
    // ========================================
    $migration->displayMessage(__('Migration to 2.0.0 completed successfully!', 'flowbpmn'));
    $migration->displayMessage(__('Please test the plugin functionality', 'flowbpmn'));

    return true;
}
