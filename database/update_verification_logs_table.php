<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Check if columns exist
    $stmt = $db->query("SHOW COLUMNS FROM verification_logs");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $alterSql = [];

    if (!in_array('service_type', $columns)) {
        $alterSql[] = "ADD COLUMN service_type VARCHAR(50) AFTER user_id";
    }

    if (!in_array('provider', $columns)) {
        $alterSql[] = "ADD COLUMN provider VARCHAR(50) DEFAULT 'robosttech' AFTER status";
    }
    
    // Also check for error_message if I used it
    if (!in_array('error_message', $columns)) {
         $alterSql[] = "ADD COLUMN error_message TEXT DEFAULT NULL";
    }

    if (!empty($alterSql)) {
        $sql = "ALTER TABLE verification_logs " . implode(", ", $alterSql);
        $db->exec($sql);
        echo "Columns added successfully: " . implode(", ", $alterSql) . "\n";
    } else {
        echo "All columns already exist.\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
