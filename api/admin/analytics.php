<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

// ADMIN_TOKEN_SECRET is defined in config.php

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
    
    $range = isset($_GET['range']) ? $_GET['range'] : '30days'; // 7days, 30days, 90days, 1year
    
    // Calculate date interval
    $interval = '30 DAY';
    if ($range === '7days') $interval = '7 DAY';
    elseif ($range === '90days') $interval = '90 DAY';
    elseif ($range === '1year') $interval = '1 YEAR';
    
    // 1. Revenue Over Time
    $stmt = $db->query("SELECT DATE(created_at) as date, SUM(amount) as total 
                        FROM wallet_transactions 
                        WHERE transaction_type = 'debit' 
                        AND created_at >= DATE_SUB(NOW(), INTERVAL $interval)
                        GROUP BY DATE(created_at)
                        ORDER BY date ASC");
    $revenueData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 2. Service Usage Breakdown
    $stmt = $db->query("SELECT verification_type as service_type, COUNT(*) as count 
                        FROM verifications 
                        WHERE initiated_at >= DATE_SUB(NOW(), INTERVAL $interval)
                        GROUP BY verification_type");
    $serviceUsage = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 3. Top Spenders
    $stmt = $db->query("SELECT u.name, u.email, SUM(wt.amount) as total_spent 
                        FROM wallet_transactions wt
                        JOIN users u ON wt.user_id = u.id
                        WHERE wt.transaction_type = 'debit'
                        AND wt.created_at >= DATE_SUB(NOW(), INTERVAL $interval)
                        GROUP BY wt.user_id
                        ORDER BY total_spent DESC
                        LIMIT 5");
    $topSpenders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 4. Transaction Success Rate (VTU)
    $vtuSuccessRate = [];
    $tableCheck = $db->query("SHOW TABLES LIKE 'vtu_transactions'");
    if ($tableCheck->rowCount() > 0) {
        $stmt = $db->query("SELECT status, COUNT(*) as count 
                            FROM vtu_transactions 
                            WHERE created_at >= DATE_SUB(NOW(), INTERVAL $interval)
                            GROUP BY status");
        $vtuSuccessRate = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'revenue_over_time' => $revenueData,
            'service_usage' => $serviceUsage,
            'top_spenders' => $topSpenders,
            'vtu_success_rate' => $vtuSuccessRate
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Admin analytics error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
