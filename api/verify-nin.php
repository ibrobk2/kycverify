<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../config/database.php';
require_once '../config/config.php';
require_once 'wallet-helper.php';
require_once 'jwt-helper.php';
require_once 'RobosttechService.php';
require_once 'DataVerifyService.php';

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
    
    if (!$input || !isset($input['verification_type'])) {
        // Fallback for old requests or defaulting to NIN
        if (isset($input['nin'])) {
             $verificationType = 'nin';
             $slipType = 'premium'; // Default or required?
             $input['verification_type'] = 'nin';
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Verification type is required']);
            exit;
        }
    }
    
    $verificationType = $input['verification_type'];
    $slipType = isset($input['slip_type']) ? $input['slip_type'] : 'premium';
    
    // Validate inputs based on type
    $requestData = [];
    $referenceNumber = '';

    if ($verificationType === 'nin') {
        if (!isset($input['nin'])) {
            http_response_code(400); echo json_encode(['success' => false, 'message' => 'NIN is required']); exit;
        }
        $requestData['nin'] = trim($input['nin']);
        $referenceNumber = $requestData['nin'];
    } elseif ($verificationType === 'phone') {
        if (!isset($input['phone'])) {
            http_response_code(400); echo json_encode(['success' => false, 'message' => 'Phone number is required']); exit;
        }
        $requestData['nin'] = trim($input['phone']); // DataVerify phone endpoints still use 'nin' key
        $referenceNumber = $requestData['nin'];
    } elseif ($verificationType === 'demographic') {
        if (!isset($input['first_name']) || !isset($input['last_name']) || !isset($input['dob'])) {
            http_response_code(400); echo json_encode(['success' => false, 'message' => 'First name, last name and DOB are required']); exit;
        }
        $requestData = [
            'firstname' => trim($input['first_name']),
            'lastname' => trim($input['last_name']),
            'dob' => $input['dob'], // Format YYYY-MM-DD?
            'gender' => isset($input['gender']) ? $input['gender'] : '' 
        ];
        $referenceNumber = $requestData['firstname'] . ' ' . $requestData['lastname'];
    }

    // Initialize services
    $walletHelper = new WalletHelper();
    
    // Choose provider (Force DataVerify for slip functionality as Robosttech might not support it same way)
    $provider = defined('VERIFICATION_PROVIDER') ? VERIFICATION_PROVIDER : 'dataverify';
    
    // Check wallet balance
    $paymentResult = $walletHelper->processPayment($userId, 'nin_verification', 'NIN Verification (' . ucfirst($slipType) . ')');
    if (!$paymentResult['success']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $paymentResult['message']]);
        exit;
    }

    $verificationResult = ['success' => false, 'message' => 'Provider initialized'];
    $providerUsed = 'unknown';

    if ($provider === 'dataverify') {
        $service = new DataVerifyService();
        $providerUsed = 'dataverify';
        
        // This is where calling printNINSlip
        $verificationResult = $service->printNINSlip($verificationType, $slipType, $requestData);
        
        // Handle result
        if ($verificationResult['success'] && isset($verificationResult['data']['status']) && $verificationResult['data']['status'] === 'success') {
             $verificationResult['success'] = true; // Confirm success based on inner status
        } else {
             $verificationResult['success'] = false;
             if (isset($verificationResult['data']['message'])) {
                 $verificationResult['message'] = $verificationResult['data']['message'];
             }
        }
    } else {
        // Robosttech fallback (existing logic)
        $service = new RobosttechService();
        $providerUsed = 'robosttech';
        if ($verificationType === 'nin') {
             $verificationResult = $service->verifyNIN($requestData['nin']);
        } else {
             $verificationResult = ['success' => false, 'message' => 'Robosttech only supports NIN verification currently'];
        }
    }
    
    if ($verificationResult['success']) {
        // Log the verification
        $database = new Database();
        $pdo = $database->getConnection();
        
        $stmt = $pdo->prepare("
            INSERT INTO verification_logs (user_id, service_type, reference_number, status, response_data, provider)
            VALUES (?, ?, ?, 'success', ?, ?)
        ");
        
        // Don't log full PDF base64
        $logData = $verificationResult['data'];
        if (isset($logData['pdf_base64'])) $logData['pdf_base64'] = 'PDF_BINARY_DATA';

        $stmt->execute([
            $userId,
            'nin_' . $slipType,
            $referenceNumber,
            json_encode($logData),
            $providerUsed
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Verification successful',
            'status' => 'success', // For frontend compatibility
            'pdf_base64' => isset($verificationResult['data']['pdf_base64']) ? $verificationResult['data']['pdf_base64'] : null,
            'data' => $verificationResult['data'],
            'amount_deducted' => $paymentResult['amount_deducted']
        ]);
    } else {
        $database = new Database();
        $pdo = $database->getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO verification_logs (user_id, service_type, reference_number, status, error_message, provider)
            VALUES (?, 'nin', ?, 'failed', ?, ?)
        ");
        $stmt->execute([
            $userId,
            $referenceNumber,
            $verificationResult['message'],
            $providerUsed
        ]);

        echo json_encode([
            'success' => false,
            'message' => $verificationResult['message']
        ]);
    }
    
} catch (Exception $e) {
    error_log('NIN Verification Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error: ' . $e->getMessage()]);
}
?>
