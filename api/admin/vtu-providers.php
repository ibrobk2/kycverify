<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE');
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
    
    // GET - List providers
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $db->query("SELECT * FROM vtu_providers ORDER BY is_default DESC, name ASC");
        $providers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $providers
        ]);
    }
    
    // POST - Add or Update provider
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = isset($input['action']) ? $input['action'] : 'create';
        
        if ($action === 'create') {
            $stmt = $db->prepare("INSERT INTO vtu_providers (name, code, api_url, api_key, api_secret, status, is_default) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $input['name'],
                $input['code'],
                $input['api_url'],
                isset($input['api_key']) ? $input['api_key'] : null,
                isset($input['api_secret']) ? $input['api_secret'] : null,
                isset($input['status']) ? $input['status'] : 'active',
                isset($input['is_default']) ? $input['is_default'] : 0
            ]);
            
            if (isset($input['is_default']) && $input['is_default']) {
                $lastId = $db->lastInsertId();
                $db->query("UPDATE vtu_providers SET is_default = 0 WHERE id != $lastId");
            }
            
            echo json_encode(['success' => true, 'message' => 'Provider added successfully']);
        }
        elseif ($action === 'update') {
            $id = $input['id'];
            $stmt = $db->prepare("UPDATE vtu_providers SET name=?, code=?, api_url=?, api_key=?, api_secret=?, status=?, is_default=? WHERE id=?");
            $stmt->execute([
                $input['name'],
                $input['code'],
                $input['api_url'],
                $input['api_key'],
                $input['api_secret'],
                $input['status'],
                $input['is_default'],
                $id
            ]);
            
            if ($input['is_default']) {
                $db->query("UPDATE vtu_providers SET is_default = 0 WHERE id != $id");
            }
            
            echo json_encode(['success' => true, 'message' => 'Provider updated successfully']);
        }
        elseif ($action === 'check_balance') {
            // Placeholder for balance check logic
            // In a real implementation, this would call the provider's API
            echo json_encode(['success' => true, 'balance' => 0.00, 'message' => 'Balance check not implemented for this provider']);
        }
    }
    
    // DELETE - Remove provider
    elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $id = isset($_GET['id']) ? $_GET['id'] : 0;
        if ($id) {
            $stmt = $db->prepare("DELETE FROM vtu_providers WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Provider deleted successfully']);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID required']);
        }
    }
    
} catch (Exception $e) {
    error_log("Admin VTU providers error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
