<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
require_once 'send-email.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['email'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email is required']);
    exit;
}

$email = filter_var($input['email'], FILTER_SANITIZE_EMAIL);

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if user exists
    $userQuery = "SELECT id, name, email FROM users WHERE email = ? AND status = 'active' AND email_verified = FALSE";
    $userStmt = $db->prepare($userQuery);
    $userStmt->execute([$email]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'User not found or email already verified']);
        exit;
    }
    
    // Generate new OTP
    $otp = generateOTP();
    $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    
    // Delete any existing OTP for this user
    $deleteQuery = "DELETE FROM otp_verifications WHERE user_id = ?";
    $deleteStmt = $db->prepare($deleteQuery);
    $deleteStmt->execute([$user['id']]);
    
    // Store new OTP in database
    $otpQuery = "INSERT INTO otp_verifications (user_id, otp_code, expires_at) VALUES (?, ?, ?)";
    $otpStmt = $db->prepare($otpQuery);
    $otpStmt->execute([$user['id'], $otp, $expiresAt]);
    
    // Send OTP email
    $emailSent = sendOTP($email, $otp);
    
    if ($emailSent) {
        echo json_encode([
            'success' => true,
            'message' => 'OTP code resent successfully. Please check your email.'
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'OTP code generated but email failed to send. Please contact support.'
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Resend OTP error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("Resend OTP error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
?>
