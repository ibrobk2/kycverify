<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

define('ADMIN_TOKEN_SECRET', 'your-very-secret-key');

function verifyAdminToken() {
    $headers = getallheaders();
    $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
    
    if (empty($authHeader) || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        return false;
    }
    
    $token = $matches[1];
    $parts = explode('.', $token);
    
    if (count($parts) !== 2) return false;
    
    list($payload_b64, $signature) = $parts;
    $expected_signature = hash_hmac('sha256', $payload_b64, ADMIN_TOKEN_SECRET);
    
    if (!hash_equals($expected_signature, $signature)) return false;
    
    $payload = json_decode(base64_decode($payload_b64), true);
    
    if (!$payload || (isset($payload['exp']) && $payload['exp'] < time())) return false;
    
    return $payload;
}

$adminData = verifyAdminToken();
if (!$adminData) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    $adminId = $adminData['admin_id'];
    
    // GET - Get admin profile and system settings
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get admin profile
        $stmt = $db->prepare("SELECT id, name, email, created_at FROM admins WHERE id = ?");
        $stmt->execute([$adminId]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Add default role if not in database
        if ($profile) {
            $profile['role'] = 'Administrator';
        }
        
        // Get system settings (if we had a settings table, for now just return mock/file-based or empty)
        // We can use api_configurations for some system-wide settings if needed, or create a new table.
        // For this phase, we'll focus on profile and password.
        
        echo json_encode([
            'success' => true,
            'data' => [
                'profile' => $profile
            ]
        ]);
    }
    
    // POST - Update profile or password
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = isset($input['action']) ? $input['action'] : 'update_profile';
        
        if ($action === 'update_profile') {
            $name = isset($input['name']) ? $input['name'] : '';
            $email = isset($input['email']) ? $input['email'] : '';
            
            if (empty($name) || empty($email)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Name and email are required']);
                exit;
            }
            
            // Check if email exists for other admins
            $stmt = $db->prepare("SELECT id FROM admins WHERE email = ? AND id != ?");
            $stmt->execute([$email, $adminId]);
            if ($stmt->fetch()) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Email already in use']);
                exit;
            }
            
            $stmt = $db->prepare("UPDATE admins SET name = ?, email = ? WHERE id = ?");
            $stmt->execute([$name, $email, $adminId]);
            
            echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
        }
        elseif ($action === 'change_password') {
            $currentPassword = isset($input['current_password']) ? $input['current_password'] : '';
            $newPassword = isset($input['new_password']) ? $input['new_password'] : '';
            
            if (empty($currentPassword) || empty($newPassword)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Current and new passwords are required']);
                exit;
            }
            
            // Verify current password
            $stmt = $db->prepare("SELECT password FROM admins WHERE id = ?");
            $stmt->execute([$adminId]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!password_verify($currentPassword, $admin['password'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Incorrect current password']);
                exit;
            }
            
            // Update password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE admins SET password = ? WHERE id = ?");
            $stmt->execute([$hashedPassword, $adminId]);
            
            echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
        }
    }
    
} catch (Exception $e) {
    error_log("Admin settings error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
