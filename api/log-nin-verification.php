<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/jwt-helper.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $userId = JWTHelper::getUserIdFromToken();
    if (!$userId) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !isset($input['verification_type']) || !isset($input['slip_type'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    $verificationType = $input['verification_type'];
    $slipType = $input['slip_type'];
    $nin = isset($input['nin']) ? $input['nin'] : '';

    $stmt = $db->prepare("
        INSERT INTO nin_verification_logs (user_id, nin, status, response_data)
        VALUES (?, ?, 'success', ?)
    ");
    
    $logData = [
        'verification_type' => $verificationType,
        'slip_type' => $slipType,
        'timestamp' => date('Y-m-d H:i:s')
    ];

    if ($stmt->execute([$userId, $nin, json_encode($logData)])) {
        echo json_encode([
            'success' => true,
            'message' => 'Transaction logged successfully',
            'transaction_id' => $db->lastInsertId()
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to log transaction']);
    }

} catch (Exception $e) {
    error_log('Log NIN verification error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
