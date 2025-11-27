<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Get authorization header
$headers = getallheaders();
$authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';

if (empty($authHeader)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authorization token required']);
    exit();
}

// Extract token
$token = str_replace('Bearer ', '', $authHeader);

// Decode JWT
function jwt_decode($token) {
    $secret = JWT_SECRET;
    $parts = explode('.', $token);
    
    if (count($parts) !== 3) {
        return false;
    }
    
    list($header, $payload, $signature) = $parts;
    
    // Verify signature
    $validSignature = hash_hmac('sha256', "$header.$payload", $secret, true);
    $validSignature = rtrim(strtr(base64_encode($validSignature), '+/', '-_'), '=');
    
    if ($signature !== $validSignature) {
        return false;
    }
    
    // Decode payload
    $payload = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);
    
    // Check expiration
    if (isset($payload['exp']) && $payload['exp'] < time()) {
        return false;
    }
    
    return $payload;
}

$decoded = jwt_decode($token);

if (!$decoded) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid or expired token']);
    exit();
}

$userId = $decoded['user_id'];

// Validate input
if (!isset($input['name']) || empty(trim($input['name']))) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Name is required']);
    exit();
}

if (!isset($input['phone']) || empty(trim($input['phone']))) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Phone number is required']);
    exit();
}

$name = trim($input['name']);
$phone = trim($input['phone']);

// Validate phone format
if (!preg_match('/^(\+234|0)[789]\d{9}$/', str_replace(' ', '', $phone))) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid phone number format']);
    exit();
}

try {
    $db = (new Database())->getConnection();
    
    // Update user profile
    $stmt = $db->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?");
    $result = $stmt->execute([$name, $phone, $userId]);
    
    if ($result) {
        // Fetch updated user data
        $stmt = $db->prepare("SELECT id, name, email, phone, wallet, created_at FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'message' => 'Profile updated successfully',
            'user' => $user
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update profile']);
    }
} catch (Exception $e) {
    error_log("Profile update error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error occurred']);
}
?>
