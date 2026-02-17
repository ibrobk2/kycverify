<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();
$tables = ['service_transactions', 'wallet_transactions', 'vtu_transactions', 'verification_logs', 'birth_attestations'];
foreach ($tables as $t) {
    $c = $db->query("SHOW TABLES LIKE '$t'")->rowCount();
    echo "$t: " . ($c > 0 ? "EXISTS" : "MISSING") . "\n";
}
?>
