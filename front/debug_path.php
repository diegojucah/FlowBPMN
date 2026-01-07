<?php
include ('../../../inc/includes.php');
header('Content-Type: text/plain');
echo "GLPI Root Doc: " . $CFG_GLPI["root_doc"] . "\n";
echo "Profile Form Path: " . $CFG_GLPI["root_doc"] . "/plugins/flowbpmn/front/profile.form.php\n";
echo "User ID: " . Session::getLoginUserID() . "\n";
