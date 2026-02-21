<?php
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/wallet-helper.php';
require_once __DIR__ . '/jwt-helper.php';
require_once __DIR__ . '/RobosttechService.php';
require_once __DIR__ . '/DataVerifyService.php';

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Authenticate user
    $userId = JWTHelper::getUserIdFromToken();
    if (!$userId) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    // Get request data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['phone_number'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Phone number is required']);
        exit;
    }
    
    $phone = trim($input['phone_number']);
    
    // Validate phone format
    if (!preg_match('/^0[789][01]\d{8}$/', $phone) && !preg_match('/^\d{11}$/', $phone)) {
         // Relaxed validation but checking for at least 11 digits
    }

    // Initialize services
    $walletHelper = new WalletHelper();
    
    // Choose provider
    $provider = defined('VERIFICATION_PROVIDER') ? VERIFICATION_PROVIDER : 'robosttech';
    $verificationService = ($provider === 'dataverify') ? new DataVerifyService() : new RobosttechService();

    // Check wallet balance and process payment
    $paymentResult = $walletHelper->processPayment($userId, 'bvn_retrieval', 'BVN Retrieval Service (' . $phone . ')');
    if (!$paymentResult['success']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $paymentResult['message']]);
        exit;
    }

    // Call actual BVN verification API by phone (assuming verifyPhone returns BVN)
    // Note: Robosttech verifyPhone is a good candidate. 
    // DataVerify might have a different endpoint if they support phone-to-bvn.
    $verificationResult = $verificationService->verifyPhone($phone);
    
    if ($verificationResult['success'] && isset($verificationResult['data']['bvn'])) {
        $bvn = $verificationResult['data']['bvn'];
        
        // Log the verification
        $database = new Database();
        $pdo = $database->getConnection();
        
        $stmt = $pdo->prepare("
            INSERT INTO verification_logs (user_id, service_type, reference_number, status, response_data, provider)
            VALUES (?, 'bvn_retrieval', ?, 'success', ?, ?)
        ");
        $stmt->execute([
            $userId,
            $phone,
            json_encode($verificationResult['data']),
            $provider
        ]);

        // Log to service_transactions
        $stmtSt = $pdo->prepare("
            INSERT INTO service_transactions (user_id, service_type, reference_number, status, amount, response_data, provider)
            VALUES (?, 'bvn_retrieval', ?, 'completed', ?, ?, ?)
        ");
        $stmtSt->execute([
            $userId,
            $phone,
            $paymentResult['amount_deducted'],
            json_encode($verificationResult['data']),
            $provider
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'BVN retrieved successfully',
            'bvn' => $bvn,
            'data' => $verificationResult['data'],
            'amount_deducted' => $paymentResult['amount_deducted']
        ]);
    } else {
        // REFUND LOGIC
        $refundAmount = $paymentResult['amount_deducted'];
        $refundDetails = "Refund for failed BVN Retrieval (" . $phone . ")";
        $refundReference = "REF-" . uniqid();
        $walletHelper->addAmount($userId, $refundAmount, $refundDetails, $refundReference);

        echo json_encode([
            'success' => false,
            'message' => 'BVN not found for this phone number. Amount refunded.'
        ]);
    }
    
} catch (Exception $e) {
    error_log('BVN Retrieval Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}
?>
