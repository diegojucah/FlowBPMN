<?php
// check_schema.php
$DB_HOST = getenv('GLPI_DB_HOST') ?: 'mariadb';
$DB_NAME = getenv('GLPI_DB_NAME') ?: 'glpi';
$DB_USER = getenv('GLPI_DB_USER') ?: 'glpi';
$DB_PASS = getenv('GLPI_DB_PASSWORD') ?: 'glpi';

$db = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error . "\n");
}

$result = $db->query("SHOW COLUMNS FROM glpi_plugin_flowbpmn_versions LIKE 'svg_content'");
if ($result && $row = $result->fetch_assoc()) {
    echo "Column: " . $row['Field'] . "\n";
    echo "Type: " . $row['Type'] . "\n";
    echo "Null: " . $row['Null'] . "\n";
} else {
    echo "Column svg_content NOT FOUND!\n";
}
$db->close();
?>
