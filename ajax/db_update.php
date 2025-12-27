<?php
// Simple script to create templates table manually
$glpi_root = dirname(__DIR__, 3);
require_once $glpi_root . '/vendor/autoload.php';

use Glpi\Kernel\Kernel;
use Glpi\Application\Environment;

$kernel = new Kernel(Environment::PRODUCTION->value, false);
$kernel->boot();

global $DB;

$user_id = Session::getLoginUserID();
if (!$user_id) die("Login required");

if (!$DB->tableExists('glpi_plugin_flowbpmn_templates')) {
    $query = "CREATE TABLE `glpi_plugin_flowbpmn_templates` (
        `id` int unsigned NOT NULL AUTO_INCREMENT,
        `entities_id` int unsigned NOT NULL DEFAULT '0',
        `is_recursive` tinyint NOT NULL DEFAULT '0',
        `name` varchar(255) DEFAULT NULL,
        `comment` text,
        `bpmn_xml` longtext,
        `svg_content` longtext,
        `is_active` tinyint NOT NULL DEFAULT '1',
        `is_public` tinyint NOT NULL DEFAULT '0',
        `users_id` int unsigned NOT NULL DEFAULT '0',
        `date_creation` timestamp NULL DEFAULT NULL,
        `date_mod` timestamp NULL DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `entities_id` (`entities_id`),
        KEY `is_recursive` (`is_recursive`),
        KEY `is_public` (`is_public`),
        KEY `users_id` (`users_id`),
        KEY `date_mod` (`date_mod`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC";
    
    $DB->doQuery($query);
    echo "Tabela templates criada com sucesso!";
} else {
    echo "Tabela templates já existe.";
}
