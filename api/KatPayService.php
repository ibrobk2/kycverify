<?php
require_once __DIR__ . '/../config/config.php';

class KatPayService {
    private $baseUrl;
    private $apiKey;
    private $apiSecret;
    private $merchantId;
    private $webhookSecret;
    private $db;

    public function __construct() {
        try {
            require_once __DIR__ . '/../config/database.php';
            $this->db = (new Database())->getConnection();
            
            // Fetch active KatPay configuration from database
            $stmt = $this->db->prepare("SELECT * FROM api_configurations WHERE service_name = 'katpay' LIMIT 1");
            $stmt->execute();
            $config = $stmt->fetch(PDO::FETCH_ASSOC);

            // Use database config if present and fields are not empty, otherwise fall back to constants
            $this->baseUrl = (!empty($config['base_url'])) ? $config['base_url'] : (defined('KATPAY_BASE_URL') ? KATPAY_BASE_URL : 'https://api.katpay.co/v1');
            $this->apiKey = (!empty($config['api_key'])) ? $config['api_key'] : (defined('KATPAY_API_KEY') ? KATPAY_API_KEY : 'pk_live_yLoloF9JV0C7AUozJzd4bJU4hU6AV0nMf0FanZlMJGg9BRZhKngiOhVm6rHvNSjZ');
            $this->apiSecret = (!empty($config['api_secret'])) ? $config['api_secret'] : (defined('KATPAY_API_SECRET') ? KATPAY_API_SECRET : 'eyJpdiI6InpRMDQzdGlQckt4Ukc2dG9UU2JKaGc9PSIsInZhbHVlIjoiQy81dkttWC9EV0o1Q3h0bVkwKy9ES2xiOFNDREhLYU5maGdRZkFRSkx5a1NKaGNtcG9qNDFVQnBsaXBQcnFkck5IZjI2QmZXU1I4ZG5tVzV0TnhQTnA3VkZRVjVGM1BKNldEcjVmWkRyYy9jODlGa1VQZXlJUkM3SWIvZjBaSjUiLCJtYWMiOiIxODBjZjI3YmUwNTA0YmZjZGE2YTA3YzY1MDIwNjQ2ODJhMjEzM2U2YmFhOTQ1ZGU4ZjFmNTNjZDVkZDliNGUxIiwidGFnIjoiIn0=');
            $this->merchantId = (isset($config['merchant_id']) && !empty($config['merchant_id'])) ? $config['merchant_id'] : (defined('KATPAY_MERCHANT_ID') ? KATPAY_MERCHANT_ID : 'KAT8882490666');
            $this->webhookSecret = (isset($config['webhook_secret']) && !empty($config['webhook_secret'])) ? $config['webhook_secret'] : (defined('KATPAY_WEBHOOK_SECRET') ? KATPAY_WEBHOOK_SECRET : 'eyJpdiI6InpRMDQzdGlQckt4Ukc2dG9UU2JKaGc9PSIsInZhbHVlIjoiQy81dkttWC9EV0o1Q3h0bVkwKy9ES2xiOFNDREhLYU5maGdRZkFRSkx5a1NKaGNtcG9qNDFVQnBsaXBQcnFkck5IZjI2QmZXU1I4ZG5tVzV0TnhQTnA3VkZRVjVGM1BKNldEcjVmWkRyYy9jODlGa1VQZXlJUkM3SWIvZjBaSjUiLCJtYWMiOiIxODBjZjI3YmUwNTA0YmZjZGE2YTA3YzY1MDIwNjQ2ODJhMjEzM2U2YmFhOTQ1ZGU4ZjFmNTNjZDVkZDliNGUxIiwidGFnIjoiIn0=');

            // Log initialized values (REDACTED) for debugging
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                $maskedKey = $this->apiKey ? substr($this->apiKey, 0, 4) . '...' . substr($this->apiKey, -4) : 'NOT SET';
                $maskedSecret = $this->apiSecret ? substr($this->apiSecret, 0, 4) . '...' . substr($this->apiSecret, -4) : 'NOT SET';
                $this->logMessage("KatPayService Initialized: BaseUrl={$this->baseUrl}, ApiKey={$maskedKey}, ApiSecret={$maskedSecret}");
            }
        } catch (Exception $e) {
            error_log("KatPayService Constructor Error: " . $e->getMessage());
        }
    }

    /**
     * Make a request to KatPay API
     */
    public function makeRequest($endpoint, $data = [], $method = 'POST') {
        $url = $this->baseUrl . $endpoint;
        
        $curl = curl_init();

        $headers = [
            "Authorization: Bearer " . $this->apiSecret,
            "Content-Type: application/json",
            "Accept: application/json",
            "api-key: " . $this->apiKey
        ];

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
        ];

        if ($method === 'POST' || $method === 'PUT') {
            $options[CURLOPT_POSTFIELDS] = json_encode($data);
        }

        curl_setopt_array($curl, $options);

        // Logging the request
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            $this->logMessage("KatPay Request: $method $url Data: " . json_encode($data));
        }

        $response = curl_exec($curl);
        $err = curl_error($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if ($err) {
            $this->logMessage("KatPay Curl Error: " . $err);
            return ['success' => false, 'message' => 'Payment service error: ' . $err];
        }

        $responseData = json_decode($response, true);
        
        // Logging the response
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            $this->logMessage("KatPay Response ($httpCode): " . $response);
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            // Normalize result extraction - handle potential nesting differences
            $finalData = $responseData;
            
            // Check if response has account_number directly or inside a data object
            if (isset($responseData['data']['account_number'])) {
                $finalData = $responseData['data'];
            }
            
            return ['success' => true, 'data' => $finalData];
        } else {
            return [
                'success' => false, 
                'message' => isset($responseData['message']) ? $responseData['message'] : 'Request failed',
                'details' => $responseData
            ];
        }
    }

    /**
     * Test connection to KatPay API
     * Uses POST /virtual-accounts with empty data as a connectivity check.
     * A 422 validation error proves the API is reachable and credentials are valid.
     */
    public function testConnection() {
        $url = $this->baseUrl . '/virtual-accounts';
        
        $curl = curl_init();

        $headers = [
            "Authorization: Bearer " . $this->apiSecret,
            "Content-Type: application/json",
            "Accept: application/json",
            "api-key: " . $this->apiKey
        ];

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode([]),
            CURLOPT_HTTPHEADER => $headers,
        ];

        curl_setopt_array($curl, $options);

        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            $this->logMessage("KatPay Connection Test: POST $url");
        }

        $response = curl_exec($curl);
        $err = curl_error($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if ($err) {
            $this->logMessage("KatPay Connection Test Curl Error: " . $err);
            return ['success' => false, 'message' => 'Payment service unreachable: ' . $err];
        }

        $responseData = json_decode($response, true);

        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            $this->logMessage("KatPay Connection Test Response ($httpCode): " . $response);
        }

        // 422 = API reachable & credentials valid (validation error expected with empty data)
        if ($httpCode === 422) {
            return ['success' => true, 'data' => ['message' => 'KatPay API connected successfully']];
        }

        // 401/403 = Bad credentials
        if ($httpCode === 401 || $httpCode === 403) {
            return ['success' => false, 'message' => 'Invalid API credentials'];
        }

        // 404 with "Merchant not found" = Bad merchant ID but API is reachable
        if ($httpCode === 404 && isset($responseData['message']) && stripos($responseData['message'], 'Merchant') !== false) {
            return ['success' => false, 'message' => 'API reachable but Merchant ID is invalid. Please check your Merchant ID configuration.'];
        }

        // 2xx = Unexpected success (shouldn't happen with empty body, but accept it)
        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true, 'data' => $responseData];
        }

        return [
            'success' => false,
            'message' => isset($responseData['message']) ? $responseData['message'] : 'Connection test failed (HTTP ' . $httpCode . ')',
            'details' => $responseData
        ];
    }

    /**
     * Create a dedicated virtual account for a user
     * Supported bankCode: PALMPAY, OPAY, 20897
     */
    public function createVirtualAccount($user_id, $name, $email, $phone) {
        $payload = [
            'email' => $email,
            'name' => $name,
            'phoneNumber' => $phone,
            'bankCode' => ['PALMPAY'],
            'merchantID' => $this->merchantId
        ];

        return $this->makeRequest('/virtual-accounts', $payload);
    }

    /**
     * Verify webhook signature using HMAC SHA-256
     * KatPay signs: timestamp.payload
     * Headers: X-Katpay-Signature, X-Katpay-Timestamp
     */
    public function verifySignature($payload, $signature, $timestamp) {
        if (empty($signature) || empty($timestamp)) return false;
        
        $signedPayload = $timestamp . '.' . $payload;
        $expectedSignature = hash_hmac('sha256', $signedPayload, $this->webhookSecret);
        return hash_equals($expectedSignature, $signature);
    }

    private function logMessage($message) {
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
        $logFile = $logDir . '/katpay_api.log';
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
    }
}
?>
