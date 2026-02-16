<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

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
    
    // Get total users
    $stmt = $db->query("SELECT COUNT(*) as total, 
                        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                        SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive
                        FROM users");
    $userStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get total verifications
    $stmt = $db->query("SELECT COUNT(*) as total,
                        SUM(CASE WHEN verification_status = 'verified' THEN 1 ELSE 0 END) as success,
                        SUM(CASE WHEN verification_status = 'pending' OR verification_status = 'processing' THEN 1 ELSE 0 END) as pending,
                        SUM(CASE WHEN verification_status = 'rejected' OR verification_status = 'expired' THEN 1 ELSE 0 END) as failed
                        FROM verifications");
    $verificationStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get total revenue from wallet transactions (debits)
    $stmt = $db->query("SELECT 
                        SUM(CASE WHEN transaction_type = 'debit' THEN amount ELSE 0 END) as total_revenue,
                        SUM(CASE WHEN transaction_type = 'debit' AND DATE(created_at) = CURDATE() THEN amount ELSE 0 END) as today_revenue,
                        SUM(CASE WHEN transaction_type = 'debit' AND YEARWEEK(created_at) = YEARWEEK(NOW()) THEN amount ELSE 0 END) as week_revenue,
                        SUM(CASE WHEN transaction_type = 'debit' AND MONTH(created_at) = MONTH(NOW()) THEN amount ELSE 0 END) as month_revenue
                        FROM wallet_transactions");
    $revenueStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get VTU statistics if table exists
    $vtuStats = ['total' => 0, 'success' => 0, 'pending' => 0, 'failed' => 0, 'revenue' => 0];
    $tableCheck = $db->query("SHOW TABLES LIKE 'vtu_transactions'");
    if ($tableCheck->rowCount() > 0) {
        $stmt = $db->query("SELECT COUNT(*) as total,
                            SUM(CASE WHEN status = 'SUCCESS' THEN 1 ELSE 0 END) as success,
                            SUM(CASE WHEN status = 'PENDING' THEN 1 ELSE 0 END) as pending,
                            SUM(CASE WHEN status = 'FAILED' THEN 1 ELSE 0 END) as failed,
                            SUM(CASE WHEN status = 'SUCCESS' THEN amount + commission ELSE 0 END) as revenue
                            FROM vtu_transactions");
        $vtuStats = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Get recent users (last 7 days)
    $stmt = $db->query("SELECT DATE(created_at) as date, COUNT(*) as count 
                        FROM users 
                        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                        GROUP BY DATE(created_at)
                        ORDER BY date ASC");
    $userGrowth = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent transactions (last 10)
    $stmt = $db->query("SELECT wt.*, u.name as user_name, u.email as user_email
                        FROM wallet_transactions wt
                        LEFT JOIN users u ON wt.user_id = u.id
                        ORDER BY wt.created_at DESC
                        LIMIT 10");
    $recentTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get service usage statistics
    $stmt = $db->query("SELECT service_type, COUNT(*) as count
                        FROM verification_logs
                        GROUP BY service_type
                        ORDER BY count DESC
                        LIMIT 5");
    $serviceUsage = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get new users today
    $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE DATE(created_at) = CURDATE()");
    $newUsersToday = $stmt->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'data' => [
            'users' => [
                'total' => (int)$userStats['total'],
                'active' => (int)$userStats['active'],
                'inactive' => (int)$userStats['inactive'],
                'new_today' => (int)$newUsersToday
            ],
            'verifications' => [
                'total' => (int)(isset($verificationStats['total']) ? $verificationStats['total'] : 0),
                'success' => (int)(isset($verificationStats['success']) ? $verificationStats['success'] : 0),
                'pending' => (int)(isset($verificationStats['pending']) ? $verificationStats['pending'] : 0),
                'failed' => (int)(isset($verificationStats['failed']) ? $verificationStats['failed'] : 0)
            ],
            'revenue' => [
                'total' => (float)(isset($revenueStats['total_revenue']) ? $revenueStats['total_revenue'] : 0),
                'today' => (float)(isset($revenueStats['today_revenue']) ? $revenueStats['today_revenue'] : 0),
                'week' => (float)(isset($revenueStats['week_revenue']) ? $revenueStats['week_revenue'] : 0),
                'month' => (float)(isset($revenueStats['month_revenue']) ? $revenueStats['month_revenue'] : 0)
            ],
            'vtu' => [
                'total' => (int)(isset($vtuStats['total']) ? $vtuStats['total'] : 0),
                'success' => (int)(isset($vtuStats['success']) ? $vtuStats['success'] : 0),
                'pending' => (int)(isset($vtuStats['pending']) ? $vtuStats['pending'] : 0),
                'failed' => (int)(isset($vtuStats['failed']) ? $vtuStats['failed'] : 0),
                'revenue' => (float)(isset($vtuStats['revenue']) ? $vtuStats['revenue'] : 0)
            ],
            'user_growth' => $userGrowth,
            'recent_transactions' => $recentTransactions,
            'service_usage' => $serviceUsage
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Admin stats error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching statistics: ' . $e->getMessage()
    ]);
}
?>
