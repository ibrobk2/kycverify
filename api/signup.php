<?php
// Ensure no output before JSON headers
ob_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/PaymentPointService.php';

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
    $checkQuery = "SELECT id, email_verified FROM users WHERE email = ?";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute([$email]);
    $existingUser = $checkStmt->fetch();

    if ($existingUser) {
        // if ($existingUser['email_verified'] == 0) {
        //     // User exists but email is not verified, resend OTP
        //     $_POST['email'] = $email;
        //     require_once 'resend-otp.php';
        //     exit; // Stop execution after resending OTP
        // } else {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Email already registered']);
            exit;
        // }
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Generate OTP
    $otp = rand(100000, 999999);
    $otpExpiresAt = date('Y-m-d H:i:s', strtotime('+15 minutes'));

    // Insert new user with email_verified set to 1 (Verified) and OTP (optional)
    $insertQuery = "INSERT INTO users (name, email, phone, password, status, email_verified, otp, otp_expires_at, created_at) VALUES (?, ?, ?, ?, 'active', 1, ?, ?, NOW())";
    $insertStmt = $db->prepare($insertQuery);
    $insertStmt->execute([$name, $email, $phone, $hashedPassword, $otp, $otpExpiresAt]);

    $userId = $db->lastInsertId();

    // Generate PaymentPoint Virtual Account
    $ppService = new PaymentPointService();
    $vaResult = $ppService->createVirtualAccount($userId, $name, $email, $phone);

    if ($vaResult['success']) {
        $vaData = $vaResult['data'];
        // Assume response structure based on typical API: 
        // { "account_number": "...", "bank_name": "...", "account_name": "..." }
        if (isset($vaData['account_number'])) {
            $updateQuery = "UPDATE users SET virtual_account_number = ?, bank_name = ?, account_name = ? WHERE id = ?";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->execute([
                $vaData['account_number'],
                isset($vaData['bank_name']) ? $vaData['bank_name'] : 'PaymentPoint Bank',
                isset($vaData['account_name']) ? $vaData['account_name'] : $name,
                $userId
            ]);
        }
    } else {
        error_log("Failed to generate virtual account for user $userId: " . $vaResult['message']);
    }

    // Send OTP email - DISABLED
    // require_once 'send-email.php';
    // $emailSent = sendOTP($email, $otp);
    $emailSent = true;

    if ($emailSent) {
        echo json_encode([
            'success' => true,
            'message' => 'Account created successfully. Please login.',
            'user_id' => $userId
        ]);
    } else { // This block is practically unreachable now
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to send OTP email.']);
    }
    
} catch (PDOException $e) {
    error_log("Signup error: " . $e->getMessage());
    
    if ($e->getCode() == 23000) { // Duplicate entry
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Email already registered']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
} catch (Exception $e) {
    error_log("Signup error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}

// Ensure no additional output
exit;
?>
