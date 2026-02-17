<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
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
    
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $type = isset($_GET['type']) ? $_GET['type'] : 'service'; // service, wallet, vtu
    
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Transaction ID required']);
        exit;
    }
    
    $transaction = null;
    
    if ($type === 'wallet') {
        $stmt = $db->prepare("
            SELECT wt.*, u.name as user_name, u.email as user_email, u.phone as user_phone
            FROM wallet_transactions wt
            LEFT JOIN users u ON wt.user_id = u.id
            WHERE wt.id = ?
        ");
        $stmt->execute([$id]);
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($transaction) $transaction['type'] = 'wallet';
        
    } elseif ($type === 'vtu') {
        $tableCheck = $db->query("SHOW TABLES LIKE 'vtu_transactions'");
        if ($tableCheck->rowCount() > 0) {
            $stmt = $db->prepare("
                SELECT vt.*, u.name as user_name, u.email as user_email, u.phone as user_phone,
                       vp.provider_name
                FROM vtu_transactions vt
                LEFT JOIN users u ON vt.user_id = u.id
                LEFT JOIN vtu_providers vp ON vt.provider_id = vp.id
                WHERE vt.id = ?
            ");
            $stmt->execute([$id]);
            $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($transaction) $transaction['type'] = 'vtu';
        }
        
    } else {
        // service_transactions
        $stmt = $db->prepare("
            SELECT st.*, u.name as user_name, u.email as user_email, u.phone as user_phone
            FROM service_transactions st
            LEFT JOIN users u ON st.user_id = u.id
            WHERE st.id = ?
        ");
        $stmt->execute([$id]);
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($transaction) $transaction['type'] = 'service';
    }
    
    if (!$transaction) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Transaction not found']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $transaction
    ]);
    
} catch (Exception $e) {
    error_log("Admin transaction detail error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
