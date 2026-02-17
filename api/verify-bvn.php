<?php
header('Content-Type: application/json');
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
    
    if (!$input || !isset($input['bvn'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'BVN is required']);
        exit;
    }
    
    $bvn = trim($input['bvn']);
    
    // Validate BVN format
    if (!preg_match('/^\d{11}$/', $bvn)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid BVN format. Must be 11 digits.']);
        exit;
    }

    // Initialize services
    $walletHelper = new WalletHelper();
    
    // Choose provider
    $provider = defined('VERIFICATION_PROVIDER') ? VERIFICATION_PROVIDER : 'robosttech';
    $verificationService = null;

    if ($provider === 'dataverify') {
        $verificationService = new DataVerifyService();
    } else {
        $verificationService = new RobosttechService();
    }

    // Check wallet balance and process payment
    $paymentResult = $walletHelper->processPayment($userId, 'bvn_verification', 'BVN Verification Service');
    if (!$paymentResult['success']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $paymentResult['message']]);
        exit;
    }

    // Call actual BVN verification API
    $verificationResult = $verificationService->verifyBVN($bvn);
    
    if ($verificationResult['success']) {
        // Log the verification
        $database = new Database();
        $pdo = $database->getConnection();
        
        $stmt = $pdo->prepare("
            INSERT INTO verification_logs (user_id, service_type, reference_number, status, response_data, provider)
            VALUES (?, 'bvn', ?, 'success', ?, ?)
        ");
        $stmt->execute([
            $userId,
            $bvn,
            json_encode($verificationResult['data']),
            $provider
        ]);

        // Log to service_transactions for admin tracking
        $stmtSt = $pdo->prepare("
            INSERT INTO service_transactions (user_id, service_type, reference_number, status, amount, response_data, provider)
            VALUES (?, 'bvn_verification', ?, 'completed', ?, ?, ?)
        ");
        $stmtSt->execute([
            $userId,
            $bvn,
            $paymentResult['amount_deducted'],
            json_encode($verificationResult['data']),
            $provider
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'BVN verification successful',
            'data' => $verificationResult['data'],
            'amount_deducted' => $paymentResult['amount_deducted']
        ]);
    } else {
        $database = new Database();
        $pdo = $database->getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO verification_logs (user_id, service_type, reference_number, status, error_message, provider)
            VALUES (?, 'bvn', ?, 'failed', ?, ?)
        ");
        $stmt->execute([
            $userId,
            $bvn,
            $verificationResult['message'],
            $provider
        ]);

        // Log failed to service_transactions
        $stmtSt = $pdo->prepare("
            INSERT INTO service_transactions (user_id, service_type, reference_number, status, amount, error_message, provider)
            VALUES (?, 'bvn_verification', ?, 'failed', ?, ?, ?)
        ");
        $stmtSt->execute([
            $userId,
            $bvn,
            $paymentResult['amount_deducted'],
            $verificationResult['message'],
            $provider
        ]);

        echo json_encode([
            'success' => false,
            'message' => $verificationResult['message']
        ]);
    }
    
} catch (Exception $e) {
    error_log('BVN Verification Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error: ' . $e->getMessage()]);
}
?>
