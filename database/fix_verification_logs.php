<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get current columns
    $stmt = $db->query("SHOW COLUMNS FROM verification_logs");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Current columns: " . implode(", ", $columns) . "\n\n";

    $alterSql = [];

    if (!in_array('reference_number', $columns)) {
        $alterSql[] = "ADD COLUMN reference_number VARCHAR(255) DEFAULT NULL AFTER service_type";
        echo "Adding: reference_number\n";
    }
    if (!in_array('response_data', $columns)) {
        $alterSql[] = "ADD COLUMN response_data TEXT DEFAULT NULL";
        echo "Adding: response_data\n";
    }

    if (!empty($alterSql)) {
        $sql = "ALTER TABLE verification_logs " . implode(", ", $alterSql);
        $db->exec($sql);
        echo "\nDone! Columns added successfully.\n";
    } else {
        echo "\nAll columns already exist, no changes needed.\n";
    }

    // Show final schema
    $stmt = $db->query("SHOW COLUMNS FROM verification_logs");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "\nFinal columns: " . implode(", ", $columns) . "\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
