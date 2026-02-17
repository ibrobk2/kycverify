<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Authorization');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/jwt-helper.php';

try {
    // Authenticate user using helper
    $userId = JWTHelper::getUserIdFromToken();
    if (!$userId) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized: Invalid or expired token']);
        exit;
    }

    // Get user data
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT id, name, email, phone, wallet, virtual_account_number, bank_name, account_name, created_at FROM users WHERE id = ? AND status = 'active'";
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
