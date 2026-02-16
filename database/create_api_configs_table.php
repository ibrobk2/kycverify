<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    $sql = "CREATE TABLE IF NOT EXISTS api_configurations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        service_name VARCHAR(50) NOT NULL UNIQUE,
        base_url VARCHAR(255) NOT NULL,
        api_key VARCHAR(255) DEFAULT NULL,
        api_secret VARCHAR(255) DEFAULT NULL,
        status ENUM('active', 'inactive') DEFAULT 'inactive',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";

    $db->exec($sql);
    echo "Table 'api_configurations' created successfully.\n";

    // Seed defaults if empty
    $defaults = [
        [
            'service_name' => 'robosttech', 
            'base_url' => defined('ROBOSTTECH_BASE_URL') ? ROBOSTTECH_BASE_URL : 'https://api.robosttech.com/v1',
            'api_key' => defined('ROBOSTTECH_API_KEY') ? ROBOSTTECH_API_KEY : '',
            'status' => 'inactive'
        ],
        [
            'service_name' => 'dataverify', 
            'base_url' => defined('DATAVERIFY_BASE_URL') ? DATAVERIFY_BASE_URL : 'https://api.dataverify.com.ng/v1',
            'api_key' => defined('DATAVERIFY_API_KEY') ? DATAVERIFY_API_KEY : '',
            'status' => 'active' // Active by default as requested
        ],
        [
            'service_name' => 'gafiapay', 
            'base_url' => defined('GAFIAPAY_BASE_URL') ? GAFIAPAY_BASE_URL : 'https://api.gafiapay.com/v1', 
            'status' => 'inactive'
        ]
    ];

    foreach ($defaults as $default) {
        $stmt = $db->prepare("INSERT IGNORE INTO api_configurations (service_name, base_url, api_key, status) VALUES (?, ?, ?, ?)");
        $stmt->execute([$default['service_name'], $default['base_url'], $default['api_key'], $default['status']]);
    }
    echo "Default configurations seeded.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
