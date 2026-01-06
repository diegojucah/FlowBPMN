<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
echo "Start include...\n";
if (!defined('GLPI_ROOT')) {
    define('GLPI_ROOT', dirname(dirname(dirname(__DIR__))));
}
require (GLPI_ROOT . '/inc/includes.php');
echo "Include success.\n";
