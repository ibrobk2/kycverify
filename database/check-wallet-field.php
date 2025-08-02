<?php
// Check if wallet field exists in users table
require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if wallet column exists
    $columnCheckQuery = "SHOW COLUMNS FROM users LIKE 'wallet'";
    $columnCheckStmt = $db->prepare($columnCheckQuery);
    $columnCheckStmt->execute();
    
    $result = $columnCheckStmt->fetch();
    
    if ($result) {
        echo "Wallet column exists in users table:\n";
        echo "Field: " . $result['Field'] . "\n";
        echo "Type: " . $result['Type'] . "\n";
        echo "Default: " . $result['Default'] . "\n";
    } else {
        echo "Wallet column does not exist in users table.\n";
    }
    
    // Show all columns in users table
    echo "\nAll columns in users table:\n";
    $allColumnsQuery = "SHOW COLUMNS FROM users";
    $allColumnsStmt = $db->prepare($allColumnsQuery);
    $allColumnsStmt->execute();
    
    while ($row = $allColumnsStmt->fetch()) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
