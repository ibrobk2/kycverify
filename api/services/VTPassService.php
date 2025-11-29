<?php
require_once __DIR__ . '/VTUServiceInterface.php';

/**
 * VTPass VTU Service Implementation
 * Implements VTUServiceInterface for VTPass API
 */
class VTPassService implements VTUServiceInterface {
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
     * Make HTTP request to VTPass API
     */
    private function makeRequest($endpoint, $data = [], $method = 'POST') {
        $url = $this->baseUrl . $endpoint;
        
        $curl = curl_init();
        
        // VTPass uses basic auth with api-key and public-key
        $headers = [
            "api-key: " . $this->apiKey,
            "public-key: " . $this->publicKey,
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
            CURLOPT_SSL_VERIFYPEER => false,
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
            error_log("VTPass API Error: " . $err);
            return [
                'success' => false,
                'message' => 'Network error: Unable to connect to VTU provider',
                'error' => $err
            ];
        }
        
        $responseData = json_decode($response, true);
        
        if (DEBUG_MODE) {
            error_log("VTPass Request to {$endpoint}: " . json_encode($data));
            error_log("VTPass Response: " . $response);
        }
        
        // VTPass response format
        if ($httpCode >= 200 && $httpCode < 300 && isset($responseData['code']) && $responseData['code'] === '000') {
            return [
                'success' => true,
                'data' => $responseData,
                'http_code' => $httpCode
            ];
        } else {
            $message = 'Request failed';
            if (isset($responseData['response_description'])) {
                $message = $responseData['response_description'];
            } elseif (isset($responseData['message'])) {
                $message = $responseData['message'];
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
        $network = strtolower($network);
        
        $payload = [
            'request_id' => $this->generateRequestId(),
            'serviceID' => $network . '-airtime',
            'amount' => (int)$amount,
            'phone' => $this->formatPhoneNumber($phone)
        ];
        
        return $this->makeRequest('/api/pay', $payload);
    }
    
    /**
     * Purchase data bundle
     */
    public function purchaseData($network, $phone, $planId) {
        $network = strtolower($network);
        
        $payload = [
            'request_id' => $this->generateRequestId(),
            'serviceID' => $network . '-data',
            'billersCode' => $this->formatPhoneNumber($phone),
            'variation_code' => $planId,
            'phone' => $this->formatPhoneNumber($phone)
        ];
        
        return $this->makeRequest('/api/pay', $payload);
    }
    
    /**
     * Get available data plans
     */
    public function getDataPlans($network) {
        $network = strtolower($network);
        $serviceID = $network . '-data';
        
        return $this->makeRequest('/api/service-variations?serviceID=' . $serviceID, [], 'GET');
    }
    
    /**
     * Verify transaction status
     */
    public function verifyTransaction($reference) {
        return $this->makeRequest('/api/requery', ['request_id' => $reference], 'POST');
    }
    
    /**
     * Get account balance
     */
    public function getBalance() {
        return $this->makeRequest('/api/balance', [], 'GET');
    }
    
    /**
     * Test API connection
     */
    public function testConnection() {
        try {
            $result = $this->getBalance();
            
            if ($result['success']) {
                $balance = 'N/A';
                if (isset($result['data']['contents']['balance'])) {
                    $balance = $result['data']['contents']['balance'];
                }
                
                return [
                    'success' => true,
                    'message' => 'Connection successful',
                    'provider' => 'VTPass',
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
                    'provider' => 'VTPass'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection error: ' . $e->getMessage(),
                'provider' => 'VTPass'
            ];
        }
    }
    
    /**
     * Format phone number
     */
    private function formatPhoneNumber($phone) {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        if (substr($phone, 0, 3) === '234') {
            return '0' . substr($phone, 3);
        }
        
        if (substr($phone, 0, 1) !== '0') {
            return '0' . $phone;
        }
        
        return $phone;
    }
    
    /**
     * Generate unique request ID
     */
    private function generateRequestId() {
        return 'VTP_' . time() . '_' . uniqid();
    }
}
?>
