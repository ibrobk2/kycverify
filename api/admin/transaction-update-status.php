<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/admin-jwt-helper.php';

$adminData = AdminJWTHelper::getAdminData();
if (!$adminData) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['transaction_id']) || !isset($input['status'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'transaction_id and status are required']);
        exit;
    }
    
    $transactionId = intval($input['transaction_id']);
    $newStatus = strtolower($input['status']);
    $adminNotes = isset($input['admin_notes']) ? trim($input['admin_notes']) : '';
    
    $allowedStatuses = ['completed', 'processing', 'pending', 'failed'];
    if (!in_array($newStatus, $allowedStatuses)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid status. Must be: completed, processing, pending, or failed']);
        exit;
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Only update service_transactions (wallet/VTU are API-managed)
    $stmt = $db->prepare("SELECT id, status FROM service_transactions WHERE id = ?");
    $stmt->execute([$transactionId]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$transaction) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Service transaction not found']);
        exit;
    }
    
    $oldStatus = $transaction['status'];
    
    // Update status
    $adminId = isset($adminData['id']) ? $adminData['id'] : (isset($adminData['sub']) ? $adminData['sub'] : null);
    
    $stmt = $db->prepare("
        UPDATE service_transactions 
        SET status = ?, admin_notes = ?, updated_by = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$newStatus, $adminNotes, $adminId, $transactionId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Transaction status updated from ' . $oldStatus . ' to ' . $newStatus,
        'data' => [
            'transaction_id' => $transactionId,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'admin_notes' => $adminNotes
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Admin transaction status update error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
