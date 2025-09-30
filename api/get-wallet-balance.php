<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../config/database.php';

// JWT decode function
function jwt_decode($token) {
    $secret = 'your-secret-key'; // Replace with your actual secret key

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

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get user ID from token
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

    $decoded = jwt_decode($token);
    if (!$decoded) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized: Invalid or expired token']);
        exit;
    }

    $user_id = $decoded->user_id;
    if (!$user_id) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid token: user_id not found']);
        exit;
    }

    // Get wallet balance
    $stmt = $db->prepare("SELECT wallet FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        echo json_encode([
            'success' => true,
            'balance' => floatval($result['wallet'])
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'User not found'
        ]);
    }

} catch (PDOException $e) {
    error_log('Get wallet balance error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error',
        'error' => $e->getMessage()
    ]);
}
?>
