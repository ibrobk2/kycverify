<?php
require_once __DIR__ . '/../config/config.php';

class JWTHelper {
    public static function decode($token) {
        $secret = JWT_SECRET;

        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }

        list($headerBase64, $payloadBase64, $signatureBase64) = $parts;

        $header = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $headerBase64)), true);
        $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $payloadBase64)), true);

        if (!$header || !$payload) {
            return false;
        }

        $signature = base64_decode(str_replace(['-', '_'], ['+', '/'], $signatureBase64));
        $expectedSignature = hash_hmac('sha256', "$headerBase64.$payloadBase64", $secret, true);

        if (!hash_equals($expectedSignature, $signature)) {
            return false;
        }

        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return false;
        }

        return (object) $payload;
    }

    public static function getUserIdFromToken() {
        $headers = getallheaders();
        $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';

        if (!$authHeader || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return false;
        }

        $token = $matches[1];
        $decoded = self::decode($token);
        
        return $decoded ? $decoded->user_id : false;
    }
}
?>
