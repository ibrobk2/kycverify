<?php
/**
 * KatPay Webhook Handler
 * Processes incoming payment notifications to fund user wallets automatically.
 * 
 * Event: virtual_account.payment_received
 * Signature: HMAC SHA-256 of (timestamp . '.' . payload) using webhook secret
 * Headers: X-Katpay-Signature, X-Katpay-Timestamp
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../wallet-helper.php';
require_once __DIR__ . '/../KatPayService.php';

// 1. Get Payload and Headers
$payload = file_get_contents('php://input');
$headers = getallheaders();

// Normalize header keys (some servers lowercase them)
$normalizedHeaders = [];
foreach ($headers as $key => $value) {
    $normalizedHeaders[strtolower($key)] = $value;
}

$signature = isset($normalizedHeaders['x-katpay-signature']) ? $normalizedHeaders['x-katpay-signature'] : '';
$timestamp = isset($normalizedHeaders['x-katpay-timestamp']) ? $normalizedHeaders['x-katpay-timestamp'] : '';

if (!$payload) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'No payload']));
}

// 2. Verify Signature
$katpayService = new KatPayService();
if (!$katpayService->verifySignature($payload, $signature, $timestamp)) {
    error_log("KatPay Webhook Signature Mismatch");
    if (!defined('DEBUG_MODE') || !DEBUG_MODE) {
        http_response_code(401);
        exit(json_encode(['success' => false, 'message' => 'Invalid signature']));
    }
}

$data = json_decode($payload, true);

if (defined('DEBUG_MODE') && DEBUG_MODE) {
    error_log("KatPay Webhook Payload: " . $payload);
}

// 3. Process virtual_account.payment_received Event
$eventType = isset($data['event_type']) ? $data['event_type'] : '';

if ($eventType === 'virtual_account.payment_received') {
    $txData = isset($data['data']['transaction']) ? $data['data']['transaction'] : [];
    $virtualAccount = isset($data['data']['virtual_account']) ? $data['data']['virtual_account'] : [];
    
    $orderNo = isset($txData['order_no']) ? $txData['order_no'] : '';
    $amount = isset($txData['order_amount']) ? (float)$txData['order_amount'] : 0;
    $reference = isset($txData['reference']) ? $txData['reference'] : $orderNo;
    $accountNumber = isset($virtualAccount['account_number']) ? $virtualAccount['account_number'] : '';

    if ($amount <= 0) {
        http_response_code(400);
        exit(json_encode(['success' => false, 'message' => 'Invalid amount']));
    }

    if (empty($accountNumber)) {
        error_log("KatPay Webhook: No account number in payload");
        http_response_code(400);
        exit(json_encode(['success' => false, 'message' => 'Missing account number']));
    }

    try {
        $database = new Database();
        $db = $database->getConnection();
        $walletHelper = new WalletHelper();

        // Find user by virtual account number
        $userStmt = $db->prepare("SELECT id FROM users WHERE virtual_account_number = ?");
        $userStmt->execute([$accountNumber]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            error_log("KatPay Webhook: No user found for account: $accountNumber");
            http_response_code(400);
            exit(json_encode(['success' => false, 'message' => 'User not found for this account']));
        }

        $userId = $user['id'];

        // Check if transaction was already processed (idempotency)
        $checkStmt = $db->prepare("SELECT id FROM wallet_transactions WHERE transaction_ref = ?");
        $checkStmt->execute([$reference]);
        if ($checkStmt->fetch()) {
            http_response_code(200);
            exit(json_encode(['success' => true, 'message' => 'Transaction already processed']));
        }

        $db->beginTransaction();

        // Credit wallet using the improved WalletHelper which now handles references
        $details = "Automated Deposit via KatPay (Ref: $reference)";
        if ($walletHelper->addAmount($userId, $amount, $details, $reference)) {
            $db->commit();
            
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Wallet funded successfully']);
        } else {
            $db->rollBack();
            error_log("Failed to add amount to wallet via KatPay webhook. User: $userId, Ref: $reference");
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Internal server error during wallet funding']);
        }
    } catch (Exception $e) {
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }
        error_log("KatPay Webhook Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'An error occurred during processing']);
    }
} else {
    // Acknowledge other events
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Event ignored: ' . $eventType]);
}
?>
