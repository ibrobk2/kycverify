<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
try {
    $db = (new Database())->getConnection();
    $stmt = $db->prepare("SELECT * FROM api_configurations WHERE service_name = 'katpay'");
    $stmt->execute();
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($config) {
        echo "Database Config Found:\n";
        print_r($config);
    } else {
        echo "No Database Config found for katpay. Falling back to config.php constants.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
