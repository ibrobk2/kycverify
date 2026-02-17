<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['id']) || !isset($input['status'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID and status are required']);
        exit;
    }
    
    $id = intval($input['id']);
    $status = strtolower($input['status']);
    $notes = isset($input['notes']) ? $input['notes'] : null;
    $adminId = is_object($adminData) ? $adminData->id : $adminData['id'];
    
    // Validate status
    $validStatuses = ['pending', 'processing', 'completed', 'failed'];
    if (!in_array($status, $validStatuses)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit;
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if it's a service transaction (from service_transactions table)
    // and what the current status is
    $stmt = $db->prepare("SELECT * FROM service_transactions WHERE id = ?");
    $stmt->execute([$id]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$transaction) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Transaction not found']);
        exit;
    }
    
    // Update the transaction
    $stmt = $db->prepare("UPDATE service_transactions SET status = ?, admin_notes = ?, updated_by = ? WHERE id = ?");
    $result = $stmt->execute([$status, $notes, $adminId, $id]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Status updated successfully'
        ]);
    } else {
        throw new Exception("Failed to update status in database");
    }
    
} catch (Exception $e) {
    error_log("Update service status error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
