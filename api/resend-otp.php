<?php
ob_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
require_once 'send-email.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['email'])) {
    if (isset($_POST['email'])) {
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    } else {
        http_response_code(400);
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Email is required']);
        exit;
    }
} else {
    $email = filter_var($input['email'], FILTER_SANITIZE_EMAIL);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if user exists
    $userQuery = "SELECT id, name, email, email_verified FROM users WHERE email = ?";
    $userStmt = $db->prepare($userQuery);
    $userStmt->execute([$email]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        http_response_code(400);
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    if ($user['email_verified'] == 1) {
        http_response_code(400);
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Email already verified']);
        exit;
    }
    
    // Generate new OTP
    $otp = rand(100000, 999999);
    $otpExpiresAt = date('Y-m-d H:i:s', strtotime('+15 minutes'));
    
    // Update user with new OTP
    $updateQuery = "UPDATE users SET otp = ?, otp_expires_at = ? WHERE id = ?";
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->execute([$otp, $otpExpiresAt, $user['id']]);
    
    // Send OTP email
    $emailSent = sendOTP($email, $otp);
    
    if ($emailSent) {
        ob_clean();
        echo json_encode([
            'success' => true,
            'message' => 'A new OTP has been sent to your email.'
        ]);
    } else {
        http_response_code(500);
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Failed to send OTP email.']);
    }
    
} catch (PDOException $e) {
    error_log("Resend OTP error: " . $e->getMessage());
    http_response_code(500);
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("Resend OTP error: " . $e->getMessage());
    http_response_code(500);
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
?>
