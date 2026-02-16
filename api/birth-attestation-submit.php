<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../config/config.php';
require_once '../config/database.php';
require_once 'wallet-helper.php';


require_once 'jwt-helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Authenticate user
    $user_id = JWTHelper::getUserIdFromToken();
    if (!$user_id) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized: Invalid or expired token']);
        exit;
    }

    // Initialize wallet helper
    $walletHelper = new WalletHelper();

    // Check wallet balance and process payment
    $paymentResult = $walletHelper->processPayment($user_id, 'birth_attestation', 'Birth Attestation Service');
    if (!$paymentResult['success']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $paymentResult['message']]);
        exit;
    }


    // Parse JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
        exit;
    }

    // Sanitize and validate input data
    $data = [
        'user_id' => $user_id,
        'title' => $input['title'],
        'nin' => $input['nin'],
        'surname' => $input['surname'],
        'first_name' => $input['first_name'],
        'middle_name' => $input['middle_name'],
        'gender' => $input['gender'],
        'new_date_of_birth' => $input['new_date_of_birth'] ,
        'phone_number' => $input['phone_number'],
        'marital_status' => $input['marital_status'],
        'town_city_residence' => $input['town_city_residence'],
        'state_residence' => $input['state_residence'],
        'lga_residence' => $input['lga_residence'],
        'address_residence' => $input['address_residence'],
        'state_origin' => $input['state_origin'],
        'lga_origin' => $input['lga_origin'],
        'father_surname' => $input['father_surname'],
        'father_first_name' => $input['father_first_name'],
        'father_state' => $input['father_state'],
        'father_lga' => $input['father_lga'],
        'father_town' => $input['father_town'],
        'mother_surname' => $input['mother_surname'],
        'mother_first_name' => $input['mother_first_name'],
        'mother_maiden_name' => $input['mother_maiden_name'],
        'mother_state' => $input['mother_state'],
        'mother_lga' => $input['mother_lga'],
        'mother_town' => $input['mother_town'],
    ];

    // Basic validation
    foreach (['surname', 'first_name'] as $field) {
        if (empty($data[$field])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => ucfirst($field) . ' is required']);
            exit;
        }
    }

    // Generate unique reference code
    $reference_code = 'BA-' . date('Y') . '-' . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);

    $stmt = $db->prepare("SELECT id FROM birth_attestations WHERE reference_code = ?");
    $stmt->execute([$reference_code]);
    while ($stmt->rowCount() > 0) {
        $reference_code = 'BA-' . date('Y') . '-' . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
        $stmt->execute([$reference_code]);
    }

    // Add reference_code to data
    $data['reference_code'] = $reference_code;

    // Build SQL
    $columns = array_keys($data);
    $placeholders = rtrim(str_repeat('?,', count($columns)), ',');
    $sql = "INSERT INTO birth_attestations (" . implode(',', $columns) . ") VALUES ($placeholders)";
    $values = array_values($data);

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
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error',
        'error' => $e->getMessage()
    ]);
}
