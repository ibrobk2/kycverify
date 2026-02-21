<?php
// Turn off error reporting for production output, but log errors
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 0);

// Start output buffering to capture any spurious output
ob_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/admin-jwt-helper.php';

// Clean buffer before any output
ob_clean();

$adminData = AdminJWTHelper::getAdminData();
if (!$adminData) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // DataTables server-side parameters
    $draw = isset($_GET['draw']) ? intval($_GET['draw']) : 0;
    $start = isset($_GET['start']) ? intval($_GET['start']) : 0;
    $length = isset($_GET['length']) ? intval($_GET['length']) : 50;
    $searchValue = isset($_GET['search']) && is_array($_GET['search']) && isset($_GET['search']['value']) ? trim($_GET['search']['value']) : '';
    
    // Custom filters
    $type = isset($_GET['type']) ? $_GET['type'] : 'all'; // all, wallet, vtu, service, or specific service types
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
    $endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';
    
    $transactions = [];
    $totalRecords = 0;
    $filteredRecords = 0;
    
    // Service types that go to service_transactions
    $serviceTypes = ['nin_verification', 'nin_premium', 'nin_regular', 'nin_standard', 'nin_vnin',
                     'bvn_verification', 'bvn_slip', 'bvn_modification', 'ipe_clearance', 
                     'birth_attestation', 'bvn_upload'];
    
    // Determine which tables to query
    $queryService = ($type === 'all' || in_array($type, $serviceTypes) || $type === 'service');
    $queryWallet = ($type === 'all' || $type === 'wallet');
    $queryVtu = ($type === 'all' || $type === 'vtu');
    
    // If specific service type, only query service_transactions
    if (in_array($type, $serviceTypes)) {
        $queryWallet = false;
        $queryVtu = false;
    }
    
    // Build UNION query for all transaction types
    $unionParts = [];
    $unionParams = [];
    
    // Service transactions
    if ($queryService) {
        $serviceWhere = ['1=1'];
        if (in_array($type, $serviceTypes)) {
            $serviceWhere[] = "st.service_type = ?";
            $unionParams[] = $type;
        }
        if (!empty($status)) {
            $serviceWhere[] = "st.status = ?";
            $unionParams[] = strtolower($status);
        }
        if (!empty($startDate)) {
            $serviceWhere[] = "DATE(st.created_at) >= ?";
            $unionParams[] = $startDate;
        }
        if (!empty($endDate)) {
            $serviceWhere[] = "DATE(st.created_at) <= ?";
            $unionParams[] = $endDate;
        }
        if (!empty($searchValue)) {
            $serviceWhere[] = "(u.name LIKE ? OR u.email LIKE ? OR st.reference_number LIKE ? OR st.service_type LIKE ?)";
            $unionParams[] = "%$searchValue%";
            $unionParams[] = "%$searchValue%";
            $unionParams[] = "%$searchValue%";
            $unionParams[] = "%$searchValue%";
        }
        $serviceWhereStr = implode(' AND ', $serviceWhere);
        
        $unionParts[] = "SELECT st.id, 'service' as source_type, st.service_type, 
                         u.name as user_name, u.email as user_email,
                         st.reference_number, st.status, st.amount, st.provider,
                         st.admin_notes, st.created_at
                         FROM service_transactions st
                         LEFT JOIN users u ON st.user_id = u.id
                         WHERE $serviceWhereStr";
    }
    
    // Wallet transactions
    if ($queryWallet) {
        $walletWhere = ['1=1'];
        if (!empty($status)) {
            if (strtolower($status) === 'completed') {
                // wallet transactions are always completed
            } else {
                // skip wallet transactions for non-completed status
                $queryWallet = false;
            }
        }
        if ($queryWallet) {
            if (!empty($startDate)) {
                $walletWhere[] = "DATE(wt.created_at) >= ?";
                $unionParams[] = $startDate;
            }
            if (!empty($endDate)) {
                $walletWhere[] = "DATE(wt.created_at) <= ?";
                $unionParams[] = $endDate;
            }
            if (!empty($searchValue)) {
                $walletWhere[] = "(u.name LIKE ? OR u.email LIKE ? OR wt.description LIKE ? OR wt.reference LIKE ?)";
                $unionParams[] = "%$searchValue%";
                $unionParams[] = "%$searchValue%";
                $unionParams[] = "%$searchValue%";
                $unionParams[] = "%$searchValue%";
            }
            $walletWhereStr = implode(' AND ', $walletWhere);
            
            $unionParts[] = "SELECT wt.id, 'wallet' as source_type, 
                             CONCAT('wallet_', wt.transaction_type) as service_type,
                             u.name as user_name, u.email as user_email,
                             wt.reference as reference_number, 
                             'completed' as status,
                             wt.amount, 'wallet' as provider,
                             wt.description as admin_notes, wt.created_at
                             FROM wallet_transactions wt
                             LEFT JOIN users u ON wt.user_id = u.id
                             WHERE $walletWhereStr";
        }
    }
    
    // VTU transactions
    if ($queryVtu) {
        $tableCheck = $db->query("SHOW TABLES LIKE 'vtu_transactions'");
        if ($tableCheck->rowCount() > 0) {
            $vtuWhere = ['1=1'];
            if (!empty($status)) {
                $statusMap = ['completed' => 'SUCCESS', 'pending' => 'PENDING', 'failed' => 'FAILED', 'processing' => 'PROCESSING'];
                $mappedStatus = isset($statusMap[strtolower($status)]) ? $statusMap[strtolower($status)] : strtoupper($status);
                $vtuWhere[] = "vt.status = ?";
                $unionParams[] = $mappedStatus;
            }
            if (!empty($startDate)) {
                $vtuWhere[] = "DATE(vt.created_at) >= ?";
                $unionParams[] = $startDate;
            }
            if (!empty($endDate)) {
                $vtuWhere[] = "DATE(vt.created_at) <= ?";
                $unionParams[] = $endDate;
            }
            if (!empty($searchValue)) {
                $vtuWhere[] = "(u.name LIKE ? OR u.email LIKE ? OR vt.transaction_ref LIKE ? OR vt.phone_number LIKE ?)";
                $unionParams[] = "%$searchValue%";
                $unionParams[] = "%$searchValue%";
                $unionParams[] = "%$searchValue%";
                $unionParams[] = "%$searchValue%";
            }
            $vtuWhereStr = implode(' AND ', $vtuWhere);
            
            $unionParts[] = "SELECT vt.id, 'vtu' as source_type,
                             CONCAT('vtu_', LOWER(vt.transaction_type)) as service_type,
                             u.name as user_name, u.email as user_email,
                             vt.transaction_ref as reference_number,
                             LOWER(vt.status) as status,
                             vt.amount, 'vtu' as provider,
                             vt.status_message as admin_notes, vt.created_at
                             FROM vtu_transactions vt
                             LEFT JOIN users u ON vt.user_id = u.id
                             WHERE $vtuWhereStr";
        }
    }
    
    if (empty($unionParts)) {
        echo json_encode([
            'success' => true,
            'draw' => $draw,
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'data' => []
        ]);
        exit;
    }
    
    $unionQuery = implode(" UNION ALL ", $unionParts);
    
    // Count total
    $countQuery = "SELECT COUNT(*) FROM ($unionQuery) as combined";
    $stmt = $db->prepare($countQuery);
    $paramIndex = 1;
    foreach ($unionParams as $value) {
        $stmt->bindValue($paramIndex++, $value);
    }
    $stmt->execute();
    $filteredRecords = $stmt->fetchColumn();
    $totalRecords = $filteredRecords; // For simplicity
    
    // Get paginated results
    $finalQuery = "SELECT * FROM ($unionQuery) as combined ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $stmt = $db->prepare($finalQuery);
    $paramIndex = 1;
    foreach ($unionParams as $value) {
        $stmt->bindValue($paramIndex++, $value);
    }
    $stmt->bindValue($paramIndex++, $length, PDO::PARAM_INT);
    $stmt->bindValue($paramIndex++, $start, PDO::PARAM_INT);
    $stmt->execute();
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format status for VTU
    foreach ($transactions as &$tx) {
        if ($tx['status'] === 'success') $tx['status'] = 'completed';
    }
    
    // Support both DataTables format and legacy front-end format
    $totalPages = ($length > 0) ? ceil($filteredRecords / $length) : 1;
    $currentPage = ($length > 0) ? floor($start / $length) + 1 : 1;
    
    echo json_encode([
        'success' => true,
        'draw' => $draw,
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $filteredRecords,
        'data' => [
            'transactions' => $transactions,
            'pagination' => [
                'page' => $currentPage,
                'limit' => $length,
                'total' => $filteredRecords,
                'total_pages' => $totalPages
            ]
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Admin transactions error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'draw' => isset($draw) ? $draw : 0,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
