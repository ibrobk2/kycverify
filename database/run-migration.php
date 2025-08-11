<?php
// Run database migration through web browser
require_once __DIR__ . '/../config/database.php';


echo "<h1>Database Migration</h1>";

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
            echo "<p>Wallet column added to users table.</p>";
        } else {
            echo "<p>Wallet column already exists in users table.</p>";
        }
    } catch (Exception $e) {
        echo "<p>Note: Could not add wallet column: " . $e->getMessage() . "</p>";
    }
    
    // Add otp column to users table if it doesn't exist
    try {
        $columnCheckQuery = "SHOW COLUMNS FROM users LIKE 'otp'";
        $columnCheckStmt = $db->prepare($columnCheckQuery);
        $columnCheckStmt->execute();
        
        if (!$columnCheckStmt->fetch()) {
            $addColumnQuery = "ALTER TABLE users ADD COLUMN otp VARCHAR(255) DEFAULT NULL AFTER password";
            $db->exec($addColumnQuery);
            echo "<p>OTP column added to users table.</p>";
        } else {
            echo "<p>OTP column already exists in users table.</p>";
        }
    } catch (Exception $e) {
        echo "<p>Note: Could not add otp column: " . $e->getMessage() . "</p>";
    }

    // Add otp_expires_at column to users table if it doesn't exist
    try {
        $columnCheckQuery = "SHOW COLUMNS FROM users LIKE 'otp_expires_at'";
        $columnCheckStmt = $db->prepare($columnCheckQuery);
        $columnCheckStmt->execute();
        
        if (!$columnCheckStmt->fetch()) {
            $addColumnQuery = "ALTER TABLE users ADD COLUMN otp_expires_at DATETIME DEFAULT NULL AFTER otp";
            $db->exec($addColumnQuery);
            echo "<p>OTP expires at column added to users table.</p>";
        } else {
            echo "<p>OTP expires at column already exists in users table.</p>";
        }
    } catch (Exception $e) {
        echo "<p>Note: Could not add otp_expires_at column: " . $e->getMessage() . "</p>";
    }
    
    echo "<p style='color: green;'>Database migration completed successfully!</p>";
    echo "<p>OTP verification table created.</p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Database migration failed: " . $e->getMessage() . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Migration failed: " . $e->getMessage() . "</p>";
}

echo "<p><a href='../'>Back to main page</a></p>";
?>
