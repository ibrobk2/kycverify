<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';
require_once 'jwt-helper.php';

// Set JSON response header
header('Content-Type: application/json');

// Get auth token
$headers = getallheaders();
$token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : null;

if (!$token) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Verify token
$decoded = verifyToken($token);
if (!$decoded) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid or expired token']);
    exit;
}

$userId = $decoded->sub;

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit;
}

$verificationType = $data['verification_type'] ?? null;
$slipType = $data['slip_type'] ?? null;
$timestamp = $data['timestamp'] ?? date('Y-m-d H:i:s');

if (!$verificationType || !$slipType) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    // Insert transaction log into database
    $stmt = $conn->prepare("
        INSERT INTO nin_verification_logs (user_id, verification_type, slip_type, created_at)
        VALUES (?, ?, ?, NOW())
    ");
    
    $stmt->bind_param('iss', $userId, $verificationType, $slipType);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Transaction logged successfully',
            'transaction_id' => $conn->insert_id
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to log transaction']);
    }
    
    $stmt->close();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>
