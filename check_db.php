<?php
// Simple script to check versions in database
$host = 'localhost';
$db = 'glpi';
$user = 'glpi';
$pass = 'glpi';
$port = 3306;

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== FLOWS ===\n";
    $stmt = $pdo->query("SELECT id, itemtype, items_id, name FROM glpi_plugin_flowbpmn_flows ORDER BY id DESC LIMIT 5");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "Flow ID: {$row['id']}, {$row['itemtype']} #{$row['items_id']}, Name: {$row['name']}\n";
    }
    
    echo "\n=== VERSIONS ===\n";
    $stmt = $pdo->query("SELECT id, plugin_flowbpmn_flows_id, version_number, name FROM glpi_plugin_flowbpmn_versions ORDER BY id DESC LIMIT 10");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "Version ID: {$row['id']}, Flow: {$row['plugin_flowbpmn_flows_id']}, v{$row['version_number']}, Name: {$row['name']}\n";
    }
    
    echo "\n=== PNG DOCUMENTS ===\n";
    $stmt = $pdo->query("SELECT d.id, d.name, d.filename, di.itemtype, di.items_id FROM glpi_documents d LEFT JOIN glpi_documents_items di ON d.id = di.documents_id WHERE d.mime='image/png' ORDER BY d.id DESC LIMIT 5");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "Doc ID: {$row['id']}, {$row['itemtype']} #{$row['items_id']}, Name: {$row['name']}\n";
    }
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
