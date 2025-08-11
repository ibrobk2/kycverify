<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['email']) || !isset($input['otp'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email and OTP are required']);
    exit;
}

$email = filter_var($input['email'], FILTER_SANITIZE_EMAIL);
$otp = trim($input['otp']);

try {
    $database = new Database();
    $db = $database->getConnection();

    // Find user by email
    $userQuery = "SELECT id, otp, otp_expires_at FROM users WHERE email = ?";
    $userStmt = $db->prepare($userQuery);
    $userStmt->execute([$email]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    // Ensure both OTPs are trimmed and compared as strings
    $dbOtp = isset($user['otp']) ? trim((string)$user['otp']) : '';
    $submittedOtp = trim((string)$otp);

    if ($dbOtp === '' || $submittedOtp === '' || $dbOtp !== $submittedOtp || strtotime($user['otp_expires_at']) < time()) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'Invalid or expired OTP code',
            'submitted_otp' => $submittedOtp,
            'database_otp' => $dbOtp,
            'database_otp_expires_at' => $user['otp_expires_at'],
            'is_expired' => strtotime($user['otp_expires_at']) < time()
        ]);
        exit;
    }

    // Update user email_verified to 1 and clear OTP fields
    $updateQuery = "UPDATE users SET email_verified = 1, otp = NULL, otp_expires_at = NULL WHERE id = ?";
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->execute([$user['id']]);

    echo json_encode([
        'success' => true,
        'message' => 'Email verified successfully. You can now log in.'
    ]);
    
} catch (PDOException $e) {
    error_log("OTP verification error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("OTP verification error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
?>
