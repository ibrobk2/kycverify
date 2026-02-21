header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/wallet-helper.php';
require_once __DIR__ . '/jwt-helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Initialize database
    $database = new Database();
    $db = $database->getConnection();

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

    // Sanitize and validate input data (with fallbacks for frontend mismatches)
    $data = [
        'user_id' => $user_id,
        'title' => isset($input['title']) ? $input['title'] : 'Mr/Mrs',
        'nin' => isset($input['nin']) ? $input['nin'] : '',
        'surname' => isset($input['surname']) ? $input['surname'] : (isset($input['full_name']) ? explode(' ', $input['full_name'])[0] : ''),
        'first_name' => isset($input['first_name']) ? $input['first_name'] : (isset($input['full_name']) && count(explode(' ', $input['full_name'])) > 1 ? explode(' ', $input['full_name'])[1] : ''),
        'middle_name' => isset($input['middle_name']) ? $input['middle_name'] : '',
        'gender' => isset($input['gender']) ? $input['gender'] : 'other',
        'new_date_of_birth' => isset($input['new_date_of_birth']) ? $input['new_date_of_birth'] : (isset($input['date_of_birth']) ? $input['date_of_birth'] : date('Y-m-d')),
        'phone_number' => isset($input['phone_number']) ? $input['phone_number'] : (isset($input['phone']) ? $input['phone'] : ''),
        'marital_status' => isset($input['marital_status']) ? $input['marital_status'] : 'single',
        'town_city_residence' => isset($input['town_city_residence']) ? $input['town_city_residence'] : (isset($input['place_of_birth']) ? $input['place_of_birth'] : ''),
        'state_residence' => isset($input['state_residence']) ? $input['state_residence'] : '',
        'lga_residence' => isset($input['lga_residence']) ? $input['lga_residence'] : '',
        'address_residence' => isset($input['address_residence']) ? $input['address_residence'] : '',
        'state_origin' => isset($input['state_origin']) ? $input['state_origin'] : '',
        'lga_origin' => isset($input['lga_origin']) ? $input['lga_origin'] : '',
        'father_surname' => isset($input['father_surname']) ? $input['father_surname'] : (isset($input['father_name']) ? explode(' ', $input['father_name'])[0] : ''),
        'father_first_name' => isset($input['father_first_name']) ? $input['father_first_name'] : (isset($input['father_name']) && count(explode(' ', $input['father_name'])) > 1 ? explode(' ', $input['father_name'])[1] : ''),
        'father_state' => isset($input['father_state']) ? $input['father_state'] : '',
        'father_lga' => isset($input['father_lga']) ? $input['father_lga'] : '',
        'father_town' => isset($input['father_town']) ? $input['father_town'] : '',
        'mother_surname' => isset($input['mother_surname']) ? $input['mother_surname'] : (isset($input['mother_name']) ? explode(' ', $input['mother_name'])[0] : ''),
        'mother_first_name' => isset($input['mother_first_name']) ? $input['mother_first_name'] : (isset($input['mother_name']) && count(explode(' ', $input['mother_name'])) > 1 ? explode(' ', $input['mother_name'])[1] : ''),
        'mother_maiden_name' => isset($input['mother_maiden_name']) ? $input['mother_maiden_name'] : '',
        'mother_state' => isset($input['mother_state']) ? $input['mother_state'] : '',
        'mother_lga' => isset($input['mother_lga']) ? $input['mother_lga'] : '',
        'mother_town' => isset($input['mother_town']) ? $input['mother_town'] : '',
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

    // Log to service_transactions for admin tracking
    $stmtSt = $db->prepare("
        INSERT INTO service_transactions (user_id, service_type, reference_number, status, amount, request_data, provider)
        VALUES (?, 'birth_attestation', ?, 'pending', ?, ?, 'internal')
    ");
    $stmtSt->execute([
        $user_id,
        $reference_code,
        $paymentResult['amount_deducted'],
        json_encode($input)
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Birth attestation application submitted successfully',
        'reference_code' => $reference_code
    ]);

} catch (PDOException $e) {
    if (isset($paymentResult) && $paymentResult['success']) {
        $walletHelper->addAmount($user_id, $paymentResult['amount_deducted'], "Refund for failed Birth Attestation (DB Error)", "REF-" . uniqid());
    }
    error_log('Birth attestation submission error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred. Amount refunded.']);
} catch (Throwable $e) {
    if (isset($paymentResult) && $paymentResult['success']) {
        $walletHelper->addAmount($user_id, $paymentResult['amount_deducted'], "Refund for failed Birth Attestation (Server Error)", "REF-" . uniqid());
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error. Amount refunded.',
        'error' => $e->getMessage()
    ]);
}
