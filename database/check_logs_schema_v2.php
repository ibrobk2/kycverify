<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    $stmt = $db->query("SHOW COLUMNS FROM verification_logs");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "COLUMNS: " . implode(", ", array_column($columns, 'Field'));

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
