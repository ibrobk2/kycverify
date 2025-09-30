<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get service name from query parameter
    $service_name = isset($_GET['service']) ? $_GET['service'] : '';


    if (empty($service_name)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Service name is required']);
        exit;
    }

    // Get service price from pricing table
    $stmt = $db->prepare("SELECT price FROM pricing WHERE service_name = ? AND status = 'active'");
    $stmt->execute([$service_name]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        echo json_encode([
            'success' => true,
            'price' => floatval($result['price'])
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Service not found or inactive'
        ]);
    }

} catch (PDOException $e) {
    error_log('Get service price error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error',
        'error' => $e->getMessage()
    ]);
}
?>
