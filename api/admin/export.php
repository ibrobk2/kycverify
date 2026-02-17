<?php
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="export.csv"');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

// ADMIN_TOKEN_SECRET is defined in config.php

// Verify token from query string since this is a direct download link
$token = isset($_GET['token']) ? $_GET['token'] : '';
if (empty($token)) {
    die('Unauthorized');
}

$parts = explode('.', $token);
if (count($parts) !== 2) die('Invalid token');

list($payload_b64, $signature) = $parts;
$expected_signature = hash_hmac('sha256', $payload_b64, ADMIN_TOKEN_SECRET);

if (!hash_equals($expected_signature, $signature)) die('Invalid signature');

$payload = json_decode(base64_decode($payload_b64), true);
if (!$payload || (isset($payload['exp']) && $payload['exp'] < time())) die('Token expired');

try {
    $database = new Database();
    $db = $database->getConnection();
    $type = isset($_GET['type']) ? $_GET['type'] : '';
    $output = fopen('php://output', 'w');
    
    if ($type === 'users') {
        header('Content-Disposition: attachment; filename="users_export_' . date('Y-m-d') . '.csv"');
        fputcsv($output, ['ID', 'Name', 'Email', 'Phone', 'Wallet Balance', 'Status', 'Joined Date']);
        
        $stmt = $db->query("SELECT id, name, email, phone, wallet, status, created_at FROM users ORDER BY created_at DESC");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, $row);
        }
    }
    elseif ($type === 'transactions') {
        header('Content-Disposition: attachment; filename="transactions_export_' . date('Y-m-d') . '.csv"');
        fputcsv($output, ['ID', 'User', 'Email', 'Type', 'Amount', 'Details', 'Date']);
        
        $stmt = $db->query("SELECT wt.id, u.name, u.email, wt.transaction_type, wt.amount, wt.description as details, wt.created_at 
                           FROM wallet_transactions wt 
                           LEFT JOIN users u ON wt.user_id = u.id 
                           ORDER BY wt.created_at DESC LIMIT 1000");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, $row);
        }
    }
    
    fclose($output);
    
} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}
?>
