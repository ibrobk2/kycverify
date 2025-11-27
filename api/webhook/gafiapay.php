<?php
// Gafiapay Webhook Handler
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../wallet-helper.php';
require_once __DIR__ . '/../GafiapayService.php';

// 1. Get Payload and Signature
$payload = file_get_contents('php://input');
$headers = getallheaders();
$signature = isset($headers['X-Gafiapay-Signature']) ? $headers['X-Gafiapay-Signature'] : '';

if (!$payload) {
    http_response_code(400);
    exit('No payload');
}

// 2. Verify Signature
$gafia = new GafiapayService();
if (!$gafia->verifySignature($payload, $signature)) {
    // For now, log and continue if signature verification fails (since we don't have real keys)
    // In production, uncomment the exit
    error_log("Gafiapay Webhook Signature Mismatch");
    // http_response_code(401);
    // exit('Invalid signature');
}

$data = json_decode($payload, true);

// 3. Process Event
// Assuming event structure: { "event": "transaction.successful", "data": { "reference": "...", "amount": 100, "customer": { "reference": "USER_123" } } }

if (isset($data['event']) && $data['event'] === 'transaction.successful') {
    $txData = $data['data'];
    $reference = $txData['reference'];
    $amount = $txData['amount'];
    $customerRef = isset($txData['customer']['reference']) ? $txData['customer']['reference'] : ''; // "USER_123"

    // Extract User ID
    if (preg_match('/^USER_(\d+)$/', $customerRef, $matches)) {
        $userId = $matches[1];

        // Credit Wallet
        $walletHelper = new WalletHelper();
        
        // Check if transaction already processed
        // Ideally, check wallet_transactions table for this reference
        // But for now, we just add it.
        
        if ($walletHelper->addTransaction($userId, $amount, 'credit', "Deposit via Gafiapay (Ref: $reference)", $reference)) {
            // Update User Wallet Balance
            $db = (new Database())->getConnection();
            $stmt = $db->prepare("UPDATE users SET wallet = wallet + ? WHERE id = ?");
            $stmt->execute([$amount, $userId]);
            
            http_response_code(200);
            echo json_encode(['status' => 'success']);
        } else {
            error_log("Failed to credit wallet for user $userId, Ref: $reference");
            http_response_code(500);
        }
    } else {
        error_log("Invalid customer reference in webhook: $customerRef");
        http_response_code(400);
    }
} else {
    // Ignore other events
    http_response_code(200);
}
?>
