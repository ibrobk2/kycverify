<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

require_once __DIR__ . '/admin-jwt-helper.php';

$adminData = AdminJWTHelper::getAdminData();
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
        // Ensure defaults are present even if some configs exist
        $defaults = [
            ['service_name' => 'robosttech', 'base_url' => 'https://api.robosttech.com/v1', 'status' => 'inactive'],
            ['service_name' => 'dataverify', 'base_url' => 'https://api.dataverify.com.ng/v1', 'status' => 'active'],
            ['service_name' => 'gafiapay', 'base_url' => 'https://api.gafiapay.com/v1', 'status' => 'inactive'],
            ['service_name' => 'monnify', 'base_url' => 'https://api.monnify.com/v1', 'status' => 'inactive'],
            ['service_name' => 'katpay', 'base_url' => 'https://api.katpay.co/v1', 'status' => 'inactive']
        ];
        
        foreach ($defaults as $default) {
            $stmt = $db->prepare("SELECT id FROM api_configurations WHERE service_name = ?");
            $stmt->execute([$default['service_name']]);
            if (!$stmt->fetch()) {
                $stmt = $db->prepare("INSERT INTO api_configurations (service_name, base_url, status) VALUES (?, ?, ?)");
                $stmt->execute([$default['service_name'], $default['base_url'], $default['status']]);
            }
        }

        $stmt = $db->query("SELECT * FROM api_configurations ORDER BY service_name ASC");
        $configs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
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
    
    // POST - Update, Test, or Add
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = isset($input['action']) ? $input['action'] : 'update';
        
        if ($action === 'add') {
            if (empty($input['service_name']) || empty($input['base_url'])) {
                echo json_encode(['success' => false, 'message' => 'Service name and Base URL are required']);
                exit;
            }

            $stmt = $db->prepare("INSERT INTO api_configurations (service_name, base_url, api_key, api_secret, merchant_id, webhook_secret, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $input['service_name'],
                $input['base_url'],
                isset($input['api_key']) ? $input['api_key'] : null,
                isset($input['api_secret']) ? $input['api_secret'] : null,
                isset($input['merchant_id']) ? $input['merchant_id'] : null,
                isset($input['webhook_secret']) ? $input['webhook_secret'] : null,
                isset($input['status']) ? $input['status'] : 'inactive'
            ]);

            echo json_encode(['success' => true, 'message' => 'New API integration added successfully']);
        }
        elseif ($action === 'update') {
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
            if (!empty($input['merchant_id'])) {
                $updates[] = "merchant_id = ?";
                $params[] = $input['merchant_id'];
            }
            if (!empty($input['webhook_secret'])) {
                $updates[] = "webhook_secret = ?";
                $params[] = $input['webhook_secret'];
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
            $id = $input['id'];
            $stmt = $db->prepare("SELECT * FROM api_configurations WHERE id = ?");
            $stmt->execute([$id]);
            $config = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$config) {
                 echo json_encode(['success' => false, 'message' => 'Configuration not found']);
                 exit;
            }

            $success = false;
            $message = 'Connection failed';

            if ($config['service_name'] === 'katpay') {
                // Test KatPay - Actually try to call an endpoint to verify credentials
                if (!empty($config['api_key']) && !empty($config['api_secret'])) {
                    if (strpos($config['api_key'], '...') !== false || strpos($config['api_secret'], '...') !== false) {
                        $success = false;
                        $message = 'Cannot test with masked credentials. Please re-enter them.';
                    } else {
                        require_once __DIR__ . '/../KatPayService.php';
                        $service = new KatPayService();
                        // Try to get balance or something simple
                        $result = $service->testConnection();
                        
                        if ($result['success']) {
                            $success = true;
                            $message = isset($result['data']['message']) ? $result['data']['message'] : 'KatPay connection successful';
                        } else {
                            $success = false;
                            $message = 'KatPay connection failed: ' . $result['message'];
                        }
                    }
                } else {
                    $success = false;
                    $message = 'KatPay credentials (API Key/Secret) are missing';
                }
            } else {
                // Mock test for others
                sleep(1);
                $success = true;
                $message = 'Connection successful (Mock)';
            }
            
            echo json_encode([
                'success' => true, 
                'connection_status' => $success ? 'success' : 'failed',
                'message' => $message
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
