<?php
// Admin dashboard statistics endpoint
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../../config/database.php';

// Verify admin token
function verifyAdminToken($db) {
    $headers = getallheaders();
    if (!isset($headers['Authorization'])) {
        return false;
    }
    
    $token = str_replace('Bearer ', '', $headers['Authorization']);
    $query = "SELECT u.id, u.email, u.role 
              FROM admin_tokens t 
              JOIN users u ON t.user_id = u.id 
              WHERE t.token = :token AND t.expires_at > NOW() AND u.role = 'admin'";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':token', $token);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Verify admin
    $admin = verifyAdminToken($db);
    if (!$admin) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    // Get dashboard statistics
    
    // Total users
    $query = "SELECT COUNT(*) as total FROM users";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Active users (last 30 days)
    $query = "SELECT COUNT(*) as active FROM users WHERE last_login > DATE_SUB(NOW(), INTERVAL 30 DAY)";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $activeUsers = $stmt->fetch(PDO::FETCH_ASSOC)['active'];
    
    // Total applications
    $query = "SELECT COUNT(*) as total FROM applications";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $totalApplications = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Applications by status
    $query = "SELECT status, COUNT(*) as count FROM applications GROUP BY status";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $applicationsByStatus = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Recent applications (last 7 days)
    $query = "SELECT COUNT(*) as recent FROM applications WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $recentApplications = $stmt->fetch(PDO::FETCH_ASSOC)['recent'];
    
    // Total revenue (if applicable)
    $query = "SELECT SUM(amount) as total FROM payments WHERE status = 'completed'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalRevenue = $result['total'] ? (float)$result['total'] : 0.0;
    
    // System health
    $query = "SELECT 
                (SELECT COUNT(*) FROM users WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)) as new_users,
                (SELECT COUNT(*) FROM applications WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)) as new_applications,
                (SELECT COUNT(*) FROM applications WHERE status = 'pending') as pending_applications";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $systemHealth = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Prepare response
    $stats = [
        'success' => true,
        'data' => [
            'users' => [
                'total' => (int)$totalUsers,
                'active' => (int)$activeUsers,
                'new_today' => (int)$systemHealth['new_users']
            ],
            'applications' => [
                'total' => (int)$totalApplications,
                'recent' => (int)$recentApplications,
                'new_today' => (int)$systemHealth['new_applications'],
                'pending' => (int)$systemHealth['pending_applications'],
                'by_status' => $applicationsByStatus
            ],
            'revenue' => [
                'total' => $totalRevenue
            ],
            'system' => [
                'timestamp' => date('Y-m-d H:i:s'),
                'status' => 'healthy'
            ]
        ]
    ];
    
    echo json_encode($stats);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    error_log('Admin stats error: ' . $e->getMessage());
}
?>
