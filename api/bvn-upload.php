<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../config/database.php';
require_once 'wallet-helper.php';


// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get authorization header
$headers = getallheaders();
$authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';

if (!$authHeader || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$token = $matches[1];

// Dummy function to decode JWT token and get user ID
function jwt_decode($token) {
    // In real implementation, decode and verify JWT token here
    return (object) [
        'user_id' => 1,
        'email' => 'ibrobk@gmail.com',
        'exp' => time() + 3600
    ];
}

try {
    $decoded = jwt_decode($token);
    $userId = $decoded->user_id;

    // Initialize wallet helper
    $walletHelper = new WalletHelper();

    // Check wallet balance and process payment
    $paymentResult = $walletHelper->processPayment($userId, 'BVN Upload', 'BVN Upload Service Payment');
    if (!$paymentResult['success']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $paymentResult['message']]);
        exit;
    }

    if (!isset($_FILES['files'])) {

        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No files uploaded']);
        exit;
    }

    $files = $_FILES['files'];
    $uploadDir = __DIR__ . '/../uploads/bvn_modifications/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $database = new Database();
    $db = $database->getConnection();

    $uploadedFiles = [];
    $reference = 'BVN-' . date('YmdHis') . '-' . bin2hex(random_bytes(4));

    for ($i = 0; $i < count($files['name']); $i++) {
        $fileName = basename($files['name'][$i]);
        $fileTmpPath = $files['tmp_name'][$i];
        $fileSize = $files['size'][$i];
        $fileType = $files['type'][$i];

        // Validate file size (max 5MB)
        if ($fileSize > 5 * 1024 * 1024) {
            continue; // Skip files larger than 5MB
        }

        // Validate file type
        $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
        if (!in_array($fileType, $allowedTypes)) {
            continue; // Skip unsupported file types
        }

        $newFileName = $reference . '-' . $i . '-' . preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $fileName);
        $destination = $uploadDir . $newFileName;

        if (move_uploaded_file($fileTmpPath, $destination)) {
            // Insert record into database
            $insertQuery = "INSERT INTO bvn_modification_uploads (user_id, reference, file_name, file_path, status) VALUES (?, ?, ?, ?, 'pending')";
            $stmt = $db->prepare($insertQuery);
            $stmt->execute([$userId, $reference, $fileName, 'uploads/bvn_modifications/' . $newFileName]);

            $uploadedFiles[] = $fileName;
        }
    }

    if (count($uploadedFiles) === 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No valid files uploaded']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Files uploaded successfully',
        'reference' => $reference,
        'files' => $uploadedFiles,
        'amount_deducted' => $paymentResult['amount_deducted']
    ]);

} catch (Exception $e) {
    error_log('BVN Upload Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}
?>
