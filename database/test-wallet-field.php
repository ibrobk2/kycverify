<?php
// Test wallet field functionality
require_once __DIR__ . '/../config/database.php';

echo "Testing wallet field functionality...\n";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Create a test user with wallet field
    $testEmail = "wallettest" . time() . "@example.com";
    $hashedPassword = password_hash("testpassword", PASSWORD_DEFAULT);
    
    $insertQuery = "INSERT INTO users (name, email, password, status, created_at) VALUES (?, ?, ?, 'active', NOW())";
    $insertStmt = $db->prepare($insertQuery);
    $insertStmt->execute(["Wallet Test User", $testEmail, $hashedPassword]);
    
    $userId = $db->lastInsertId();
    echo "Created test user with ID: " . $userId . "\n";
    
    // Retrieve the user to check wallet field
    $selectQuery = "SELECT id, name, email, wallet FROM users WHERE id = ?";
    $selectStmt = $db->prepare($selectQuery);
    $selectStmt->execute([$userId]);
    $user = $selectStmt->fetch();
    
    echo "User details:\n";
    echo "ID: " . $user['id'] . "\n";
    echo "Name: " . $user['name'] . "\n";
    echo "Email: " . $user['email'] . "\n";
    echo "Wallet: " . $user['wallet'] . "\n";
    
    // Update wallet value
    $updateQuery = "UPDATE users SET wallet = ? WHERE id = ?";
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->execute([100.50, $userId]);
    
    echo "Updated wallet to 100.50\n";
    
    // Retrieve updated user
    $selectStmt->execute([$userId]);
    $user = $selectStmt->fetch();
    
    echo "Updated user wallet: " . $user['wallet'] . "\n";
    
    // Clean up test user
    $deleteQuery = "DELETE FROM users WHERE id = ?";
    $deleteStmt = $db->prepare($deleteQuery);
    $deleteStmt->execute([$userId]);
    
    echo "Cleaned up test user\n";
    
    echo "Wallet field test completed successfully!\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
