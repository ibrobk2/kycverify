<?php
// filepath: C:/xampp/htdocs/lildone/api/auth.php
require_once __DIR__ . '/../config/config.php';

class Auth {
    public static function getBearerToken() {
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            $matches = [];
            if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
                return $matches[1];
            }
        }
        return null;
    }

    public static function generateToken($admin_id, $email) {
        $payload = [
            'admin_id' => $admin_id,
            'email' => $email,
            'exp' => time() + (24 * 60 * 60) // 24 hours
        ];
        $payload_json = json_encode($payload);
        $payload_b64 = base64_encode($payload_json);
        $signature = hash_hmac('sha256', $payload_b64, ADMIN_TOKEN_SECRET);
        return $payload_b64 . '.' . $signature;
    }

    public static function validateToken($token) {
        $parts = explode('.', $token);
        if (count($parts) !== 2) {
            return ['success' => false, 'message' => 'Invalid token format'];
        }

        list($payload_b64, $signature) = $parts;
        $expected_signature = hash_hmac('sha256', $payload_b64, ADMIN_TOKEN_SECRET);

        if (!hash_equals($expected_signature, $signature)) {
            return ['success' => false, 'message' => 'Invalid token signature'];
        }

        $payload_json = base64_decode($payload_b64);
        $payload = json_decode($payload_json, true);

        if (!$payload || !isset($payload['exp']) || $payload['exp'] < time()) {
            return ['success' => false, 'message' => 'Token expired or invalid'];
        }

        return ['success' => true, 'admin_id' => $payload['admin_id'], 'email' => $payload['email']];
    }

    public static function authenticate() {
        $token = self::getBearerToken();
        if (!$token) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'No token provided']);
            exit;
        }

        $validation = self::validateToken($token);
        if (!$validation['success']) {
            http_response_code(401);
            echo json_encode($validation);
            exit;
        }
        return $validation;
    }
}
?>