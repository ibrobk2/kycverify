<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

require_once __DIR__ . '/jwt-helper.php';

$userId = JWTHelper::getUserIdFromToken();

if (!$userId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get query parameters
$page = max(1, intval(isset($_GET['page']) ? $_GET['page'] : 1));
$limit = min(50, max(10, intval(isset($_GET['limit']) ? $_GET['limit'] : 20)));
$offset = ($page - 1) * $limit;

$type = isset($_GET['type']) ? $_GET['type'] : ''; // AIRTIME or DATA
$status = isset($_GET['status']) ? $_GET['status'] : ''; // PENDING, SUCCESS, FAILED

try {
    $db = (new Database())->getConnection();
    
    // Build query
    $where = ['user_id = ?'];
    $params = [$userId];
    
    if (!empty($type)) {
        $where[] = 'transaction_type = ?';
        $params[] = strtoupper($type);
    }
    
    if (!empty($status)) {
        $where[] = 'status = ?';
        $params[] = strtoupper($status);
    }
    
    $whereClause = implode(' AND ', $where);
    
    // Get total count
    $stmt = $db->prepare("SELECT COUNT(*) FROM vtu_transactions WHERE {$whereClause}");
    $stmt->execute($params);
    $totalCount = $stmt->fetchColumn();
    
    // Get transactions
    $stmt = $db->prepare("
        SELECT 
            vt.*,
            vp.provider_name
        FROM vtu_transactions vt
        LEFT JOIN vtu_providers vp ON vt.provider_id = vp.id
        WHERE {$whereClause}
        ORDER BY vt.created_at DESC
        LIMIT ? OFFSET ?
    ");
    
    // Bind params manually
    $paramIndex = 1;
    foreach ($params as $value) {
        $stmt->bindValue($paramIndex++, $value);
    }
    $stmt->bindValue($paramIndex++, $limit, PDO::PARAM_INT);
    $stmt->bindValue($paramIndex++, $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format transactions
    $formattedTransactions = array_map(function($tx) {
        return [
            'id' => $tx['id'],
            'transaction_ref' => $tx['transaction_ref'],
            'provider_ref' => $tx['provider_ref'],
            'type' => $tx['transaction_type'],
            'network' => $tx['network'],
            'phone_number' => $tx['phone_number'],
            'amount' => floatval($tx['amount']),
            'commission' => floatval($tx['commission']),
            'plan_name' => $tx['plan_name'],
            'data_amount' => $tx['data_amount'],
            'status' => $tx['status'],
            'status_message' => $tx['status_message'],
            'provider_name' => $tx['provider_name'],
            'created_at' => $tx['created_at'],
            'completed_at' => $tx['completed_at']
        ];
    }, $transactions);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'transactions' => $formattedTransactions,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $totalCount,
                'total_pages' => ceil($totalCount / $limit)
            ]
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Get VTU Transactions Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
?>
