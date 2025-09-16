<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/config.php';
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get user ID from token
    $headers = getallheaders();
    if (!isset($headers['Authorization'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Authorization header missing']);
        exit;
    }

    $authHeader = $headers['Authorization'];
    if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid authorization header']);
        exit;
    }

    $token = $matches[1];

    // Decode token
    function jwt_decode($token) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }
        $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);
        return $payload ? (object) $payload : false;
    }

    $decoded = jwt_decode($token);
    if (!$decoded) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid token']);
        exit;
    }

    $user_id = $decoded->user_id;


    // Sanitize and validate input data
    $data = [
        'user_id' => $user_id,
        'title' => isset($_POST['title']) ? trim($_POST['title']) : '',
        'nin' => isset($_POST['nin']) ? trim($_POST['nin']) : '',
        'surname' => isset($_POST['surname']) ? trim($_POST['surname']) : '',
        'first_name' => isset($_POST['first_name']) ? trim($_POST['first_name']) : '',
        'middle_name' => isset($_POST['middle_name']) ? trim($_POST['middle_name']) : '',
        'gender' => isset($_POST['gender']) ? trim($_POST['gender']) : '',
        'new_date_of_birth' => isset($_POST['new_date_of_birth']) ? trim($_POST['new_date_of_birth']) : '',
        'phone_number' => isset($_POST['phone_number']) ? trim($_POST['phone_number']) : '',
        'marital_status' => isset($_POST['marital_status']) ? trim($_POST['marital_status']) : '',
        'town_city_residence' => isset($_POST['town_city_residence']) ? trim($_POST['town_city_residence']) : '',
        'state_residence' => isset($_POST['state_residence']) ? trim($_POST['state_residence']) : '',
        'lga_residence' => isset($_POST['lga_residence']) ? trim($_POST['lga_residence']) : '',
        'address_residence' => isset($_POST['address_residence']) ? trim($_POST['address_residence']) : '',
        'state_origin' => isset($_POST['state_origin']) ? trim($_POST['state_origin']) : '',
        'lga_origin' => isset($_POST['lga_origin']) ? trim($_POST['lga_origin']) : '',
        'father_surname' => isset($_POST['father_surname']) ? trim($_POST['father_surname']) : '',
        'father_first_name' => isset($_POST['father_first_name']) ? trim($_POST['father_first_name']) : '',
        'father_state' => isset($_POST['father_state']) ? trim($_POST['father_state']) : '',
        'father_lga' => isset($_POST['father_lga']) ? trim($_POST['father_lga']) : '',
        'father_town' => isset($_POST['father_town']) ? trim($_POST['father_town']) : '',
        'mother_surname' => isset($_POST['mother_surname']) ? trim($_POST['mother_surname']) : '',
        'mother_first_name' => isset($_POST['mother_first_name']) ? trim($_POST['mother_first_name']) : '',
        'mother_maiden_name' => isset($_POST['mother_maiden_name']) ? trim($_POST['mother_maiden_name']) : '',
        'mother_state' => isset($_POST['mother_state']) ? trim($_POST['mother_state']) : '',
        'mother_lga' => isset($_POST['mother_lga']) ? trim($_POST['mother_lga']) : '',
        'mother_town' => isset($_POST['mother_town']) ? trim($_POST['mother_town']) : '',
    ];

    // Basic validation
    $required_fields = ['surname', 'first_name'];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => ucfirst($field) . ' is required']);
            exit;
        }
    }

    // Generate unique reference code
    $reference_code = 'BA-' . date('Y') . '-' . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);

    // Check if reference code already exists (unlikely but safe)
    $stmt = $db->prepare("SELECT id FROM birth_attestations WHERE reference_code = ?");
    $stmt->execute([$reference_code]);
    while ($stmt->rowCount() > 0) {
        $reference_code = 'BA-' . date('Y') . '-' . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
        $stmt->execute([$reference_code]);
    }

    // Insert data into database
    $columns = array_keys($data);
    $placeholders = str_repeat('?,', count($columns) - 1) . '?';
    $columns[] = 'reference_code';
    $placeholders .= ',?';

    $sql = "INSERT INTO birth_attestations (" . implode(',', $columns) . ") VALUES ($placeholders)";
    $values = array_values($data);
    $values[] = $reference_code;

    $stmt = $db->prepare($sql);
    $stmt->execute($values);

    echo json_encode([
        'success' => true,
        'message' => 'Birth attestation application submitted successfully',
        'reference_code' => $reference_code
    ]);

} catch (PDOException $e) {
    error_log('Birth attestation submission error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log('Birth attestation submission error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred while processing your request']);
}
?>
