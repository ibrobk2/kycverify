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

if (!$input || !isset($input['user_id']) || !isset($input['otp_code'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'User ID and OTP code are required']);
    exit;
}

$userId = (int)$input['user_id'];
$otpCode = trim($input['otp_code']);

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if OTP exists and is valid
    $otpQuery = "SELECT id FROM otp_verifications WHERE user_id = ? AND otp_code = ? AND expires_at > NOW()";
    $otpStmt = $db->prepare($otpQuery);
    $otpStmt->execute([$userId, $otpCode]);
    
    $otpRecord = $otpStmt->fetch();
    
    if (!$otpRecord) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid or expired OTP code']);
        exit;
    }
    
    // Update user's email_verified status
    $updateQuery = "UPDATE users SET email_verified = TRUE WHERE id = ?";
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->execute([$userId]);
    
    // Delete used OTP
    $deleteQuery = "DELETE FROM otp_verifications WHERE user_id = ? AND otp_code = ?";
    $deleteStmt = $db->prepare($deleteQuery);
    $deleteStmt->execute([$userId, $otpCode]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Email verified successfully'
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
