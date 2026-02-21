<?php
error_reporting(0);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();
$s = $db->query("SELECT GROUP_CONCAT(COLUMN_NAME ORDER BY ORDINAL_POSITION SEPARATOR '|') as cols FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='wallet_transactions' AND TABLE_SCHEMA='" . DB_NAME . "'");
file_put_contents(__DIR__ . '/columns_output.txt', $s->fetchColumn());
echo "DONE";
?>
