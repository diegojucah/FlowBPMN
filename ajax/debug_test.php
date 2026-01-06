<?php
/**
 * Test endpoint to debug the 500 error
 */

// Enable error display
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log to file
$log_file = __DIR__ . '/debug_log.txt';
file_put_contents($log_file, "=== Test started at " . date('Y-m-d H:i:s') . " ===\n", FILE_APPEND);

try {
    file_put_contents($log_file, "Step 1: Getting GLPI root\n", FILE_APPEND);
    $glpi_root = dirname(__DIR__, 3);
    file_put_contents($log_file, "GLPI Root: $glpi_root\n", FILE_APPEND);
    
    $autoload_path = $glpi_root . '/vendor/autoload.php';
    file_put_contents($log_file, "Autoload path: $autoload_path\n", FILE_APPEND);
    file_put_contents($log_file, "File exists: " . (file_exists($autoload_path) ? 'YES' : 'NO') . "\n", FILE_APPEND);
    
    if (!file_exists($autoload_path)) {
        throw new Exception("Autoload not found at: $autoload_path");
    }
    
    file_put_contents($log_file, "Step 2: Loading autoload\n", FILE_APPEND);
    require_once $autoload_path;
    
    file_put_contents($log_file, "Step 3: Importing classes\n", FILE_APPEND);
    use Glpi\Kernel\Kernel;
    use Glpi\Application\Environment;
    
    file_put_contents($log_file, "Step 4: Creating kernel\n", FILE_APPEND);
    $kernel = new Kernel(Environment::PRODUCTION->value, false);
    
    file_put_contents($log_file, "Step 5: Booting kernel\n", FILE_APPEND);
    $kernel->boot();
    
    file_put_contents($log_file, "Step 6: Checking DB\n", FILE_APPEND);
    global $DB;
    
    if (!isset($DB)) {
        throw new Exception("DB not available after boot");
    }
    
    file_put_contents($log_file, "Step 7: Checking session\n", FILE_APPEND);
    Session::checkLoginUser();
    
    file_put_contents($log_file, "Step 8: Getting parameters\n", FILE_APPEND);
    $itemtype = $_GET['itemtype'] ?? 'Ticket';
    file_put_contents($log_file, "Itemtype: $itemtype\n", FILE_APPEND);
    
    file_put_contents($log_file, "SUCCESS: All steps completed\n", FILE_APPEND);
    
    echo json_encode([
        'success' => true,
        'message' => 'Test successful',
        'itemtype' => $itemtype
    ]);
    
} catch (Throwable $e) {
    file_put_contents($log_file, "ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
    file_put_contents($log_file, "File: " . $e->getFile() . ":" . $e->getLine() . "\n", FILE_APPEND);
    file_put_contents($log_file, "Trace:\n" . $e->getTraceAsString() . "\n", FILE_APPEND);
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
