<?php
// filepath: c:\xampp\htdocs\agentify\api\admin\fund-wallet.php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'c:\\xampp\\htdocs\\agentify\\api\\admin\\error.log');
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

require_once '../../config/database.php';
require_once __DIR__ . '/admin-jwt-helper.php';

try {
    $adminData = AdminJWTHelper::getAdminData();
    if (!$adminData) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }

    $database = new Database();
    $db = $database->getConnection();

    $input = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data: ' . json_last_error_msg()]);
        exit();
    }

    if (!isset($input['user_id']) || !isset($input['amount'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields: user_id and amount']);
        exit();
    }

    $userId = $input['user_id'];
    $amount = filter_var($input['amount'], FILTER_VALIDATE_FLOAT);

    if ($amount === false || $amount <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid amount. Must be a number greater than 0.']);
        exit();
    }

    $db->beginTransaction();

    $userQuery = "SELECT id, name, email, wallet FROM users WHERE id = ? FOR UPDATE";
    $userStmt = $db->prepare($userQuery);
    $userStmt->execute([$userId]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $db->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit();
    }

    $newBalance = (float)$user['wallet'] + $amount;
    $updateQuery = "UPDATE users SET wallet = ? WHERE id = ?";
    $updateStmt = $db->prepare($updateQuery);
    
    if ($updateStmt->execute([$newBalance, $userId])) {
        try {
            $logQuery = "INSERT INTO wallet_transactions (user_id, amount, transaction_type, description, previous_balance, new_balance, status) 
                         VALUES (?, ?, 'credit', ?, ?, ?, 'completed')";
            $logStmt = $db->prepare($logQuery);
            $logDetails = json_encode([
                'previous_balance' => (float)$user['wallet'],
                'amount_added' => $amount,
                'new_balance' => $newBalance,
                'admin_action' => true
            ]);
            $logStmt->execute([$userId, $amount, $logDetails, (float)$user['wallet'], $newBalance]);
        } catch (PDOException $e) {
            // Log the error and continue without failing the whole transaction
            error_log('Error logging wallet transaction: ' . $e->getMessage());
        }

        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Wallet funded successfully',
            'data' => [
                'user_id' => $userId,
                'user_name' => $user['name'],
                'user_email' => $user['email'],
                'previous_balance' => (float)$user['wallet'],
                'amount_added' => $amount,
                'new_balance' => $newBalance
            ]
        ]);
    } else {
        $db->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update wallet balance']);
    }
} catch (PDOException $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('PDOException in fund-wallet.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error',
        'error' => $e->getMessage()
    ]);
} catch (Throwable $e) {
    error_log('Throwable in fund-wallet.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error',
        'error' => $e->getMessage()
    ]);
}
