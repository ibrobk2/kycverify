<?php
// Add wallet field to users table
require_once __DIR__ . '/../config/database.php';

echo "<h1>Database Migration - Add Wallet Field</h1>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if wallet column already exists
    $columnCheckQuery = "SHOW COLUMNS FROM users LIKE 'wallet'";
    $columnCheckStmt = $db->prepare($columnCheckQuery);
    $columnCheckStmt->execute();
    
    if ($columnCheckStmt->fetch()) {
        echo "<p style='color: orange;'>Wallet column already exists in users table.</p>";
    } else {
        // Add wallet column to users table
        $addColumnQuery = "ALTER TABLE users ADD COLUMN wallet DECIMAL(10,2) DEFAULT 0.00 AFTER company";
        $db->exec($addColumnQuery);
        
        echo "<p style='color: green;'>Wallet column added successfully to users table!</p>";
    }
    
    echo "<p style='color: green;'>Database migration completed successfully!</p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Database migration failed: " . $e->getMessage() . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Migration failed: " . $e->getMessage() . "</p>";
}

echo "<p><a href='../'>Back to main page</a></p>";
?>
