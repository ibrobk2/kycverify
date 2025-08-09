<?php
// Admin authentication middleware
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

define('ADMIN_TOKEN_SECRET', 'your-very-secret-key'); // Must match admin-login.php

function getBearerToken() {
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        $matches = [];
        if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
            return $matches[1];
        }
    }
    // Fallback: try POST or GET
    if (isset($_POST['token'])) return $_POST['token'];
    if (isset($_GET['token'])) return $_GET['token'];
    return null;
}

$token = getBearerToken();
if (!$token) {
    echo json_encode(['success' => false, 'message' => 'No token provided']);
    exit;
}

$parts = explode('.', $token);
if (count($parts) !== 2) {
    echo json_encode(['success' => false, 'message' => 'Invalid token format']);
    exit;
}

list($payload_b64, $signature) = $parts;
$expected_signature = hash_hmac('sha256', $payload_b64, ADMIN_TOKEN_SECRET);

if (!hash_equals($expected_signature, $signature)) {
    echo json_encode(['success' => false, 'message' => 'Invalid token signature']);
    exit;
}

$payload_json = base64_decode($payload_b64);
$payload = json_decode($payload_json, true);

if (!$payload || !isset($payload['exp']) || $payload['exp'] < time()) {
    echo json_encode(['success' => false, 'message' => 'Token expired or invalid']);
    exit;
}

echo json_encode(['success' => true, 'admin_id' => $payload['admin_id'], 'email' => $payload['email']]);
exit;
?>
