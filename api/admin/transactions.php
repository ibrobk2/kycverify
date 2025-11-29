<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

define('ADMIN_TOKEN_SECRET', 'your-very-secret-key');

function verifyAdminToken() {
    $headers = getallheaders();
    $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
    
    if (empty($authHeader) || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        return false;
    }
    
    $token = $matches[1];
    $parts = explode('.', $token);
    
    if (count($parts) !== 2) return false;
    
    list($payload_b64, $signature) = $parts;
    $expected_signature = hash_hmac('sha256', $payload_b64, ADMIN_TOKEN_SECRET);
    
    if (!hash_equals($expected_signature, $signature)) return false;
    
    $payload = json_decode(base64_decode($payload_b64), true);
    
    if (!$payload || (isset($payload['exp']) && $payload['exp'] < time())) return false;
    
    return $payload;
}

$adminData = verifyAdminToken();
if (!$adminData) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $page = max(1, intval(isset($_GET['page']) ? $_GET['page'] : 1));
    $limit = min(100, max(10, intval(isset($_GET['limit']) ? $_GET['limit'] : 50)));
    $offset = ($page - 1) * $limit;
    $type = isset($_GET['type']) ? $_GET['type'] : ''; // wallet, vtu, verification
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
    $endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';
    
    $transactions = [];
    $totalCount = 0;
    
    // Wallet transactions
    if (empty($type) || $type === 'wallet') {
        $where = ['1=1'];
        $params = [];
        
        if (!empty($status)) {
            // Wallet transactions don't have status, but we can filter by type
            if ($status === 'credit') {
                $where[] = "transaction_type = 'credit'";
            } elseif ($status === 'debit') {
                $where[] = "transaction_type = 'debit'";
            }
        }
        
        if (!empty($startDate)) {
            $where[] = "DATE(created_at) >= ?";
            $params[] = $startDate;
        }
        
        if (!empty($endDate)) {
            $where[] = "DATE(created_at) <= ?";
            $params[] = $endDate;
        }
        
        $whereClause = implode(' AND ', $where);
        
        // Get count
        $countQuery = "SELECT COUNT(*) FROM wallet_transactions WHERE $whereClause";
        $stmt = $db->prepare($countQuery);
        $stmt->execute($params);
        $totalCount = $stmt->fetchColumn();
        
        // Get transactions
        $query = "SELECT wt.*, u.name as user_name, u.email as user_email
                  FROM wallet_transactions wt
                  LEFT JOIN users u ON wt.user_id = u.id
                  WHERE $whereClause
                  ORDER BY wt.created_at DESC
                  LIMIT ? OFFSET ?";
        $stmt = $db->prepare($query);
        
        // Bind params manually
        $paramIndex = 1;
        foreach ($params as $value) {
            $stmt->bindValue($paramIndex++, $value);
        }
        $stmt->bindValue($paramIndex++, $limit, PDO::PARAM_INT);
        $stmt->bindValue($paramIndex++, $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $walletTxs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($walletTxs as $tx) {
            $transactions[] = [
                'id' => $tx['id'],
                'type' => 'wallet',
                'user_name' => $tx['user_name'],
                'user_email' => $tx['user_email'],
                'amount' => $tx['amount'],
                'transaction_type' => $tx['transaction_type'],
                'details' => $tx['details'],
                'reference' => $tx['reference'],
                'created_at' => $tx['created_at']
            ];
        }
    }
    
    // VTU transactions
    if ($type === 'vtu') {
        $tableCheck = $db->query("SHOW TABLES LIKE 'vtu_transactions'");
        if ($tableCheck->rowCount() > 0) {
            $where = ['1=1'];
            $params = [];
            
            if (!empty($status)) {
                $where[] = "status = ?";
                $params[] = strtoupper($status);
            }
            
            if (!empty($startDate)) {
                $where[] = "DATE(created_at) >= ?";
                $params[] = $startDate;
            }
            
            if (!empty($endDate)) {
                $where[] = "DATE(created_at) <= ?";
                $params[] = $endDate;
            }
            
            $whereClause = implode(' AND ', $where);
            
            $countQuery = "SELECT COUNT(*) FROM vtu_transactions WHERE $whereClause";
            $stmt = $db->prepare($countQuery);
            $stmt->execute($params);
            $totalCount = $stmt->fetchColumn();
            
            $query = "SELECT vt.*, u.name as user_name, u.email as user_email
                      FROM vtu_transactions vt
                      LEFT JOIN users u ON vt.user_id = u.id
                      WHERE $whereClause
                      ORDER BY vt.created_at DESC
                      LIMIT ? OFFSET ?";
            $stmt = $db->prepare($query);
            
            // Bind params manually
            $paramIndex = 1;
            foreach ($params as $value) {
                $stmt->bindValue($paramIndex++, $value);
            }
            $stmt->bindValue($paramIndex++, $limit, PDO::PARAM_INT);
            $stmt->bindValue($paramIndex++, $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            $vtuTxs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($vtuTxs as $tx) {
                $transactions[] = [
                    'id' => $tx['id'],
                    'type' => 'vtu',
                    'user_name' => $tx['user_name'],
                    'user_email' => $tx['user_email'],
                    'transaction_ref' => $tx['transaction_ref'],
                    'transaction_type' => $tx['transaction_type'],
                    'network' => $tx['network'],
                    'phone_number' => $tx['phone_number'],
                    'amount' => $tx['amount'],
                    'commission' => $tx['commission'],
                    'status' => $tx['status'],
                    'created_at' => $tx['created_at']
                ];
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'transactions' => $transactions,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $totalCount,
                'total_pages' => ceil($totalCount / $limit)
            ]
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Admin transactions error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
