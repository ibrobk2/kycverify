<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
try {
    $database = new Database();
    $db = $database->getConnection();
    $stmt = $db->query("SELECT id, name, email, virtual_account_number, bank_name, account_name FROM users WHERE virtual_account_number IS NOT NULL LIMIT 20");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($rows, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
