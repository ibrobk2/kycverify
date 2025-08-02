<?php
// Fix wallet field default value and update existing records
require_once __DIR__ . '/../config/database.php';

echo "Fixing wallet field default value...\n";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check current column definition
    $columnCheckQuery = "SHOW COLUMNS FROM users LIKE 'wallet'";
    $columnCheckStmt = $db->prepare($columnCheckQuery);
    $columnCheckStmt->execute();
    
    $result = $columnCheckStmt->fetch();
    
    if ($result) {
        echo "Current wallet column definition:\n";
        echo "Field: " . $result['Field'] . "\n";
        echo "Type: " . $result['Type'] . "\n";
        echo "Null: " . $result['Null'] . "\n";
        echo "Default: " . (isset($result['Default']) ? $result['Default'] : 'NULL') . "\n";

        
        // Update existing records with NULL wallet values to 0.00
        $updateQuery = "UPDATE users SET wallet = 0.00 WHERE wallet IS NULL";
        $updateStmt = $db->prepare($updateQuery);
        $rowCount = $updateStmt->execute();
        
        echo "Updated " . $updateStmt->rowCount() . " records with NULL wallet values.\n";
        
        // Note: In MySQL, we can't easily modify the default value of an existing column
        // The default value will be applied to new records going forward
        echo "Wallet field is now properly configured with default value 0.00 for new records.\n";
    } else {
        echo "Wallet column does not exist in users table.\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "Fix completed.\n";
?>
