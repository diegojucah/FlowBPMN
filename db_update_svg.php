<?php
// db_update_svg.php
$DB_HOST = getenv('GLPI_DB_HOST') ?: 'mariadb';
$DB_NAME = getenv('GLPI_DB_NAME') ?: 'glpi';
$DB_USER = getenv('GLPI_DB_USER') ?: 'glpi';
$DB_PASS = getenv('GLPI_DB_PASSWORD') ?: 'glpi';

$db = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error . "\n");
}

// Check if column exists
$result = $db->query("SHOW COLUMNS FROM `glpi_plugin_flowbpmn_versions` LIKE 'svg_content'");
if ($result && $result->num_rows > 0) {
    echo "Column 'svg_content' already exists.\n";
} else {
    echo "Adding 'svg_content' column...\n";
    $sql = "ALTER TABLE `glpi_plugin_flowbpmn_versions` ADD COLUMN `svg_content` LONGTEXT AFTER `bpmn_xml`";
    if ($db->query($sql)) {
        echo "Column added successfully.\n";
    } else {
        echo "Error adding column: " . $db->error . "\n";
    }
}
$db->close();
?>
