<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Authorization');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// JWT decode function
function jwt_decode($token) {
    $secret = JWT_SECRET;

    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return false;
    }

    list($headerBase64, $payloadBase64, $signatureBase64) = $parts;

    $header = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $headerBase64)), true);
    $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $payloadBase64)), true);

    if (!$header || !$payload) {
        return false;
    }

    $signature = base64_decode(str_replace(['-', '_'], ['+', '/'], $signatureBase64));
    $expectedSignature = hash_hmac('sha256', "$headerBase64.$payloadBase64", $secret, true);

    if (!hash_equals($expectedSignature, $signature)) {
        return false;
    }

    if (isset($payload['exp']) && $payload['exp'] < time()) {
        return false;
    }

    return (object) $payload;
}

// Get authorization header
$headers = getallheaders();
$authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';

if (!$authHeader || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authorization token required']);
    exit;
}

$token = $matches[1];

try {
    // Decode token
    $decoded = jwt_decode($token);
    if (!$decoded) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid or expired token']);
        exit;
    }

    $userId = $decoded->user_id;
    
    // Get user data
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT id, name, email, phone, wallet, created_at FROM users WHERE id = ? AND status = 'active'";
    $stmt = $db->prepare($query);
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'user' => $user
    ]);
    
} catch (Exception $e) {
    error_log("Token verification error: " . $e->getMessage());
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid token']);
}
?>
