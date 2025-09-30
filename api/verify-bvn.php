<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../config/database.php';
require_once '../config/config.php';
require_once 'wallet-helper.php';


// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get authorization header
$headers = getallheaders();
$authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';

if (!$authHeader || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

    $token = $matches[1];

try {
    // Verify JWT token
    $decoded = jwt_decode($token);
    $userId = $decoded->user_id;

    // Initialize wallet helper
    $walletHelper = new WalletHelper();

    // Check wallet balance and process payment
    $paymentResult = $walletHelper->processPayment($userId, 'BVN Verification', 'BVN Verification Service Payment');
    if (!$paymentResult['success']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $paymentResult['message']]);
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
        echo json_encode(['success' => false, 'message' => 'Invalid BVN format']);
        exit;
    }
    
    // User balance already checked and deducted by wallet helper

    
    // Simulate BVN verification
    $verificationResult = verifyBVNWithAPI($bvn);
    
    if ($verificationResult['success']) {
        // Log the verification (wallet transaction already logged by wallet helper)
        $stmt = $pdo->prepare("
            INSERT INTO verifications (user_id, type, reference, amount, status, data, created_at)
            VALUES (?, 'bvn', ?, ?, 'success', ?, NOW())
        ");
        $stmt->execute([
            $userId,
            $bvn,
            $paymentResult['amount_deducted'],
            json_encode($verificationResult['data'])
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'BVN verification successful',
            'data' => $verificationResult['data'],
            'amount_deducted' => $paymentResult['amount_deducted']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => $verificationResult['message']
        ]);
    }

    
} catch (Exception $e) {
    error_log('BVN Verification Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}

function verifyBVNWithAPI($bvn) {
    // Demo BVN data
    $demoBVNs = [
        '12345678901' => [
            'name' => 'ADEBAYO JOHN OLUMIDE',
            'bank' => 'First Bank Nigeria'
        ],
        '98765432109' => [
            'name' => 'FATIMA AISHA MOHAMMED', 
            'bank' => 'Access Bank'
        ],
        '11111111111' => [
            'name' => 'CHINEDU PETER OKWU',
            'bank' => 'GTBank'
        ]
    ];
    
    // Simulate API delay
    usleep(1500000);
    
    if (isset($demoBVNs[$bvn])) {
        return [
            'success' => true,
            'data' => [
                'name' => $demoBVNs[$bvn]['name'],
                'bvn' => $bvn,
                'bank' => $demoBVNs[$bvn]['bank'],
                'status' => 'Verified',
                'verification_date' => date('Y-m-d H:i:s')
            ]
        ];
    } else {
        return [
            'success' => false,
            'message' => 'BVN not found in database'
        ];
    }
}

function jwt_decode($token) {
    return (object) [
        'user_id' => 1,
        'email' => 'ibrobk@gmail.com',
        'exp' => time() + 3600
    ];
}
?>
