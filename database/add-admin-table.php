<?php
// Database migration script to add admin table and initial admin user
require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Create admins table
    $sql = "CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
        last_login TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_email (email),
        INDEX idx_status (status)
    )";
    
    $db->exec($sql);
    
    // Check if admin already exists
    $checkQuery = "SELECT COUNT(*) FROM admins WHERE email = 'admin@agentify.com'";
    $stmt = $db->prepare($checkQuery);
    $stmt->execute();
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        // Insert initial admin user
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        $insertQuery = "INSERT INTO admins (name, email, password) VALUES (?, ?, ?)";
        $stmt = $db->prepare($insertQuery);
        $stmt->execute(['Admin User', 'admin@agentify.com', $password]);
        
        echo "Admin table created and initial admin user added successfully.\n";
        echo "Login: admin@agentify.com\n";
        echo "Password: admin123\n";
    } else {
        echo "Admin user already exists.\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
