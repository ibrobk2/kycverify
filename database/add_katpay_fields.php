<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Add merchant_id and webhook_secret columns if they don't exist
    $columns = [
        'merchant_id' => "ALTER TABLE api_configurations ADD COLUMN merchant_id VARCHAR(100) DEFAULT NULL AFTER api_secret",
        'webhook_secret' => "ALTER TABLE api_configurations ADD COLUMN webhook_secret VARCHAR(255) DEFAULT NULL AFTER merchant_id"
    ];

    foreach ($columns as $column => $sql) {
        $check = $db->query("SHOW COLUMNS FROM api_configurations LIKE '$column'");
        if ($check->rowCount() == 0) {
            $db->exec($sql);
            echo "Column '$column' added successfully.\n";
        } else {
            echo "Column '$column' already exists.\n";
        }
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
