<?php
require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    $sql = "
    CREATE TABLE IF NOT EXISTS bvn_modification_uploads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        reference VARCHAR(50) NOT NULL,
        file_name VARCHAR(255) NOT NULL,
        file_path VARCHAR(255) NOT NULL,
        status ENUM('pending', 'processing', 'completed', 'rejected') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_user_id (user_id),
        INDEX idx_reference (reference),
        INDEX idx_status (status)
    );
    ";

    $db->exec($sql);
    echo "Table bvn_modification_uploads created successfully.\n";
} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage() . "\n";
}
?>
