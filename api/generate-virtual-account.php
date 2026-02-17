<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/jwt-helper.php';
require_once __DIR__ . '/KatPayService.php';
require_once __DIR__ . '/PaymentPointService.php';

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

    $db = (new Database())->getConnection();
    
    // Check if user already has an account
    $stmt = $db->prepare("SELECT virtual_account_number, email, name, phone FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && $user['virtual_account_number']) {
        echo json_encode([
            'success' => true, 
            'message' => 'User already has a virtual account',
            'data' => [
                'account_number' => $user['virtual_account_number']
            ]
        ]);
        exit;
    }

    // Generate account using active payment gateway
    $gateway = defined('PAYMENT_GATEWAY') ? PAYMENT_GATEWAY : 'katpay';
    
    if ($gateway === 'katpay') {
        $paymentService = new KatPayService();
    } else {
        $paymentService = new PaymentPointService();
    }
    
    $result = $paymentService->createVirtualAccount($userId, $user['name'], $user['email'], isset($user['phone']) ? $user['phone'] : '');

    if ($result['success']) {
        $vaData = $result['data'];
        $accNo = isset($vaData['account_number']) ? $vaData['account_number'] : (isset($vaData['accountNumber']) ? $vaData['accountNumber'] : null);
        $bankName = isset($vaData['bank_name']) ? $vaData['bank_name'] : (isset($vaData['bankName']) ? $vaData['bankName'] : ($gateway === 'katpay' ? 'KatPay Bank' : 'PaymentPoint Bank'));
        $accName = isset($vaData['account_name']) ? $vaData['account_name'] : (isset($vaData['accountName']) ? $vaData['accountName'] : $user['name']);

        if ($accNo) {
            // Update user record
            $stmt = $db->prepare("
                UPDATE users SET 
                virtual_account_number = ?, 
                bank_name = ?, 
                account_name = ? 
                WHERE id = ?
            ");
            $stmt->execute([$accNo, $bankName, $accName, $userId]);

            echo json_encode([
                'success' => true,
                'message' => 'Virtual account generated successfully',
                'data' => [
                    'account_number' => $accNo,
                    'bank_name' => $bankName,
                    'account_name' => $accName
                ]
            ]);
        } else {
             throw new Exception("Account number missing in gateway response");
        }
    } else {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $result['message']
        ]);
    }

} catch (Exception $e) {
    error_log('Virtual Account Generation Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error: ' . $e->getMessage()]);
}
?>
