<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/admin-jwt-helper.php';

// Verify Admin Token
$adminData = AdminJWTHelper::getAdminData();
if (!$adminData) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        // List all slider images
        $stmt = $db->query("SELECT * FROM slider_images ORDER BY id DESC");
        $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $images]);
    } 
    elseif ($method === 'POST') {
        // Add new slider image
        $input = json_decode(file_get_contents('php://input'), true);
        if (!isset($input['image_url'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Image URL is required']);
            exit;
        }

        $stmt = $db->prepare("INSERT INTO slider_images (image_url, caption, status) VALUES (?, ?, ?)");
        $stmt->execute([
            $input['image_url'],
            isset($input['caption']) ? $input['caption'] : null,
            isset($input['status']) ? $input['status'] : 'active'
        ]);

        echo json_encode(['success' => true, 'message' => 'Slider image added successfully']);
    } 
    elseif ($method === 'DELETE') {
        // Remove slider image
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID is required']);
            exit;
        }

        $stmt = $db->prepare("DELETE FROM slider_images WHERE id = ?");
        $stmt->execute([$id]);

        echo json_encode(['success' => true, 'message' => 'Slider image deleted successfully']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
