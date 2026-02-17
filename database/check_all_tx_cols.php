<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();
$tables = ['service_transactions', 'wallet_transactions', 'vtu_transactions', 'users'];
foreach ($tables as $table) {
    echo "--- $table ---\n";
    try {
        $s = $db->query("SHOW COLUMNS FROM $table");
        foreach ($s as $r) echo $r['Field'] . "\n";
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
?>
