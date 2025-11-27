<?php
require_once __DIR__ . '/../config/config.php';

class GafiapayService {
    private $baseUrl;
    private $apiKey;
    private $secret;

    public function __construct() {
        $this->baseUrl = GAFIAPAY_BASE_URL;
        $this->apiKey = GAFIAPAY_API_KEY;
        $this->secret = GAFIAPAY_SECRET;
    }

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
            error_log("Gafiapay API Error: " . $err);
            return ['success' => false, 'message' => 'Service provider error'];
        }

        $responseData = json_decode($response, true);
        
        if (DEBUG_MODE) {
            error_log("Gafiapay Response: " . $response);
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

    public function createVirtualAccount($user_id, $name, $bvn = null) {
        // Assuming endpoint for creating virtual account
        $payload = [
            'account_name' => $name,
            'reference' => 'USER_' . $user_id,
            // 'bvn' => $bvn // If required
        ];

        return $this->makeRequest('/accounts', $payload);
    }

    public function verifyTransaction($reference) {
        return $this->makeRequest('/transactions/' . $reference, [], 'GET');
    }

    public function verifySignature($payload, $signature) {
        // Standard HMAC SHA512 or SHA256 signature verification
        // Check Gafiapay docs for exact algorithm
        $computedSignature = hash_hmac('sha512', $payload, $this->secret);
        return hash_equals($computedSignature, $signature);
    }
}
?>
