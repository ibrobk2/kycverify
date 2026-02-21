<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/wallet-helper.php';
require_once __DIR__ . '/jwt-helper.php';
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
        echo json_encode(['success' => false, 'message' => 'BVN number is required']);
        exit;
    }
    
    $bvn = trim($input['bvn']);
    
    // Validate BVN format (11 digits)
    if (!preg_match('/^\d{11}$/', $bvn)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid BVN format. Must be 11 digits.']);
        exit;
    }

    // Initialize services
    $walletHelper = new WalletHelper();
    
    // Check wallet balance and process payment
    $paymentResult = $walletHelper->processPayment($userId, 'bvn_slip_printing', 'BVN Slip Printing');
    if (!$paymentResult['success']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $paymentResult['message']]);
        exit;
    }

    // Call DataVerify API for BVN slip
    $service = new DataVerifyService();
    $verificationResult = $service->printBVNSlip($bvn);

    if ($verificationResult['success'] && isset($verificationResult['data']['status']) && $verificationResult['data']['status'] === 'success') {
        // Log the verification
        $database = new Database();
        $pdo = $database->getConnection();
        
        $stmt = $pdo->prepare("
            INSERT INTO verification_logs (user_id, service_type, reference_number, status, response_data, provider)
            VALUES (?, 'bvn_slip', ?, 'success', ?, 'dataverify')
        ");
        
        // Don't log full PDF base64
        $logData = $verificationResult['data'];
        if (isset($logData['pdf_base64'])) $logData['pdf_base64'] = 'PDF_BINARY_DATA';

        $stmt->execute([
            $userId,
            $bvn,
            json_encode($logData),
            'dataverify'
        ]);

        // Log to service_transactions for admin tracking
        $stmtSt = $pdo->prepare("
            INSERT INTO service_transactions (user_id, service_type, reference_number, status, amount, response_data, provider)
            VALUES (?, 'bvn_slip', ?, 'completed', ?, ?, 'dataverify')
        ");
        $stmtSt->execute([
            $userId,
            $bvn,
            $paymentResult['amount_deducted'],
            json_encode($logData)
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'BVN Slip retrieved successfully',
            'status' => 'success',
            'pdf_base64' => isset($verificationResult['data']['pdf_base64']) ? $verificationResult['data']['pdf_base64'] : null,
            'data' => $verificationResult['data'],
            'amount_deducted' => $paymentResult['amount_deducted']
        ]);
    } else {
        // REFUND LOGIC START
        // If verification failed, refund the user
        $refundAmount = $paymentResult['amount_deducted'];
        $refundDetails = "Refund for failed BVN Slip Printing (" . $bvn . ")";
        $refundReference = "REF-" . uniqid();
        
        $walletHelper->addAmount($userId, $refundAmount, $refundDetails, $refundReference);
        // REFUND LOGIC END

        // Log failure
        $database = new Database();
        $pdo = $database->getConnection();
        $errorMsg = isset($verificationResult['data']['message']) ? $verificationResult['data']['message'] : (isset($verificationResult['message']) ? $verificationResult['message'] : 'Unknown error');
        
        $stmt = $pdo->prepare("
            INSERT INTO verification_logs (user_id, service_type, reference_number, status, error_message, provider)
            VALUES (?, 'bvn_slip', ?, 'failed', ?, 'dataverify')
        ");
        $stmt->execute([
            $userId,
            $bvn,
            $errorMsg
        ]);

        // Log failed to service_transactions
        $stmtSt = $pdo->prepare("
            INSERT INTO service_transactions (user_id, service_type, reference_number, status, amount, error_message, provider)
            VALUES (?, 'bvn_slip', ?, 'failed', ?, ?, 'dataverify')
        ");
        $stmtSt->execute([
            $userId,
            $bvn,
            $paymentResult['amount_deducted'],
            $errorMsg
        ]);

        echo json_encode([
            'success' => false,
            'message' => $errorMsg . ". Amount refunded."
        ]);
    }
    
} catch (Exception $e) {
    error_log('BVN Slip Printing Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error: ' . $e->getMessage()]);
}
?>
