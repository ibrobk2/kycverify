<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../api/wallet-helper.php';

// ADMIN_TOKEN_SECRET is defined in config.php

require_once __DIR__ . '/admin-jwt-helper.php';

$adminData = AdminJWTHelper::getAdminData();
if (!$adminData) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // GET - List users
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $page = max(1, intval(isset($_GET['page']) ? $_GET['page'] : 1));
        $limit = min(100, max(10, intval(isset($_GET['limit']) ? $_GET['limit'] : 20)));
        $offset = ($page - 1) * $limit;
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        
        // Build query
        $where = [];
        $params = [];
        
        if (!empty($search)) {
            $where[] = "(name LIKE ? OR email LIKE ? OR phone LIKE ?)";
            $searchTerm = "%{$search}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if (!empty($status)) {
            $where[] = "status = ?";
            $params[] = $status;
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        // Get total count
        $countQuery = "SELECT COUNT(*) FROM users $whereClause";
        $stmt = $db->prepare($countQuery);
        $stmt->execute($params);
        $totalCount = $stmt->fetchColumn();
        
        // Get users
        $query = "SELECT id, name, email, phone, company, wallet, status, email_verified, 
                  virtual_account_number, bank_name, account_name, last_login, created_at 
                  FROM users $whereClause ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $stmt = $db->prepare($query);
        
        // Bind params manually
        $paramIndex = 1;
        foreach ($params as $value) {
            $stmt->bindValue($paramIndex++, $value);
        }
        $stmt->bindValue($paramIndex++, $limit, PDO::PARAM_INT);
        $stmt->bindValue($paramIndex++, $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'users' => $users,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $totalCount,
                    'total_pages' => ceil($totalCount / $limit)
                ]
            ]
        ]);
    }
    
    // POST - Update user or manage wallet
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = isset($input['action']) ? $input['action'] : '';
        $userId = isset($input['user_id']) ? $input['user_id'] : 0;
        
        if (!$userId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'User ID required']);
            exit;
        }
        
        if ($action === 'update_status') {
            $newStatus = isset($input['status']) ? $input['status'] : '';
            if (!in_array($newStatus, ['active', 'inactive', 'suspended'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid status']);
                exit;
            }
            
            $stmt = $db->prepare("UPDATE users SET status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $userId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'User status updated successfully'
            ]);
        }
        elseif ($action === 'credit_wallet') {
            $amount = floatval(isset($input['amount']) ? $input['amount'] : 0);
            $details = isset($input['details']) ? $input['details'] : 'Admin credit';
            
            if ($amount <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid amount']);
                exit;
            }
            
            $walletHelper = new WalletHelper();
            $result = $walletHelper->addAmount($userId, $amount, $details);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Wallet credited successfully',
                    'new_balance' => $walletHelper->getBalance($userId)
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to credit wallet']);
            }
        }
        elseif ($action === 'debit_wallet') {
            $amount = floatval(isset($input['amount']) ? $input['amount'] : 0);
            $details = isset($input['details']) ? $input['details'] : 'Admin debit';
            
            if ($amount <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid amount']);
                exit;
            }
            
            $walletHelper = new WalletHelper();
            $result = $walletHelper->deductAmount($userId, $amount, $details);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Wallet debited successfully',
                    'new_balance' => $walletHelper->getBalance($userId)
                ]);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Insufficient balance or failed to debit']);
            }
        }
        else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    }
    
} catch (Exception $e) {
    error_log("Admin users error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
