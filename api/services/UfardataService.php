<?php
require_once __DIR__ . '/VTUServiceInterface.php';

/**
 * Ufardata VTU Service Implementation
 * Implements VTUServiceInterface for Ufardata API
 */
class UfardataService implements VTUServiceInterface {
    private $baseUrl;
    private $apiKey;
    private $apiSecret;
    private $publicKey;
    private $providerConfig;
    
    public function __construct($providerConfig) {
        $this->providerConfig = $providerConfig;
        $this->baseUrl = rtrim($providerConfig['base_url'], '/');
        $this->apiKey = $providerConfig['api_key'];
        $this->apiSecret = isset($providerConfig['api_secret']) ? $providerConfig['api_secret'] : null;
        $this->publicKey = isset($providerConfig['public_key']) ? $providerConfig['public_key'] : null;
    }
    
    /**
     * Make HTTP request to Ufardata API
     */
    private function makeRequest($endpoint, $data = [], $method = 'POST') {
        $url = $this->baseUrl . $endpoint;
        
        $curl = curl_init();
        
        // Ufardata typically uses Authorization header with API key
        $headers = [
            "Authorization: Token " . $this->apiKey,
            "Content-Type: application/json",
            "Accept: application/json"
        ];
        
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => false, // Set to true in production with proper SSL
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
            error_log("Ufardata API Error: " . $err);
            return [
                'success' => false,
                'message' => 'Network error: Unable to connect to VTU provider',
                'error' => $err
            ];
        }
        
        $responseData = json_decode($response, true);
        
        if (DEBUG_MODE) {
            error_log("Ufardata Request to {$endpoint}: " . json_encode($data));
            error_log("Ufardata Response: " . $response);
        }
        
        // Ufardata typically returns success in the response
        // Adjust this based on actual API response format
        if ($httpCode >= 200 && $httpCode < 300) {
            return [
                'success' => true,
                'data' => $responseData,
                'http_code' => $httpCode
            ];
        } else {
            $message = 'Request failed';
            if (isset($responseData['message'])) {
                $message = $responseData['message'];
            } elseif (isset($responseData['error'])) {
                $message = $responseData['error'];
            }
            
            return [
                'success' => false,
                'message' => $message,
                'data' => $responseData,
                'http_code' => $httpCode
            ];
        }
    }
    
    /**
     * Purchase airtime
     */
    public function purchaseAirtime($network, $phone, $amount) {
        // Normalize network code
        $network = strtoupper($network);
        
        // Ufardata airtime purchase endpoint (adjust based on actual API)
        $payload = [
            'network' => $this->mapNetworkCode($network),
            'phone' => $this->formatPhoneNumber($phone),
            'amount' => (float)$amount,
            'bypass' => false, // Set to true for instant delivery
            'request_id' => $this->generateRequestId()
        ];
        
        return $this->makeRequest('/topup/', $payload);
    }
    
    /**
     * Purchase data bundle
     */
    public function purchaseData($network, $phone, $planId) {
        // Normalize network code
        $network = strtoupper($network);
        
        // Ufardata data purchase endpoint (adjust based on actual API)
        $payload = [
            'network' => $this->mapNetworkCode($network),
            'phone' => $this->formatPhoneNumber($phone),
            'plan' => $planId,
            'bypass' => false,
            'request_id' => $this->generateRequestId()
        ];
        
        return $this->makeRequest('/data/', $payload);
    }
    
    /**
     * Get available data plans
     */
    public function getDataPlans($network) {
        // Normalize network code
        $network = strtoupper($network);
        
        // Ufardata data plans endpoint (adjust based on actual API)
        $endpoint = '/data-plans/?network=' . $this->mapNetworkCode($network);
        
        return $this->makeRequest($endpoint, [], 'GET');
    }
    
    /**
     * Verify transaction status
     */
    public function verifyTransaction($reference) {
        // Ufardata transaction verification endpoint
        $endpoint = '/verify/' . $reference;
        
        return $this->makeRequest($endpoint, [], 'GET');
    }
    
    /**
     * Get account balance
     */
    public function getBalance() {
        // Ufardata balance endpoint
        return $this->makeRequest('/user/', [], 'GET');
    }
    
    /**
     * Test API connection
     */
    public function testConnection() {
        try {
            $result = $this->getBalance();
            
            if ($result['success']) {
                $balance = 'N/A';
                if (isset($result['data']['balance'])) {
                    $balance = $result['data']['balance'];
                }
                
                return [
                    'success' => true,
                    'message' => 'Connection successful',
                    'provider' => 'Ufardata',
                    'balance' => $balance
                ];
            } else {
                $message = 'Unknown error';
                if (isset($result['message'])) {
                    $message = $result['message'];
                }
                
                return [
                    'success' => false,
                    'message' => 'Connection failed: ' . $message,
                    'provider' => 'Ufardata'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection error: ' . $e->getMessage(),
                'provider' => 'Ufardata'
            ];
        }
    }
    
    /**
     * Map network codes to provider-specific codes
     */
    private function mapNetworkCode($network) {
        $networkMap = [
            'MTN' => '1',
            'GLO' => '2',
            'AIRTEL' => '3',
            '9MOBILE' => '4',
            'ETISALAT' => '4' // Alias for 9mobile
        ];
        
        return isset($networkMap[$network]) ? $networkMap[$network] : $network;
    }
    
    /**
     * Format phone number to Nigerian format
     */
    private function formatPhoneNumber($phone) {
        // Remove any non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // If starts with 234, keep as is
        if (substr($phone, 0, 3) === '234') {
            return $phone;
        }
        
        // If starts with 0, replace with 234
        if (substr($phone, 0, 1) === '0') {
            return '234' . substr($phone, 1);
        }
        
        // If 10 digits, add 234
        if (strlen($phone) === 10) {
            return '234' . $phone;
        }
        
        return $phone;
    }
    
    /**
     * Generate unique request ID
     */
    private function generateRequestId() {
        return 'UFAR_' . time() . '_' . uniqid();
    }
}
?>
