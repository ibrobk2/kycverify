<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();
$tables = ['service_transactions', 'wallet_transactions', 'vtu_transactions', 'users'];
foreach ($tables as $table) {
    echo "[$table]\n";
    try {
        $s = $db->query("DESCRIBE $table");
        while($r = $s->fetch(PDO::FETCH_ASSOC)) {
            echo "  " . $r['Field'] . " (" . $r['Type'] . ")\n";
        }
    } catch (Exception $e) {
        echo "  MISSING or Error: " . $e->getMessage() . "\n";
    }
}
?>
