<?php
// Ensure no output before JSON headers
ob_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
require_once 'send-email.php';

// Clear any previous output
ob_clean();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['name']) || !isset($input['email']) || !isset($input['password']) || !isset($input['phone'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Name, email, phone and password are required']);
    exit;
}


$name = trim($input['name']);
$email = filter_var($input['email'], FILTER_SANITIZE_EMAIL);
$phone = trim($input['phone']);
$password = $input['password'];


// Validation
if (strlen($name) < 2) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Name must be at least 2 characters']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

if (strlen($password) < 6) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if user already exists
    $checkQuery = "SELECT id FROM users WHERE email = ?";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute([$email]);
    
    if ($checkStmt->fetch()) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Email already registered']);
        exit;
    }
    
    // Start transaction
    $db->beginTransaction();
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new user with email_verified = false
    $insertQuery = "INSERT INTO users (name, email, phone, password, status, email_verified, created_at) VALUES (?, ?, ?, ?, 'active', FALSE, NOW())";
    $insertStmt = $db->prepare($insertQuery);
    $insertStmt->execute([$name, $email, $phone, $hashedPassword]);


    
    $userId = $db->lastInsertId();
    
    // Generate OTP
    $otp = generateOTP();
    $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    
    // Store OTP in database
    $otpQuery = "INSERT INTO otp_verifications (user_id, otp_code, expires_at) VALUES (?, ?, ?)";
    $otpStmt = $db->prepare($otpQuery);
    $otpStmt->execute([$userId, $otp, $expiresAt]);
    
    // Commit transaction
    $db->commit();
    
    // Send OTP email
    $emailSent = sendOTP($email, $otp);
    
    if ($emailSent) {
        echo json_encode([
            'success' => true,
            'message' => 'Account created successfully. Please check your email for verification code.',
            'user_id' => $userId,
            'email_sent' => true
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'Account created successfully. Please verify your email using the OTP code sent to your email.',
            'user_id' => $userId,
            'email_sent' => false
        ]);
    }
    
} catch (PDOException $e) {
    // Rollback transaction on error
    if ($db->inTransaction()) {
        $db->rollback();
    }
    
    error_log("Signup error: " . $e->getMessage());
    
    if ($e->getCode() == 23000) { // Duplicate entry
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Email already registered']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
} catch (Exception $e) {
    // Rollback transaction on error
    if ($db->inTransaction()) {
        $db->rollback();
    }
    
    error_log("Signup error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}

// Ensure no additional output
exit;
?>
