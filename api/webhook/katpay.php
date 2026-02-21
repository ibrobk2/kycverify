<?php
/**
 * KatPay Webhook Handler
 * Processes incoming payment notifications to fund user wallets automatically.
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../wallet-helper.php';
require_once __DIR__ . '/../KatPayService.php';

// 1. Get Payload (Top of file to avoid stream consumption issues)
$payload = file_get_contents('php://input');

// Fallback to $_POST if php://input is empty (some environments might populate $_POST)
if (empty($payload) && !empty($_POST)) {
    $payload = json_encode($_POST);
}

// 2. Diagnostic Logging
require_once __DIR__ . '/../../config/config.php';
if (defined('DEBUG_MODE') && DEBUG_MODE) {
    $logDir = __DIR__ . '/../../logs';
    if (!is_dir($logDir)) mkdir($logDir, 0777, true);
    $logFile = $logDir . '/katpay_api.log';
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'UNKNOWN';
    $logEntry = "[" . date('Y-m-d H:i:s') . "] Webhook Received: $method\n";
    $logEntry .= "Headers: " . json_encode($headers) . "\n";
    $logEntry .= "Payload: " . (empty($payload) ? "(empty)" : $payload) . "\n";
    $logEntry .= "--------------------------------------------------\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../wallet-helper.php';
require_once __DIR__ . '/../KatPayService.php';

// 3. Get Headers
$signature = isset($_SERVER['HTTP_X_KATPAY_SIGNATURE']) ? $_SERVER['HTTP_X_KATPAY_SIGNATURE'] : '';
$timestamp = isset($_SERVER['HTTP_X_KATPAY_TIMESTAMP']) ? $_SERVER['HTTP_X_KATPAY_TIMESTAMP'] : '';

// Fallback if not found in $_SERVER (some environments strip HTTP_ prefix or change case)
if (empty($signature) || empty($timestamp)) {
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        $normalizedHeaders = array_change_key_case($headers, CASE_LOWER);
        
        if (empty($signature)) {
            $signature = isset($normalizedHeaders['x-katpay-signature']) ? $normalizedHeaders['x-katpay-signature'] : '';
        }
        if (empty($timestamp)) {
            $timestamp = isset($normalizedHeaders['x-katpay-timestamp']) ? $normalizedHeaders['x-katpay-timestamp'] : '';
        }
    }
}

// 4. Validate Request
if (empty($payload)) {
    http_response_code(400);
    echo json_encode(array('success' => false, 'message' => 'No payload received via php://input or $_POST'));
    exit;
}

if (empty($signature) || empty($timestamp)) {
    http_response_code(400);
    echo json_encode(array('success' => false, 'message' => 'Missing signature headers'));
    exit;
}

// 4. Verify Signature
$katpayService = new KatPayService();
// Verify signature using the service which has the secret loaded from DB/Config
if (!$katpayService->verifySignature($payload, $signature, $timestamp)) {
    error_log("KatPay Webhook Signature Mismatch. Sig: $signature, Time: $timestamp");
    http_response_code(401);
    echo json_encode(array('success' => false, 'message' => 'Invalid signature'));
    exit;
}

// 5. Process Event
$data = json_decode($payload, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(array('success' => false, 'message' => 'Invalid JSON payload'));
    exit;
}

$eventType = isset($data['event_type']) ? $data['event_type'] : '';

// Log event for debugging
if (defined('DEBUG_MODE') && DEBUG_MODE) {
    error_log("KatPay Webhook Event: $eventType");
}

if ($eventType === 'virtual_account.payment_received') {
    $txData = isset($data['data']['transaction']) ? $data['data']['transaction'] : array();
    $virtualAccount = isset($data['data']['virtual_account']) ? $data['data']['virtual_account'] : array();
    $customer = isset($data['data']['customer']) ? $data['data']['customer'] : array();
    
    // Extract relevant data
    $amount = isset($txData['order_amount']) ? (float)$txData['order_amount'] : 0;
    // Use reference if available, otherwise order_no
    $reference = isset($txData['reference']) ? $txData['reference'] : '';
    $orderNo = isset($txData['order_no']) ? $txData['order_no'] : '';
    $txRef = !empty($reference) ? $reference : $orderNo;
    
    $accountNumber = isset($virtualAccount['account_number']) ? $virtualAccount['account_number'] : '';
    
    if ($amount <= 0) {
        http_response_code(400);
        echo json_encode(array('success' => false, 'message' => 'Invalid amount'));
        exit;
    }
    
    if (empty($accountNumber)) {
        http_response_code(400);
        echo json_encode(array('success' => false, 'message' => 'Missing account number'));
        exit;
    }
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        $walletHelper = new WalletHelper();
        
        // Find user by virtual account number
        error_log("KatPay Webhook: Looking up user with account: $accountNumber");
        $userStmt = $db->prepare("SELECT id FROM users WHERE virtual_account_number = ?");
        $userStmt->execute(array($accountNumber));
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            error_log("KatPay Webhook: No user found for account: $accountNumber");
            // Return 200 even if user not found to stop webhook retries, but log error
            http_response_code(200); 
            echo json_encode(array('success' => false, 'message' => 'User not found'));
            exit;
        }
        
        $userId = $user['id'];
        error_log("KatPay Webhook: Found user $userId for account $accountNumber");
        
        // Check for duplicate transaction
        $checkStmt = $db->prepare("SELECT id FROM wallet_transactions WHERE reference = ?");
        $checkStmt->execute(array($txRef));
        if ($checkStmt->fetch()) {
            http_response_code(200);
            echo json_encode(array('success' => true, 'message' => 'Transaction already processed'));
            exit;
        }
        
        // Fund Wallet
        $details = "Deposit via KatPay (Ref: $txRef)";
        error_log("KatPay Webhook: Funding wallet for User $userId, Amount $amount, Ref $txRef");
        if ($walletHelper->addAmount($userId, $amount, $details, $txRef)) {
            error_log("KatPay Webhook: SUCCESS - Wallet funded for User $userId, Amount $amount");
            http_response_code(200);
            echo json_encode(array('success' => true, 'message' => 'Payment processed successfully'));
        } else {
            error_log("KatPay Webhook: FAILED to fund wallet for User $userId, Ref $txRef");
            http_response_code(500);
            echo json_encode(array('success' => false, 'message' => 'Failed to fund wallet'));
        }
        
    } catch (Exception $e) {
        error_log("KatPay Webhook Error: " . $e->getMessage() . " | File: " . $e->getFile() . " | Line: " . $e->getLine());
        error_log("KatPay Webhook Trace: " . $e->getTraceAsString());
        http_response_code(500);
        echo json_encode(array('success' => false, 'message' => 'Internal server error'));
    }

} else {
    // Handle other event types
    http_response_code(200);
    echo json_encode(array('success' => true, 'message' => 'Event received'));
}
?>
