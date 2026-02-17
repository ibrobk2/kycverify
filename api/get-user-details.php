<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/jwt-helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get user ID from token using helper
    $user_id = JWTHelper::getUserIdFromToken();
    if (!$user_id) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized: Invalid or expired token']);
        exit;
    }

    // Get user details from database
    $stmt = $db->prepare("SELECT id, name, email, phone, created_at FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Format the response
        $userDetails = [
            'id' => $user['id'],
            'name' => $user['name'] ?: 'User',
            'email' => $user['email'],
            'phone' => $user['phone'] ?: '',
            'member_since' => date('M Y', strtotime($user['created_at'])),
            'avatar' => '', // Can be extended to include avatar functionality
            'role' => 'User' // Default role, can be extended
        ];

        echo json_encode([
            'success' => true,
            'user' => $userDetails
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'User not found'
        ]);
    }

} catch (PDOException $e) {
    error_log('Get user details error: ' . $e->getMessage());
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
