<?php
// Test file to check GLPI includes
$cwd = getcwd();
echo "Current directory: $cwd\n";

$include_path = '../../../inc/includes.php';
echo "Include path: $include_path\n";
echo "File exists: " . (file_exists($include_path) ? 'YES' : 'NO') . "\n";
echo "Real path: " . realpath($include_path) . "\n";

include ($include_path);

echo "After include:\n";
echo "Session class: " . (class_exists('Session') ? 'EXISTS' : 'NOT FOUND') . "\n";
echo "GLPI_ROOT defined: " . (defined('GLPI_ROOT') ? GLPI_ROOT : 'NO') . "\n";
