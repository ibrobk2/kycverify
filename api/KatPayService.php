<?php
require_once __DIR__ . '/../config/config.php';

class KatPayService {
    private $baseUrl;
    private $apiKey;
    private $apiSecret;
    private $merchantId;
    private $webhookSecret;

    public function __construct() {
        $this->baseUrl = defined('KATPAY_BASE_URL') ? KATPAY_BASE_URL : 'https://api.katpay.co/v1';
        $this->apiKey = defined('KATPAY_API_KEY') ? KATPAY_API_KEY : '';
        $this->apiSecret = defined('KATPAY_API_SECRET') ? KATPAY_API_SECRET : '';
        $this->merchantId = defined('KATPAY_MERCHANT_ID') ? KATPAY_MERCHANT_ID : '';
        $this->webhookSecret = defined('KATPAY_WEBHOOK_SECRET') ? KATPAY_WEBHOOK_SECRET : '';
    }

    /**
     * Make a request to KatPay API
     */
    private function makeRequest($endpoint, $data = [], $method = 'POST') {
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

        $response = curl_exec($curl);
        $err = curl_error($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if ($err) {
            error_log("KatPay API Error: " . $err);
            return ['success' => false, 'message' => 'Payment service error: ' . $err];
        }

        $responseData = json_decode($response, true);
        
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log("KatPay Response ($httpCode): " . $response);
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true, 'data' => $responseData];
        } else {
            return [
                'success' => false, 
                'message' => isset($responseData['message']) ? $responseData['message'] : 'Request failed',
                'details' => $responseData
            ];
        }
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
}
?>
