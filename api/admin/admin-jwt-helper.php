<?php
require_once __DIR__ . '/../../config/config.php';

class AdminJWTHelper {
    /**
     * Get admin data from JWT token in Authorization header
     * @return array|false Admin payload or false if invalid
     */
    public static function getAdminData() {
        $headers = getallheaders();
        $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
        
        if (empty($authHeader) || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return false;
        }
        
        $token = $matches[1];
        $parts = explode('.', $token);
        
        if (count($parts) !== 2) {
            return false;
        }
        
        list($payload_b64, $signature) = $parts;
        $expected_signature = hash_hmac('sha256', $payload_b64, ADMIN_TOKEN_SECRET);
        
        if (!hash_equals($expected_signature, $signature)) {
            return false;
        }
        
        $payload_json = base64_decode($payload_b64);
        $payload = json_decode($payload_json, true);
        
        if (!$payload || (isset($payload['exp']) && $payload['exp'] < time())) {
            return false;
        }
        
        return $payload;
    }

    /**
     * Verify if the current request has a valid admin token
     * @return bool
     */
    public static function verify() {
        return self::getAdminData() !== false;
    }
}
?>
