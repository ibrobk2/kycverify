<?php
// Integration Test Script

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../api/wallet-helper.php';

// Helper to make internal API calls
function callApi($endpoint, $method, $data = [], $token = null) {
    $url = 'http://localhost/lildone/api/' . $endpoint;
    
    // Since we are running from CLI, we might need to mock $_SERVER and require the file directly
    // But that's complex due to output buffering and exit() calls in the API files.
    // Instead, let's test the classes directly where possible, or use curl if the server was running.
    // Given the environment, we will test the Logic Classes directly.
    return null; 
}

echo "Starting Integration Tests...\n";

try {
    $db = (new Database())->getConnection();
    $walletHelper = new WalletHelper();

    // 1. Test User Creation (Direct DB Insert to avoid email sending issues in test)
    echo "1. Creating Test User...\n";
    $testEmail = 'test_' . time() . '@example.com';
    $testPass = password_hash('password123', PASSWORD_DEFAULT);
    
    $stmt = $db->prepare("INSERT INTO users (name, email, password, status, email_verified, wallet) VALUES (?, ?, ?, 'active', 1, 0.00)");
    $stmt->execute(['Test User', $testEmail, $testPass]);
    $userId = $db->lastInsertId();
    echo "User created with ID: $userId\n";

    // 2. Test Wallet Funding
    echo "2. Funding Wallet...\n";
    $initialBalance = $walletHelper->getBalance($userId);
    echo "Initial Balance: $initialBalance\n";
    
    $fundAmount = 500.00;
    $walletHelper->addTransaction($userId, $fundAmount, 'credit', 'Test Funding');
    $db->prepare("UPDATE users SET wallet = wallet + ? WHERE id = ?")->execute([$fundAmount, $userId]);
    
    $newBalance = $walletHelper->getBalance($userId);
    echo "New Balance: $newBalance\n";
    
    if ($newBalance != $initialBalance + $fundAmount) {
        throw new Exception("Wallet funding failed!");
    }
    echo "Wallet funding successful.\n";

    // 3. Test Service Price Retrieval
    echo "3. Checking Service Price...\n";
    $service = 'nin_verification';
    $price = $walletHelper->getServicePrice($service);
    echo "Price for $service: $price\n";
    
    if ($price <= 0) {
        throw new Exception("Invalid service price!");
    }

    // 4. Test Wallet Deduction Logic
    echo "4. Testing Wallet Deduction...\n";
    $deducted = $walletHelper->deductAmount($userId, $price, "Test Service Charge");
    
    if ($deducted) {
        $finalBalance = $walletHelper->getBalance($userId);
        echo "Deduction successful. Final Balance: $finalBalance\n";
        
        if ($finalBalance != $newBalance - $price) {
            throw new Exception("Balance mismatch after deduction!");
        }
    } else {
        throw new Exception("Deduction failed!");
    }

    // 5. Test Robosttech Service (Mocked)
    echo "5. Testing Robosttech Service Wrapper...\n";
    require_once __DIR__ . '/../api/RobosttechService.php';
    $robo = new RobosttechService();
    // We can't easily mock the HTTP request here without a library, 
    // so we just instantiate it to ensure no syntax errors.
    echo "RobosttechService instantiated successfully.\n";

    echo "\nALL TESTS PASSED!\n";
    
    // Cleanup
    // $db->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);

} catch (Exception $e) {
    echo "\nTEST FAILED: " . $e->getMessage() . "\n";
    exit(1);
}
?>
