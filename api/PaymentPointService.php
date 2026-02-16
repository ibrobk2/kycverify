<?php
require_once __DIR__ . '/../config/config.php';

class PaymentPointService {
    private $baseUrl;
    private $apiKey;
    private $secret;

    public function __construct() {
        $this->baseUrl = PAYMENTPOINT_BASE_URL;
        $this->apiKey = PAYMENTPOINT_API_KEY;
        $this->secret = PAYMENTPOINT_SECRET;
    }

    /**
     * Make a request to PaymentPoint API
     */
    private function makeRequest($endpoint, $data = [], $method = 'POST') {
        $url = $this->baseUrl . $endpoint;
        
        $curl = curl_init();

        $headers = [
            "Authorization: Bearer " . $this->apiKey,
            "Content-Type: application/json",
            "Accept: application/json"
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
            error_log("PaymentPoint API Error: " . $err);
            return ['success' => false, 'message' => 'Service provider error'];
        }

        $responseData = json_decode($response, true);
        
        if (DEBUG_MODE) {
            error_log("PaymentPoint Response ($httpCode): " . $response);
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
     */
    public function createVirtualAccount($user_id, $name, $email, $phone) {
        // Typical structure for virtual account creation
        $payload = [
            'account_name' => $name,
            'email' => $email,
            'phone' => $phone,
            'reference' => 'USER_' . $user_id,
            'permanent' => true
        ];

        // Using common endpoint pattern /virtual-accounts
        return $this->makeRequest('/virtual-accounts', $payload);
    }

    /**
     * Verify webhook signature
     */
    public function verifySignature($payload, $signature) {
        if (empty($signature)) return false;
        
        // Typical HMAC SHA512 signature verification
        $computedSignature = hash_hmac('sha512', $payload, $this->secret);
        return hash_equals($computedSignature, $signature);
    }
}
?>
