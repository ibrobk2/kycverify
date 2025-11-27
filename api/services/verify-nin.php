<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../wallet-helper.php';
require_once __DIR__ . '/../RobosttechService.php';

// JWT decode function (Shared logic, ideally move to a helper)
function jwt_decode_verify($token) {
    $secret = JWT_SECRET;
    $parts = explode('.', $token);
    if (count($parts) !== 3) return false;

    list($headerBase64, $payloadBase64, $signatureBase64) = $parts;

    $header = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $headerBase64)), true);
    $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $payloadBase64)), true);

    if (!$header || !$payload) return false;

    $signature = base64_decode(str_replace(['-', '_'], ['+', '/'], $signatureBase64));
    $expectedSignature = hash_hmac('sha256', "$headerBase64.$payloadBase64", $secret, true);

    if (!hash_equals($expectedSignature, $signature)) return false;
    if (isset($payload['exp']) && $payload['exp'] < time()) return false;

    return (object) $payload;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// 1. Authenticate User
$headers = getallheaders();
if (!isset($headers['Authorization'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authorization header missing']);
    exit;
}

$authHeader = $headers['Authorization'];
if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid authorization header']);
    exit;
}

$token = $matches[1];
$decoded = jwt_decode_verify($token);

if (!$decoded) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized: Invalid or expired token']);
    exit;
}

$user_id = $decoded->user_id;

// 2. Validate Input
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['nin'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'NIN is required']);
    exit;
}

$nin = trim($input['nin']);

// 3. Process Payment & Verification
try {
    $walletHelper = new WalletHelper();
    $serviceName = 'nin_verification';
    $price = $walletHelper->getServicePrice($serviceName);

    // Check balance
    if (!$walletHelper->hasSufficientBalance($user_id, $serviceName)) {
        http_response_code(402); // Payment Required
        echo json_encode([
            'success' => false, 
            'message' => 'Insufficient wallet balance. Please fund your wallet.',
            'required' => $price
        ]);
        exit;
    }

    // Deduct amount
    if ($walletHelper->deductAmount($user_id, $price, "NIN Verification: $nin")) {
        
        // Call Robosttech API
        $robosttech = new RobosttechService();
        $result = $robosttech->verifyNIN($nin);

        if ($result['success']) {
            // Log success
            // TODO: Add logging to verification_logs table
            
            echo json_encode([
                'success' => true,
                'message' => 'Verification successful',
                'data' => $result['data']
            ]);
        } else {
            // Refund if service failed? 
            // Usually, if the API fails due to system error, we refund. 
            // If it fails because NIN is invalid, we might still charge.
            // For now, let's assume no refund for invalid NIN, but refund for system error.
            
            // If it's a provider error, maybe refund.
            // $walletHelper->addTransaction($user_id, $price, 'credit', "Refund: NIN Verification Failed");
            
            echo json_encode([
                'success' => false,
                'message' => $result['message']
            ]);
        }

    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Transaction failed']);
    }

} catch (Exception $e) {
    error_log("NIN Verification Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
?>
