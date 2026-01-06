<?php
// Simple test to see if we can even reach this file
error_log("=== FLOWBPMN DEBUG: File reached ===");
file_put_contents(__DIR__ . '/simple_test.log', date('Y-m-d H:i:s') . " - File reached\n", FILE_APPEND);

echo json_encode(['success' => true, 'message' => 'Simple test successful']);
