<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/wallet-helper.php';
require_once __DIR__ . '/jwt-helper.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $userId = JWTHelper::getUserIdFromToken();
    if (!$userId) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    $walletHelper = new WalletHelper();
    
    $paymentResult = $walletHelper->processPayment($userId, 'ipe_clearance', 'IPE Clearance Service');
    if (!$paymentResult['success']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $paymentResult['message']]);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !isset($input['ipeCategory']) || !isset($input['trackingId'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Required fields missing']);
        exit;
    }

    $reference = 'IPE-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(4)));

    $database = new Database();
    $db = $database->getConnection();

    $stmt = $db->prepare("
        INSERT INTO verification_logs (user_id, service_type, reference_number, status, response_data)
        VALUES (?, 'ipe_clearance', ?, 'pending', ?)
    ");
    
    $stmt->execute([
        $userId,
        $reference,
        json_encode($input)
    ]);

    // Log to service_transactions for admin tracking
    $stmtSt = $db->prepare("
        INSERT INTO service_transactions (user_id, service_type, reference_number, status, amount, request_data, provider)
        VALUES (?, 'ipe_clearance', ?, 'pending', ?, ?, 'internal')
    ");
    $stmtSt->execute([
        $userId,
        $reference,
        $paymentResult['amount_deducted'],
        json_encode($input)
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'IPE Clearance request submitted successfully',
        'reference' => $reference,
        'amount_deducted' => $paymentResult['amount_deducted']
    ]);

} catch (Exception $e) {
    error_log('IPE Clearance Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}
?>
