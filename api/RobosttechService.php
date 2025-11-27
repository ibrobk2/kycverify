<?php
require_once __DIR__ . '/../config/config.php';

class RobosttechService {
    private $baseUrl;
    private $apiKey;

    public function __construct() {
        $this->baseUrl = ROBOSTTECH_BASE_URL;
        $this->apiKey = ROBOSTTECH_API_KEY;
    }

    private function makeRequest($endpoint, $data) {
        $url = $this->baseUrl . $endpoint;
        
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer " . $this->apiKey,
                "Content-Type: application/json",
                "Accept: application/json"
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if ($err) {
            error_log("Robosttech API Error: " . $err);
            return ['success' => false, 'message' => 'Service provider error'];
        }

        $responseData = json_decode($response, true);
        
        // Log the raw response for debugging (remove in production or log conditionally)
        if (DEBUG_MODE) {
            error_log("Robosttech Response: " . $response);
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true, 'data' => $responseData];
        } else {
            return [
                'success' => false, 
                'message' => isset($responseData['message']) ? $responseData['message'] : 'Verification failed',
                'details' => $responseData
            ];
        }
    }

    public function verifyNIN($nin) {
        return $this->makeRequest('/nin', ['nin' => $nin]);
    }

    public function verifyBVN($bvn) {
        return $this->makeRequest('/bvn', ['bvn' => $bvn]);
    }

    public function verifyPhone($phone) {
        return $this->makeRequest('/phone', ['phone' => $phone]);
    }
}
?>
