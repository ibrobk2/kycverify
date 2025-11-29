<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

define('ADMIN_TOKEN_SECRET', 'your-very-secret-key');

function verifyAdminToken() {
    $headers = getallheaders();
    $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
    
    if (empty($authHeader) || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        return false;
    }
    
    $token = $matches[1];
    $parts = explode('.', $token);
    
    if (count($parts) !== 2) return false;
    
    list($payload_b64, $signature) = $parts;
    $expected_signature = hash_hmac('sha256', $payload_b64, ADMIN_TOKEN_SECRET);
    
    if (!hash_equals($expected_signature, $signature)) return false;
    
    $payload = json_decode(base64_decode($payload_b64), true);
    
    if (!$payload || (isset($payload['exp']) && $payload['exp'] < time())) return false;
    
    return $payload;
}

$adminData = verifyAdminToken();
if (!$adminData) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // GET - List configurations
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $db->query("SELECT * FROM api_configurations ORDER BY service_name ASC");
        $configs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // If no configs exist, return defaults
        if (empty($configs)) {
            $defaults = [
                ['service_name' => 'robosttech', 'base_url' => 'https://api.robosttech.com', 'status' => 'inactive'],
                ['service_name' => 'gafiapay', 'base_url' => 'https://api.gafiapay.com', 'status' => 'inactive'],
                ['service_name' => 'monnify', 'base_url' => 'https://api.monnify.com', 'status' => 'inactive']
            ];
            
            foreach ($defaults as $default) {
                $stmt = $db->prepare("INSERT INTO api_configurations (service_name, base_url, status) VALUES (?, ?, ?)");
                $stmt->execute([$default['service_name'], $default['base_url'], $default['status']]);
            }
            
            $stmt = $db->query("SELECT * FROM api_configurations ORDER BY service_name ASC");
            $configs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        // Mask secrets
        foreach ($configs as &$config) {
            if (!empty($config['api_secret'])) {
                $config['api_secret'] = substr($config['api_secret'], 0, 4) . '...' . substr($config['api_secret'], -4);
            }
            if (!empty($config['api_key'])) {
                $config['api_key'] = substr($config['api_key'], 0, 4) . '...' . substr($config['api_key'], -4);
            }
        }
        
        echo json_encode(['success' => true, 'data' => $configs]);
    }
    
    // POST - Update or Test
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = isset($input['action']) ? $input['action'] : 'update';
        
        if ($action === 'update') {
            $id = $input['id'];
            $apiKey = $input['api_key'];
            $apiSecret = $input['api_secret'];
            
            // Only update if provided (to allow keeping existing masked values)
            $updates = [];
            $params = [];
            
            if (!empty($input['base_url'])) {
                $updates[] = "base_url = ?";
                $params[] = $input['base_url'];
            }
            if (!empty($input['status'])) {
                $updates[] = "status = ?";
                $params[] = $input['status'];
            }
            if (!empty($apiKey) && strpos($apiKey, '...') === false) {
                $updates[] = "api_key = ?";
                $params[] = $apiKey;
            }
            if (!empty($apiSecret) && strpos($apiSecret, '...') === false) {
                $updates[] = "api_secret = ?";
                $params[] = $apiSecret;
            }
            
            if (!empty($updates)) {
                $params[] = $id;
                $sql = "UPDATE api_configurations SET " . implode(', ', $updates) . " WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
            }
            
            echo json_encode(['success' => true, 'message' => 'Configuration updated successfully']);
        }
        elseif ($action === 'test') {
            // Mock test connection
            // In real implementation, this would make a request to the service
            sleep(1); // Simulate network delay
            $success = rand(0, 1) === 1; // Random success/fail for demo
            
            echo json_encode([
                'success' => true, 
                'connection_status' => $success ? 'success' : 'failed',
                'message' => $success ? 'Connection successful' : 'Connection failed: Invalid credentials'
            ]);
        }
    }
    
} catch (Exception $e) {
    error_log("Admin API config error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
