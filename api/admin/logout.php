<!-- generate logout.php assuming adminToken and adminUser are stored in LocalStorage -->
<?php
// filepath: c:\xampp\htdocs\agentify\api\admin\logout.php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../admin-auth.php';

// Authenticate admin
// $admin = authenticateAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Optionally, log the logout action
    $logQuery = "INSERT INTO admin_logs (admin_id, action, created_at) VALUES (?, 'logout', NOW())";
    $logStmt = $db->prepare($logQuery);
    $logStmt->execute([$admin['admin_id']]);

    // Prepare response
    $response = [
        'success' => true,
        'message' => 'Admin logged out successfully'
    ];

    echo json_encode($response);
    exit;

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred',
        'error' => $e->getMessage()
    ]);
    error_log('Admin users error: ' . $e->getMessage());
    exit;
}


