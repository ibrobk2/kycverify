<?php
// Admin users management endpoint
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Include necessary files
require_once '../../config/database.php';
require_once '../../config/constants.php';

// Prevent any HTML output
ob_start();


// Helper function to get bearer token
function getBearerToken() {
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        $matches = [];
        if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
            return $matches[1];
        }
    }
    return null;
}

// Helper function to authenticate admin
function authenticateAdmin() {
    $token = getBearerToken();
    if (!$token) {
        return ['success' => false, 'message' => 'No token provided'];
    }

    define('ADMIN_TOKEN_SECRET', 'your-very-secret-key');
    
    $parts = explode('.', $token);
    if (count($parts) !== 2) {
        return ['success' => false, 'message' => 'Invalid token format'];
    }

    list($payload_b64, $signature) = $parts;
    $expected_signature = hash_hmac('sha256', $payload_b64, ADMIN_TOKEN_SECRET);

    if (!hash_equals($expected_signature, $signature)) {
        return ['success' => false, 'message' => 'Invalid token signature'];
    }

    $payload_json = base64_decode($payload_b64);
    $payload = json_decode($payload_json, true);

    if (!$payload || !isset($payload['exp']) || $payload['exp'] < time()) {
        return ['success' => false, 'message' => 'Token expired or invalid'];
    }

    return ['success' => true, 'admin_id' => $payload['admin_id']];
}

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

try {
    // Authenticate for all methods except GET (GET is handled below)
    if ($method !== 'GET') {
        $auth = authenticateAdmin();
        if (!$auth['success']) {
            http_response_code(401);
            echo json_encode($auth);
            exit;
        }
    }

    // Initialize database connection
    $database = new Database();
    $db = $database->getConnection();


    switch ($method) {
        case 'GET':
            // Check authentication for GET as well
            $auth = authenticateAdmin();
            if (!$auth['success']) {
                http_response_code(401);
                echo json_encode($auth);
                exit;
            }

            // Get search parameter if provided
            $search = isset($_GET['search']) ? $_GET['search'] : '';
            
            // Build base query
            $query = "SELECT 
                        id,
                        name,
                        email,
                        password,
                        phone,
                        wallet,
                        status,
                        created_at,
                        last_login
                      FROM users";

            
            // Add search condition if search parameter is provided
            if (!empty($search)) {
                $query .= " WHERE name LIKE :search OR email LIKE :search OR phone LIKE :search OR wallet LIKE :search";
            }

            
            $query .= " ORDER BY created_at DESC";


            $stmt = $db->prepare($query);
            if (!empty($search)) {
                $searchParam = '%' . $search . '%';
                $stmt->bindParam(':search', $searchParam);
            }
            $stmt->execute();

            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format the response
            $response = [
                'success' => true,
                'users' => array_map(function($user) {
                    return [
                        'id' => (int)$user['id'],
                        'name' => $user['name'],
                        'email' => $user['email'],
                        'password' => '••••••••', // Hide password in response
                        'phone' => $user['phone'],
                        'wallet' => $user['wallet'],
                        'status' => $user['status'],
                        'created_at' => $user['created_at'],
                        'last_login' => $user['last_login'],
                        'total_verifications' => 0,
                        'pending_verifications' => 0,
                        'completed_verifications' => 0
                    ];
                }, $users)
            ];



            // Ensure clean JSON output
            echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

            break;

        case 'POST':
            // Create new user
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['full_name']) || !isset($input['email'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Full name and email are required']);
                exit;
            }

            // Check if email already exists
            $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
            $stmt->execute(['email' => $input['email']]);
            if ($stmt->fetch()) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Email already exists']);
                exit;
            }

            $stmt = $db->prepare("INSERT INTO users (name, email, phone, status, created_at) VALUES (:full_name, :email, :phone, :status, NOW())");
            $stmt->execute([
                'name' => $input['full_name'],
                'email' => $input['email'],
                'phone' => isset($input['phone']) ? $input['phone'] : '',
                'status' => isset($input['status']) ? $input['status'] : 'active'
            ]);

            echo json_encode(['success' => true, 'message' => 'User created successfully']);
            break;

        case 'PUT':
            // Update user
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['id']) || !isset($input['full_name']) || !isset($input['email'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'User ID, full name and email are required']);
                exit;
            }

            $stmt = $db->prepare("UPDATE users SET name = :full_name, email = :email, phone = :phone, status = :status WHERE id = :id");
            $stmt->execute([
                'id' => $input['id'],
                'name' => $input['full_name'],
                'email' => $input['email'],
                'phone' => isset($input['phone']) ? $input['phone'] : '',
                'status' => isset($input['status']) ? $input['status'] : 'active'
            ]);

            echo json_encode(['success' => true, 'message' => 'User updated successfully']);
            break;

        case 'DELETE':
            // Delete user
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'User ID is required']);
                exit;
            }

            $stmt = $db->prepare("DELETE FROM users WHERE id = :id");
            $stmt->execute(['id' => $input['id']]);

            echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred',
        'error' => $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred',
        'error' => $e->getMessage()
    ]);
}

// Clean output buffer
ob_end_flush();
?>
