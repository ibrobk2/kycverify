<?php
// filepath: c:\xampp\htdocs\agentify\api\admin-auth.php
// Enable error reporting for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// ADMIN_TOKEN_SECRET is defined in config.php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

require_once __DIR__ . '/admin/admin-jwt-helper.php';

$adminData = AdminJWTHelper::getAdminData();

if (!$adminData) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized or token expired']);
    exit;
}


// If authenticated, fetch users
try {
    $database = new Database();
    $db = $database->getConnection();

    $stmt = $db->query("SELECT id, name, email, status, created_at FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'admin_id' => $adminData['admin_id'],
        'email' => $adminData['email'],
        'users' => $users
    ]);
    exit;
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred',
        'error' => $e->getMessage()
    ]);
    exit;
}
?>
