<?php
// ajax/render_dropdown.php
// Renders a GLPI Dropdown HTML for a given itemtype

$glpi_root = dirname(__DIR__, 3);
include_once $glpi_root . '/inc/includes.php';

// Modern Boot
if (class_exists('Glpi\Kernel\Kernel')) {
    // If usage of Kernel is clearer in this version
    // But include includes.php already handles most generic setups.
    // Let's rely on standard session checks.
}

Session::checkLoginUser();
header("Content-Type: text/html; charset=UTF-8");

$itemtype = $_POST['itemtype'] ?? '';

if (!$itemtype || !class_exists($itemtype)) {
    echo "<div class='alert alert-danger'>Invalid Item Type</div>";
    exit;
}

// Ensure the user can view this itemtype
if (!Session::haveRight($itemtype::$rightname, READ)) {
    echo "<div class='alert alert-danger'>Access Denied</div>";
    exit;
}

// Unique ID for the dropdown to avoid conflicts
$rand = mt_rand();
$dom_id = 'flowbpmn_dropdown_' . $itemtype . '_' . $rand;

ob_start();
Dropdown::show($itemtype, [
    'name'   => 'flowbpmn_import_item_id', // Generic name
    'width'  => '100%',
    'display' => true,
    'rand'   => $rand,
    'entity' => $_SESSION['glpiactive_entity'] ?? 0,
    // Native search options
    'comments' => false,
    'toadd'    => []
]);
$html = ob_get_clean();

echo $html;
