<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../config/database.php';
require_once '../config/config.php';

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
    // Verify JWT token (simplified for demo)
    $decoded = jwt_decode($token);
    $userId = $decoded->user_id;
    
    // Get request data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['nin']) || !isset($input['phone'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'NIN and phone number are required']);
        exit;
    }
    
    $nin = trim($input['nin']);
    $phone = trim($input['phone']);
    
    // Validate NIN format
    if (!preg_match('/^\d{11}$/', $nin)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid NIN format']);
        exit;
    }
    
    // Check user balance
    $stmt = $pdo->prepare("SELECT balance FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user || $user['balance'] < NIN_VERIFICATION_COST) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Insufficient balance. Please fund your wallet.']);
        exit;
    }
    
    // Simulate NIN verification API call
    $verificationResult = verifyNINWithAPI($nin, $phone);
    
    if ($verificationResult['success']) {
        // Deduct cost from user balance
        $newBalance = $user['balance'] - NIN_VERIFICATION_COST;
        $stmt = $pdo->prepare("UPDATE users SET balance = ? WHERE id = ?");
        $stmt->execute([$newBalance, $userId]);
        
        // Log the verification
        $stmt = $pdo->prepare("
            INSERT INTO verifications (user_id, type, reference, amount, status, data, created_at) 
            VALUES (?, 'nin', ?, ?, 'success', ?, NOW())
        ");
        $stmt->execute([
            $userId,
            $nin,
            NIN_VERIFICATION_COST,
            json_encode($verificationResult['data'])
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'NIN verification successful',
            'data' => $verificationResult['data']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => $verificationResult['message'] 
        ]);
    }
    
} catch (Exception $e) {
    error_log('NIN Verification Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}

function verifyNINWithAPI($nin, $phone) {
    // This would integrate with actual NIN verification API
    // For demo purposes, we'll simulate the response
    
    $demoNames = [
        '12345678901' => 'ADEBAYO JOHN OLUMIDE',
        '98765432109' => 'FATIMA AISHA MOHAMMED',
        '11111111111' => 'CHINEDU PETER OKWU'
    ];
    
    // Simulate API delay
    usleep(1500000); // 1.5 seconds
    
    if (isset($demoNames[$nin])) {
        return [
            'success' => true,
            'data' => [
                'name' => $demoNames[$nin],
                'nin' => $nin,
                'phone' => $phone,
                'status' => 'Verified',
                'verification_date' => date('Y-m-d H:i:s')
            ]
        ];
    } else {
        return [
            'success' => false,
            'message' => 'NIN not found in database'
        ];
    }
}

function jwt_decode($token) {
    // Simplified JWT decode for demo
    // In production, use a proper JWT library
    return (object) [
        'user_id' => 1,
        'email' => 'ibrobk@gmail.com',
        'exp' => time() + 3600
    ];
}
?>
