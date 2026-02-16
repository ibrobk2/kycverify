<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/wallet-helper.php';
require_once __DIR__ . '/services/VTUServiceFactory.php';

// Enable error logging
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

require_once __DIR__ . '/jwt-helper.php';

$userId = JWTHelper::getUserIdFromToken();

if (!$userId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get request data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit;
}

// Validate required fields
$network = strtoupper(isset($input['network']) ? $input['network'] : '');
$phone = isset($input['phone']) ? $input['phone'] : '';
$amount = floatval(isset($input['amount']) ? $input['amount'] : 0);

if (empty($network) || empty($phone) || $amount <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Network, phone number, and amount are required'
    ]);
    exit;
}

// Validate network
$validNetworks = ['MTN', 'GLO', 'AIRTEL', '9MOBILE', 'ETISALAT'];
if (!in_array($network, $validNetworks)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid network. Must be MTN, GLO, AIRTEL, or 9MOBILE'
    ]);
    exit;
}

// Validate phone number
$phone = preg_replace('/[^0-9]/', '', $phone);
if (strlen($phone) < 10 || strlen($phone) > 14) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid phone number format'
    ]);
    exit;
}

// Check VTU settings
try {
    $db = (new Database())->getConnection();
    
    // Check if VTU is enabled
    $stmt = $db->prepare("SELECT setting_value FROM vtu_api_settings WHERE setting_key = 'vtu_enabled'");
    $stmt->execute();
    $vtuEnabled = $stmt->fetchColumn();
    
    if ($vtuEnabled != '1') {
        http_response_code(503);
        echo json_encode([
            'success' => false,
            'message' => 'VTU service is currently unavailable'
        ]);
        exit;
    }
    
    // Get min/max limits
    $stmt = $db->prepare("SELECT setting_key, setting_value FROM vtu_api_settings WHERE setting_key IN ('min_airtime_amount', 'max_airtime_amount')");
    $stmt->execute();
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $minAmount = floatval(isset($settings['min_airtime_amount']) ? $settings['min_airtime_amount'] : 50);
    $maxAmount = floatval(isset($settings['max_airtime_amount']) ? $settings['max_airtime_amount'] : 50000);
    
    if ($amount < $minAmount || $amount > $maxAmount) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => "Amount must be between ₦{$minAmount} and ₦{$maxAmount}"
        ]);
        exit;
    }
    
    // Get commission percentage
    $stmt = $db->prepare("SELECT setting_value FROM vtu_api_settings WHERE setting_key = 'airtime_commission_percent'");
    $stmt->execute();
    $commissionVal = $stmt->fetchColumn();
    $commissionPercent = floatval($commissionVal !== false ? $commissionVal : 0);
    
    // Calculate total amount (amount + commission)
    $commission = ($amount * $commissionPercent) / 100;
    $totalAmount = $amount + $commission;
    
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
    
    // Get active VTU provider
    $vtuService = VTUServiceFactory::getActiveProvider();
    
    if (!$vtuService) {
        http_response_code(503);
        echo json_encode([
            'success' => false,
            'message' => 'VTU service provider not configured'
        ]);
        exit;
    }
    
    // Get provider ID
    $stmt = $db->prepare("SELECT setting_value FROM vtu_api_settings WHERE setting_key = 'active_provider_id'");
    $stmt->execute();
    $providerId = $stmt->fetchColumn();
    
    // Generate transaction reference
    $transactionRef = 'AIR_' . $userId . '_' . time() . '_' . uniqid();
    
    // Create transaction record
    $stmt = $db->prepare("
        INSERT INTO vtu_transactions (
            user_id, provider_id, transaction_ref, transaction_type, network, 
            phone_number, amount, commission, status, wallet_balance_before, 
            wallet_balance_after, api_request
        ) VALUES (?, ?, ?, 'AIRTIME', ?, ?, ?, ?, 'PENDING', ?, ?, ?)
    ");
    
    $apiRequest = json_encode([
        'network' => $network,
        'phone' => $phone,
        'amount' => $amount
    ]);
    
    $stmt->execute([
        $userId,
        $providerId,
        $transactionRef,
        $network,
        $phone,
        $amount,
        $commission,
        $walletBalance,
        $walletBalance - $totalAmount,
        $apiRequest
    ]);
    
    $transactionId = $db->lastInsertId();
    
    // Deduct from wallet
    $deductResult = $walletHelper->deductAmount($userId, $totalAmount, "Airtime purchase - {$network} - {$phone}");
    
    if (!$deductResult) {
        // Update transaction status
        $stmt = $db->prepare("UPDATE vtu_transactions SET status = 'FAILED', status_message = ? WHERE id = ?");
        $stmt->execute(['Wallet deduction failed', $transactionId]);
        
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to process payment'
        ]);
        exit;
    }
    
    // Update transaction status to PROCESSING
    $stmt = $db->prepare("UPDATE vtu_transactions SET status = 'PROCESSING' WHERE id = ?");
    $stmt->execute([$transactionId]);
    
    // Make API call to provider
    $result = $vtuService->purchaseAirtime($network, $phone, $amount);
    
    // Update transaction with API response
    $apiResponse = json_encode($result);
    
    if ($result['success']) {
        $stmt = $db->prepare("
            UPDATE vtu_transactions 
            SET status = 'SUCCESS', 
                status_message = 'Airtime purchase successful', 
                provider_ref = ?,
                api_response = ?,
                completed_at = NOW()
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
            'message' => 'Airtime purchase successful',
            'data' => [
                'transaction_ref' => $transactionRef,
                'network' => $network,
                'phone' => $phone,
                'amount' => $amount,
                'commission' => $commission,
                'total' => $totalAmount,
                'new_balance' => $walletBalance - $totalAmount
            ]
        ]);
    } else {
        // Refund wallet
        $walletHelper->addAmount($userId, $totalAmount, "Airtime purchase refund - {$transactionRef}");
        
        $stmt = $db->prepare("
            UPDATE vtu_transactions 
            SET status = 'FAILED', 
                status_message = ?,
                api_response = ?,
                completed_at = NOW()
            WHERE id = ?
        ");
        
        $message = isset($result['message']) ? $result['message'] : 'Airtime purchase failed';
        $stmt->execute([$message, $apiResponse, $transactionId]);
        
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $message,
            'transaction_ref' => $transactionRef
        ]);
    }
    
} catch (Exception $e) {
    error_log("Airtime Purchase Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while processing your request'
    ]);
}
?>
