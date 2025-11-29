<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

define('ADMIN_TOKEN_SECRET', 'your-very-secret-key'); // Change this to a secure key

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['email']) || !isset($input['password'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email and password are required']);
    exit;
}

$email = filter_var($input['email'], FILTER_SANITIZE_EMAIL);
$password = $input['password'];

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if admins table exists
    $tableCheck = $db->query("SHOW TABLES LIKE 'admins'");
    if ($tableCheck->rowCount() == 0) {
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'Admin table not found. Please run setup first.',
            'setup_url' => '/lildone/admin/setup.html'
        ]);
        exit;
    }
    
    // Check if admin exists
    $query = "SELECT id, name, email, password, status, created_at FROM admins WHERE email = ? AND status = 'active'";
    $stmt = $db->prepare($query);
    $stmt->execute([$email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$admin) {
        http_response_code(401);
        echo json_encode([
            'success' => false, 
            'message' => 'No active admin found with this email. Please check your credentials or run setup.',
            'setup_url' => '/lildone/admin/setup.html'
        ]);
        exit;
    }
    
    if (!password_verify($password, $admin['password'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid password']);
        exit;
    }
    
    // Generate a simple HMAC-signed token (not a full JWT)
    $payload = [
        'admin_id' => $admin['id'],
        'email' => $admin['email'],
        'exp' => time() + (24 * 60 * 60) // 24 hours
    ];
    $payload_json = json_encode($payload);
    $payload_b64 = base64_encode($payload_json);
    $signature = hash_hmac('sha256', $payload_b64, ADMIN_TOKEN_SECRET);
    $token = $payload_b64 . '.' . $signature;
    
    // Update last login
    $updateQuery = "UPDATE admins SET last_login = NOW() WHERE id = ?";
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->execute([$admin['id']]);
    
    // Remove password from response
    unset($admin['password']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'token' => $token,
        'admin' => $admin
    ]);
    
} catch (PDOException $e) {
    error_log("Admin login error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage(),
        'debug' => DEBUG_MODE ? $e->getTraceAsString() : null
    ]);
} catch (Exception $e) {
    error_log("Admin login error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}
?>
