<?php
// Admin users management endpoint
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../../config/database.php';
require_once '../admin-auth.php';

// Authenticate admin
// $admin = authenticateAdmin();

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get all users with their details
    $query = "SELECT 
                u.id,
                u.full_name as name,
                u.email,
                u.phone,
                u.status,
                u.created_at,
                u.last_login,
                COUNT(v.id) as total_verifications,
                SUM(CASE WHEN v.status = 'pending' THEN 1 ELSE 0 END) as pending_verifications,
                SUM(CASE WHEN v.status = 'completed' THEN 1 ELSE 0 END) as completed_verifications
              FROM users u
              LEFT JOIN verifications v ON u.id = v.user_id
              GROUP BY u.id
              ORDER BY u.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the response
    $response = [
        'success' => true,
        'users' => array_map(function($user) {
            return [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'phone' => $user['phone'],
                'status' => $user['status'],
                'created_at' => $user['created_at'],
                'last_login' => $user['last_login'],
                'total_verifications' => (int)$user['total_verifications'],
                'pending_verifications' => (int)$user['pending_verifications'],
                'completed_verifications' => (int)$user['completed_verifications']
            ];
        }, $users)
    ];
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred',
        'error' => $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred',
        'error' => $e->getMessage()
    ]);
}

// const selected = validationTypeSelect.value;
?>
