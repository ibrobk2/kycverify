<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$db = (new Database())->getConnection();
$cols = $db->query('SHOW COLUMNS FROM verification_logs')->fetchAll(PDO::FETCH_COLUMN);

echo "=== CURRENT COLUMNS ===\n";
foreach ($cols as $c) echo "  - $c\n";
echo "\n";

// All columns needed by verify-nin.php and verify-bvn.php:
// user_id, service_type, reference_number, status, response_data, provider, error_message
$needed = ['service_type', 'reference_number', 'status', 'response_data', 'provider', 'error_message'];
$missing = [];
foreach ($needed as $n) {
    if (!in_array($n, $cols)) {
        $missing[] = $n;
    }
}

if (empty($missing)) {
    echo "ALL REQUIRED COLUMNS EXIST!\n";
} else {
    echo "MISSING: " . implode(', ', $missing) . "\n";
    $alter = [];
    foreach ($missing as $m) {
        switch ($m) {
            case 'service_type': $alter[] = "ADD COLUMN service_type VARCHAR(50) DEFAULT NULL"; break;
            case 'reference_number': $alter[] = "ADD COLUMN reference_number VARCHAR(255) DEFAULT NULL"; break;
            case 'response_data': $alter[] = "ADD COLUMN response_data LONGTEXT DEFAULT NULL"; break;
            case 'provider': $alter[] = "ADD COLUMN provider VARCHAR(50) DEFAULT 'robosttech'"; break;
            case 'error_message': $alter[] = "ADD COLUMN error_message TEXT DEFAULT NULL"; break;
        }
    }
    if (!empty($alter)) {
        $sql = "ALTER TABLE verification_logs " . implode(", ", $alter);
        echo "Running: $sql\n";
        $db->exec($sql);
        echo "DONE - columns added!\n";
    }
}
?>
