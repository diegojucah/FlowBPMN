<?php
// Test endpoint with detailed error logging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$log_file = __DIR__ . '/detailed_debug.log';
file_put_contents($log_file, "\n=== Test at " . date('Y-m-d H:i:s') . " ===\n", FILE_APPEND);

try {
    file_put_contents($log_file, "Step 1: Getting GLPI root\n", FILE_APPEND);
    $glpi_root = dirname(__DIR__, 3);
    file_put_contents($log_file, "GLPI Root: $glpi_root\n", FILE_APPEND);
    
    $autoload_path = $glpi_root . '/vendor/autoload.php';
    file_put_contents($log_file, "Autoload path: $autoload_path\n", FILE_APPEND);
    file_put_contents($log_file, "File exists: " . (file_exists($autoload_path) ? 'YES' : 'NO') . "\n", FILE_APPEND);
    
    if (!file_exists($autoload_path)) {
        throw new Exception("Autoload not found");
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
    
    file_put_contents($log_file, "Step 6: Checking user\n", FILE_APPEND);
    $user_id = Session::getLoginUserID();
    file_put_contents($log_file, "User ID: " . ($user_id ?: 'NULL') . "\n", FILE_APPEND);
    
    if (!$user_id) {
        file_put_contents($log_file, "ERROR: No user logged in\n", FILE_APPEND);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Not authenticated', 'user_id' => $user_id]);
        exit;
    }
    
    file_put_contents($log_file, "Step 7: Getting DB\n", FILE_APPEND);
    global $DB;
    file_put_contents($log_file, "DB available: " . (isset($DB) ? 'YES' : 'NO') . "\n", FILE_APPEND);
    
    file_put_contents($log_file, "SUCCESS: All steps completed\n", FILE_APPEND);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Test successful',
        'user_id' => $user_id,
        'db_available' => isset($DB)
    ]);
    
} catch (Throwable $e) {
    file_put_contents($log_file, "ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
    file_put_contents($log_file, "File: " . $e->getFile() . ":" . $e->getLine() . "\n", FILE_APPEND);
    file_put_contents($log_file, "Trace:\n" . $e->getTraceAsString() . "\n", FILE_APPEND);
    
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
