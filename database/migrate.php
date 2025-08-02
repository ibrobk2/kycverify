<?php
// Database Migration Script
require_once __DIR__ . '/../config/database.php';


try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Create OTP verification table
    $otpTableQuery = "
    CREATE TABLE IF NOT EXISTS otp_verifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        otp_code VARCHAR(6) NOT NULL,
        expires_at TIMESTAMP NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_user_id (user_id),
        INDEX idx_otp_code (otp_code),
        INDEX idx_expires_at (expires_at)
    )";
    
    $db->exec($otpTableQuery);
    
    // Add wallet column to users table if it doesn't exist
    try {
        $columnCheckQuery = "SHOW COLUMNS FROM users LIKE 'wallet'";
        $columnCheckStmt = $db->prepare($columnCheckQuery);
        $columnCheckStmt->execute();
        
        if (!$columnCheckStmt->fetch()) {
            $addColumnQuery = "ALTER TABLE users ADD COLUMN wallet DECIMAL(10,2) DEFAULT 0.00 AFTER company";
            $db->exec($addColumnQuery);
            echo "Wallet column added to users table.\n";
        } else {
            echo "Wallet column already exists in users table.\n";
        }
    } catch (Exception $e) {
        echo "Note: Could not add wallet column: " . $e->getMessage() . "\n";
    }
    
    echo "Database migration completed successfully!\n";
    echo "OTP verification table created.\n";
    
} catch (PDOException $e) {
    echo "Database migration failed: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
?>
