<?php
require_once '../../config/database.php';
require_once '../../config/constants.php';

// Set headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Check authentication
$headers = getallheaders();
$auth_header = isset($headers['Authorization']) ? $headers['Authorization'] : '';
$token = str_replace('Bearer ', '', $auth_header);

if (empty($token)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authorization token required']);
    exit;
}

// Verify token (simplified - in production, use proper JWT verification)
if ($token !== 'your-admin-token') { // Replace with actual token verification
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid token']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $method = $_SERVER['REQUEST_METHOD'];
    $input = json_decode(file_get_contents('php://input'), true);

    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                // Get specific pricing
                $stmt = $pdo->prepare("SELECT * FROM pricing WHERE id = ?");
                $stmt->execute(array($_GET['id']));

                $pricing = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($pricing) {
                    echo json_encode(['success' => true, 'pricing' => $pricing]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Pricing not found']);
                }
            } else {
                // Get all pricing
                $stmt = $pdo->query("SELECT * FROM pricing ORDER BY id DESC");
                $pricing = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'pricing' => $pricing]);
            }
            break;

        case 'POST':
            // Add new pricing
            if (!isset($input['service_name']) || !isset($input['price'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Service name and price are required']);
                break;
            }

            $stmt = $pdo->prepare("INSERT INTO pricing (service_name, price, description, status) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $input['service_name'],
                $input['price'],
                isset($input['description']) ? $input['description'] : '',
                isset($input['status']) ? $input['status'] : 'active'
            ]);


            echo json_encode(['success' => true, 'message' => 'Pricing added successfully', 'id' => $pdo->lastInsertId()]);
            break;

        case 'PUT':
            // Update pricing
            if (!isset($input['id']) || !isset($input['service_name']) || !isset($input['price'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID, service name, and price are required']);
                break;
            }

            $stmt = $pdo->prepare("UPDATE pricing SET service_name = ?, price = ?, description = ?, status = ? WHERE id = ?");
            $stmt->execute([
                $input['service_name'],
                $input['price'],
                isset($input['description']) ? $input['description'] : '',
                isset($input['status']) ? $input['status'] : 'active',
                $input['id']
            ]);


            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Pricing updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Pricing not found or no changes made']);
            }
            break;

        case 'DELETE':
            // Delete pricing
            if (!isset($input['id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID is required']);
                break;
            }

            $stmt = $pdo->prepare("DELETE FROM pricing WHERE id = ?");
            $stmt->execute([$input['id']]);

            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Pricing deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Pricing not found']);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
