<?php
/**
 * PaymentPoint Webhook Handler
 * Processes incoming payment notifications to fund user wallets automatically.
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../wallet-helper.php';
require_once __DIR__ . '/../PaymentPointService.php';

// 1. Get Payload and Signature
$payload = file_get_contents('php://input');
$headers = getallheaders();
$signature = isset($headers['X-PaymentPoint-Signature']) ? $headers['X-PaymentPoint-Signature'] : '';

if (!$payload) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'No payload']));
}

// 2. Verify Signature
$ppService = new PaymentPointService();
if (!$ppService->verifySignature($payload, $signature)) {
    // For local development or missing keys, we log but don't exit unless in production
    error_log("PaymentPoint Webhook Signature Mismatch");
    if (!DEBUG_MODE) {
        http_response_code(401);
        exit(json_encode(['success' => false, 'message' => 'Invalid signature']));
    }
}

$data = json_decode($payload, true);

if (DEBUG_MODE) {
    error_log("PaymentPoint Webhook Payload: " . $payload);
}

// 3. Process Success Event
// Example structure: { "event": "payment.success", "data": { "reference": "...", "amount": 1000, "customer": { "reference": "USER_123" } } }
if (isset($data['event']) && $data['event'] === 'payment.success') {
    $txData = $data['data'];
    $reference = isset($txData['reference']) ? $txData['reference'] : '';
    $amount = isset($txData['amount']) ? (float)$txData['amount'] : 0;
    $customerRef = isset($txData['customer']['reference']) ? $txData['customer']['reference'] : '';

    if ($amount <= 0) {
        http_response_code(400);
        exit(json_encode(['success' => false, 'message' => 'Invalid amount']));
    }

    // Extract User ID from customer reference
    if (preg_match('/^USER_(\d+)$/', $customerRef, $matches)) {
        $userId = $matches[1];

        try {
            $database = new Database();
            $db = $database->getConnection();
            $walletHelper = new WalletHelper();

            // Check if transaction was already processed (idempotency)
            $checkStmt = $db->prepare("SELECT id FROM wallet_transactions WHERE reference = ?");
            $checkStmt->execute([$reference]);
            if ($checkStmt->fetch()) {
                http_response_code(200);
                exit(json_encode(['success' => true, 'message' => 'Transaction already processed']));
            }

            // Credit wallet using WalletHelper (handles its own transaction)
            $details = "Automated Deposit via PaymentPoint (Ref: $reference)";
            if ($walletHelper->addAmount($userId, $amount, $details, $reference)) {
                http_response_code(200);
                echo json_encode(['success' => true, 'message' => 'Wallet funded successfully']);
            } else {
                error_log("Failed to add amount to wallet via PaymentPoint webhook. User: $userId, Ref: $reference");
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Internal server error during wallet funding']);
            }
        } catch (Exception $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            error_log("PaymentPoint Webhook Error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'An error occurred during processing']);
        }
    } else {
        error_log("Invalid customer reference in PaymentPoint webhook: $customerRef");
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid customer reference']);
    }
} else {
    // Acknowledge other events but do nothing
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Event ignored']);
}
?>
