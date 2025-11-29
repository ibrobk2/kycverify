<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/wallet-helper.php';
require_once __DIR__ . '/services/VTUServiceFactory.php';

// Get authorization and verify user
$headers = getallheaders();
$authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';

if (empty($authHeader) || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$token = $matches[1];
require_once __DIR__ . '/../api/verify-token.php';
$tokenData = verifyJWT($token);

if (!$tokenData) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid or expired token']);
    exit;
}

$userId = $tokenData['user_id'];
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit;
}

// Validate required fields
$network = strtoupper(isset($input['network']) ? $input['network'] : '');
$phone = isset($input['phone']) ? $input['phone'] : '';
$planId = isset($input['plan_id']) ? $input['plan_id'] : '';

if (empty($network) || empty($phone) || empty($planId)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Network, phone number, and plan ID are required'
    ]);
    exit;
}

try {
    $db = (new Database())->getConnection();
    
    // Check if VTU is enabled
    $stmt = $db->prepare("SELECT setting_value FROM vtu_api_settings WHERE setting_key = 'vtu_enabled'");
    $stmt->execute();
    if ($stmt->fetchColumn() != '1') {
        http_response_code(503);
        echo json_encode(['success' => false, 'message' => 'VTU service is currently unavailable']);
        exit;
    }
    
    // Get provider ID
    $stmt = $db->prepare("SELECT setting_value FROM vtu_api_settings WHERE setting_key = 'active_provider_id'");
    $stmt->execute();
    $providerId = $stmt->fetchColumn();
    
    // Get plan details
    $stmt = $db->prepare("SELECT * FROM data_plans WHERE provider_id = ? AND network = ? AND plan_id = ? AND is_active = 1");
    $stmt->execute([$providerId, $network, $planId]);
    $plan = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$plan) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid data plan selected']);
        exit;
    }
    
    // Get commission
    $stmt = $db->prepare("SELECT setting_value FROM vtu_api_settings WHERE setting_key = 'data_commission_percent'");
    $stmt->execute();
    $commissionVal = $stmt->fetchColumn();
    $commissionPercent = floatval($commissionVal !== false ? $commissionVal : 0);
    
    $planPrice = floatval($plan['price']);
    $commission = ($planPrice * $commissionPercent) / 100;
    $totalAmount = $planPrice + $commission;
    
    // Check wallet balance
    $walletHelper = new WalletHelper();
    $walletBalance = $walletHelper->getBalance($userId);
    
    if ($walletBalance < $totalAmount) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Insufficient wallet balance',
            'required' => $totalAmount,
            'available' => $walletBalance
        ]);
        exit;
    }
    
    // Get VTU provider
    $vtuService = VTUServiceFactory::getActiveProvider();
    if (!$vtuService) {
        http_response_code(503);
        echo json_encode(['success' => false, 'message' => 'VTU service provider not configured']);
        exit;
    }
    
    // Generate transaction reference
    $transactionRef = 'DATA_' . $userId . '_' . time() . '_' . uniqid();
    
    // Create transaction record
    $stmt = $db->prepare("
        INSERT INTO vtu_transactions (
            user_id, provider_id, transaction_ref, transaction_type, network, 
            phone_number, amount, commission, plan_id, plan_name, data_amount,
            status, wallet_balance_before, wallet_balance_after, api_request
        ) VALUES (?, ?, ?, 'DATA', ?, ?, ?, ?, ?, ?, ?, 'PENDING', ?, ?, ?)
    ");
    
    $apiRequest = json_encode([
        'network' => $network,
        'phone' => $phone,
        'plan_id' => $planId
    ]);
    
    $stmt->execute([
        $userId, $providerId, $transactionRef, $network, $phone,
        $planPrice, $commission, $planId, $plan['plan_name'], $plan['data_amount'],
        $walletBalance, $walletBalance - $totalAmount, $apiRequest
    ]);
    
    $transactionId = $db->lastInsertId();
    
    // Deduct from wallet
    if (!$walletHelper->deductBalance($userId, $totalAmount, "Data purchase - {$network} - {$plan['plan_name']}")) {
        $stmt = $db->prepare("UPDATE vtu_transactions SET status = 'FAILED', status_message = ? WHERE id = ?");
        $stmt->execute(['Wallet deduction failed', $transactionId]);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to process payment']);
        exit;
    }
    
    // Update to PROCESSING
    $stmt = $db->prepare("UPDATE vtu_transactions SET status = 'PROCESSING' WHERE id = ?");
    $stmt->execute([$transactionId]);
    
    // Make API call
    $result = $vtuService->purchaseData($network, $phone, $planId);
    $apiResponse = json_encode($result);
    
    if ($result['success']) {
        $stmt = $db->prepare("
            UPDATE vtu_transactions 
            SET status = 'SUCCESS', status_message = 'Data purchase successful', 
                provider_ref = ?, api_response = ?, completed_at = NOW()
            WHERE id = ?
        ");
        
        $providerRef = null;
        if (isset($result['data']['reference'])) {
            $providerRef = $result['data']['reference'];
        } elseif (isset($result['data']['requestId'])) {
            $providerRef = $result['data']['requestId'];
        }
        
        $stmt->execute([$providerRef, $apiResponse, $transactionId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Data purchase successful',
            'data' => [
                'transaction_ref' => $transactionRef,
                'network' => $network,
                'phone' => $phone,
                'plan' => $plan['plan_name'],
                'data_amount' => $plan['data_amount'],
                'amount' => $planPrice,
                'commission' => $commission,
                'total' => $totalAmount,
                'new_balance' => $walletBalance - $totalAmount
            ]
        ]);
    } else {
        // Refund wallet
        $walletHelper->addBalance($userId, $totalAmount, "Data purchase refund - {$transactionRef}");
        
        $stmt = $db->prepare("
            UPDATE vtu_transactions 
            SET status = 'FAILED', status_message = ?, api_response = ?, completed_at = NOW()
            WHERE id = ?
        ");
        
        $message = isset($result['message']) ? $result['message'] : 'Data purchase failed';
        $stmt->execute([$message, $apiResponse, $transactionId]);
        
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $message,
            'transaction_ref' => $transactionRef
        ]);
    }
    
} catch (Exception $e) {
    error_log("Data Purchase Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred while processing your request']);
}
?>
