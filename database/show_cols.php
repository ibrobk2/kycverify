<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();
$s = $db->query('SHOW COLUMNS FROM verification_logs');
foreach ($s as $r) echo $r['Field'] . "\n";
?>
