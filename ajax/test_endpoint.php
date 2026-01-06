<?php
// Enable error display
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "Starting test...\n";

$glpi_root = dirname(__DIR__, 3);
echo "GLPI Root: $glpi_root\n";

$autoload_path = $glpi_root . '/glpi-11.0.1/vendor/autoload.php';
echo "Autoload path: $autoload_path\n";
echo "File exists: " . (file_exists($autoload_path) ? 'YES' : 'NO') . "\n";

if (!file_exists($autoload_path)) {
    // Try alternative path
    $autoload_path = $glpi_root . '/vendor/autoload.php';
    echo "Trying alternative: $autoload_path\n";
    echo "File exists: " . (file_exists($autoload_path) ? 'YES' : 'NO') . "\n";
}

if (file_exists($autoload_path)) {
    require_once $autoload_path;
    echo "Autoload loaded\n";
} else {
    die("Cannot find autoload.php\n");
}

use Glpi\Kernel\Kernel;
use Glpi\Application\Environment;

try {
    echo "Classes imported\n";
    
    $kernel = new Kernel(Environment::PRODUCTION->value, false);
    echo "Kernel created\n";
    
    $kernel->boot();
    echo "Kernel booted\n";
    
    global $DB;
    echo "DB available: " . (isset($DB) ? 'YES' : 'NO') . "\n";
    
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
