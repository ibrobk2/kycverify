<?php
require_once __DIR__ . '/../config/config.php';

class DataVerifyService {
    private $baseUrl;
    private $apiKey;

    public function __construct() {
        // Default values from config
        $this->baseUrl = defined('DATAVERIFY_BASE_URL') ? DATAVERIFY_BASE_URL : '';
        $this->apiKey = defined('DATAVERIFY_API_KEY') ? DATAVERIFY_API_KEY : '';

        // Try to fetch from database
        try {
            require_once __DIR__ . '/../config/database.php';
            $database = new Database();
            $db = $database->getConnection();
            
            $stmt = $db->prepare("SELECT base_url, api_key FROM api_configurations WHERE service_name = 'dataverify' AND status = 'active' LIMIT 1");
            $stmt->execute();
            $config = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($config) {
                if (!empty($config['base_url'])) $this->baseUrl = $config['base_url'];
                if (!empty($config['api_key'])) $this->apiKey = $config['api_key'];
            }
        } catch (Exception $e) {
            // Fallback to constants if DB fails
            error_log("DataVerifyService Config Error: " . $e->getMessage());
        }
    }

    private $slipEndpoints = [
        'nin' => [
            'premium' => 'https://dataverify.com.ng/developers/nin_slips/nin_premium.php',
            'regular' => 'https://dataverify.com.ng/developers/nin_slips/nin_regular.php',
            'standard' => 'https://dataverify.com.ng/developers/nin_slips/nin_standard.php',
            'vnin' => 'https://dataverify.com.ng/developers/nin_slips/vnin_slip.php'
        ],
        'phone' => [
            'premium' => 'https://dataverify.com.ng/developers/nin_slips/nin_premium_phone.php',
            'standard' => 'https://dataverify.com.ng/developers/nin_slips/nin_standard_phone.php',
            'regular' => 'https://dataverify.com.ng/developers/nin_slips/nin_regular_phone.php'
        ],
        'demographic' => [
            'premium' => 'https://dataverify.com.ng/developers/nin_slips/nin_premium_demo.php'
        ]
    ];

    private function makeRequest($endpoint, $data, $isSlipRequest = false) {
        $url = $isSlipRequest ? $endpoint : $this->baseUrl . $endpoint;
        
        $curl = curl_init();

        // Add API Key to body for slip requests
        if ($isSlipRequest) {
            $data['api_key'] = $this->apiKey;
        }

        $payload = json_encode($data);

        $headers = [
            "Content-Type: application/json",
            "Accept: application/json"
        ];

        // Only add Bearer token for non-slip requests (assuming standard API uses it)
        if (!$isSlipRequest) {
            $headers[] = "Authorization: Bearer " . $this->apiKey;
        }

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 60, // Increased timeout for PDF generation
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => $headers,
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if ($err) {
            error_log("DataVerify API Error: " . $err);
            return ['success' => false, 'message' => 'Service provider error: ' . $err];
        }

        $responseData = json_decode($response, true);
        
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            // Avoid logging full PDF base64 data to keep logs clean
            $logResponse = $responseData;
            if (isset($logResponse['pdf_base64'])) {
                $logResponse['pdf_base64'] = '[BASE64_PDF_DATA_TRUNCATED]';
            }
            error_log("DataVerify Response: " . json_encode($logResponse));
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
        // Fallback or specific logic if needed
        return $this->makeRequest('/nin/verify', ['nin' => $nin]); 
    }

    public function verifyBVN($bvn) {
        return $this->makeRequest('/bvn/verify', ['bvn' => $bvn]);
    }
    
    public function printNINSlip($verificationType, $slipType, $data) {
        if (!isset($this->slipEndpoints[$verificationType][$slipType])) {
            return ['success' => false, 'message' => 'Invalid verification or slip type combination'];
        }

        $url = $this->slipEndpoints[$verificationType][$slipType];
        
        // Pass entire data array (nin, phone, demo details)
        return $this->makeRequest($url, $data, true);
    }

    /**
     * Print BVN Slip - Only requires BVN number
     */
    public function printBVNSlip($bvn) {
        $url = 'https://dataverify.com.ng/developers/bvn/bvn_slip.php';
        $data = ['bvn' => $bvn];
        return $this->makeRequest($url, $data, true);
    }
}
?>
