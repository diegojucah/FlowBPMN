<?php
include ('../../../inc/includes.php');

header("Content-Type: text/plain; charset=UTF-8");
Html::header_nocache();

// Force PT_BR for testing if needed, or rely on user session
// $_SESSION['glpi_language'] = 'pt_BR'; 
// Session::loadLanguage();

echo "Current Language: " . $_SESSION['glpilanguage'] . "\n";
echo "Testing translation for 'Import': " . __('Import', 'flowbpmn') . "\n";
echo "Testing translation for 'Import from %s': " . sprintf(__('Import from %s', 'flowbpmn'), 'TEST') . "\n";
echo "Testing existing key 'BPMN Flow': " . __('BPMN Flow', 'flowbpmn') . "\n";
