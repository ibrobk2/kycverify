<?php
// Turn off error reporting for production output, but log errors
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 0);

// Start output buffering
ob_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/jwt-helper.php';

// Clean buffer
ob_clean();

$userId = JWTHelper::getUserIdFromToken();

if (!$userId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Parameters
    $page = max(1, intval(isset($_GET['page']) ? $_GET['page'] : 1));
    $limit = min(50, max(5, intval(isset($_GET['limit']) ? $_GET['limit'] : 20)));
    $offset = ($page - 1) * $limit;
    
    $type = isset($_GET['type']) ? $_GET['type'] : 'all'; // all, wallet, vtu, service
    
    $unionParts = [];
    $unionParams = [];
    
    // 1. Wallet Transactions
    if ($type === 'all' || $type === 'wallet') {
        $unionParts[] = "SELECT 
                            wt.id, 
                            'wallet' as source,
                            wt.transaction_type as type,
                            wt.amount,
                            'completed' as status,
                            wt.description as details,
                            wt.reference,
                            wt.created_at
                         FROM wallet_transactions wt
                         WHERE wt.user_id = ?";
        $unionParams[] = $userId;
    }
    
    // 2. VTU Transactions
    if ($type === 'all' || $type === 'vtu') {
        // limit to successful ones or show all? usually user wants to see all
        $unionParts[] = "SELECT 
                            vt.id, 
                            'vtu' as source,
                            vt.transaction_type as type,
                            vt.amount,
                            LOWER(vt.status) as status,
                            CONCAT(vt.network, ' ', vt.phone_number) as details,
                            vt.transaction_ref as reference,
                            vt.created_at
                         FROM vtu_transactions vt
                         WHERE vt.user_id = ?";
        $unionParams[] = $userId;
    }
    
    // 3. Service Transactions
    if ($type === 'all' || $type === 'service') {
        $unionParts[] = "SELECT 
                            st.id, 
                            'service' as source,
                            st.service_type as type,
                            st.amount,
                            st.status,
                            st.service_type as details,
                            st.reference_number as reference,
                            st.created_at
                         FROM service_transactions st
                         WHERE st.user_id = ?";
        $unionParams[] = $userId;
    }
    
    if (empty($unionParts)) {
        echo json_encode([
            'success' => true,
            'data' => [],
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => 0,
                'total_pages' => 0
            ]
        ]);
        exit;
    }
    
    $unionQuery = implode(" UNION ALL ", $unionParts);
    
    // Count total
    $countQuery = "SELECT COUNT(*) FROM ($unionQuery) as combined";
    $stmt = $db->prepare($countQuery);
    // Bindparams for count
    foreach ($unionParams as $i => $val) {
        $stmt->bindValue($i + 1, $val);
    }
    $stmt->execute();
    $totalRecords = $stmt->fetchColumn();
    
    // Fetch Data
    $finalQuery = "SELECT * FROM ($unionQuery) as combined ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $stmt = $db->prepare($finalQuery);
    
    $paramIndex = 1;
    foreach ($unionParams as $val) {
        $stmt->bindValue($paramIndex++, $val);
    }
    $stmt->bindValue($paramIndex++, $limit, PDO::PARAM_INT);
    $stmt->bindValue($paramIndex++, $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $transactions,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $totalRecords,
            'total_pages' => ceil($totalRecords / $limit)
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Get Transactions Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred fetching transactions']);
}
?>
