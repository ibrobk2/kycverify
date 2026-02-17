<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
try {
    $database = new Database();
    $db = $database->getConnection();
    $defaults = [
        ['service_name' => 'robosttech', 'base_url' => 'https://api.robosttech.com/v1', 'status' => 'inactive'],
        ['service_name' => 'dataverify', 'base_url' => 'https://api.dataverify.com.ng/v1', 'status' => 'active'],
        ['service_name' => 'gafiapay', 'base_url' => 'https://api.gafiapay.com/v1', 'status' => 'inactive'],
        ['service_name' => 'monnify', 'base_url' => 'https://api.monnify.com/v1', 'status' => 'inactive'],
        ['service_name' => 'katpay', 'base_url' => 'https://api.katpay.co/v1', 'status' => 'inactive']
    ];
    foreach ($defaults as $default) {
        $stmt = $db->prepare("SELECT id FROM api_configurations WHERE service_name = ?");
        $stmt->execute([$default['service_name']]);
        if (!$stmt->fetch()) {
            $stmt = $db->prepare("INSERT INTO api_configurations (service_name, base_url, status) VALUES (?, ?, ?)");
            $stmt->execute([$default['service_name'], $default['base_url'], $default['status']]);
            echo "Registered " . $default['service_name'] . "\n";
        } else {
            echo $default['service_name'] . " already exists\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
