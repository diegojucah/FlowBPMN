<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include ('../../../inc/includes.php');
header('Content-Type: application/json');
echo json_encode(['status' => 'ok', 'user' => Session::getLoginUserID()]);
