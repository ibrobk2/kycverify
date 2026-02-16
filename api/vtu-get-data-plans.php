<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/services/VTUServiceFactory.php';

require_once __DIR__ . '/jwt-helper.php';

if (!JWTHelper::getUserIdFromToken()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get network parameter
$network = strtoupper(isset($_GET['network']) ? $_GET['network'] : '');

if (empty($network)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Network parameter is required']);
    exit;
}

$validNetworks = ['MTN', 'GLO', 'AIRTEL', '9MOBILE'];
if (!in_array($network, $validNetworks)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid network']);
    exit;
}

try {
    $db = (new Database())->getConnection();
    
    // Get active provider ID
    $stmt = $db->prepare("SELECT setting_value FROM vtu_api_settings WHERE setting_key = 'active_provider_id'");
    $stmt->execute();
    $providerId = $stmt->fetchColumn();
    
    if (!$providerId) {
        http_response_code(503);
        echo json_encode(['success' => false, 'message' => 'VTU service not configured']);
        exit;
    }
    
    // Check if we have cached plans
    $stmt = $db->prepare("
        SELECT * FROM data_plans 
        WHERE provider_id = ? AND network = ? AND is_active = 1 
        ORDER BY CAST(SUBSTRING_INDEX(data_amount, 'GB', 1) AS DECIMAL(10,2)) ASC
    ");
    $stmt->execute([$providerId, $network]);
    $cachedPlans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If we have cached plans less than 24 hours old, return them
    if (count($cachedPlans) > 0) {
        echo json_encode([
            'success' => true,
            'data' => [
                'network' => $network,
                'plans' => $cachedPlans
            ]
        ]);
        exit;
    }
    
    // Otherwise, fetch from API and cache
    $vtuService = VTUServiceFactory::getActiveProvider();
    
    if (!$vtuService) {
        http_response_code(503);
        echo json_encode(['success' => false, 'message' => 'VTU service provider not available']);
        exit;
    }
    
    $result = $vtuService->getDataPlans($network);
    
    if ($result['success'] && isset($result['data'])) {
        // Parse and cache plans (format depends on provider)
        // This is a generic implementation - adjust based on actual API response
        $plans = [];
        if (isset($result['data']['plans'])) {
            $plans = $result['data']['plans'];
        } elseif (isset($result['data']['content'])) {
            $plans = $result['data']['content'];
        } elseif (isset($result['data'])) {
            $plans = $result['data'];
        }
        
        if (is_array($plans)) {
            // Clear old plans for this network
            $stmt = $db->prepare("DELETE FROM data_plans WHERE provider_id = ? AND network = ?");
            $stmt->execute([$providerId, $network]);
            
            // Insert new plans
            $stmt = $db->prepare("
                INSERT INTO data_plans (
                    provider_id, network, plan_id, plan_name, plan_type, 
                    data_amount, validity, price, cost_price
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            foreach ($plans as $plan) {
                $planId = isset($plan['plan_id']) ? $plan['plan_id'] : (isset($plan['variation_code']) ? $plan['variation_code'] : (isset($plan['id']) ? $plan['id'] : ''));
                $planName = isset($plan['name']) ? $plan['name'] : (isset($plan['plan_name']) ? $plan['plan_name'] : '');
                $planType = isset($plan['type']) ? $plan['type'] : 'DATA';
                $dataAmount = isset($plan['data_amount']) ? $plan['data_amount'] : (isset($plan['size']) ? $plan['size'] : '');
                $validity = isset($plan['validity']) ? $plan['validity'] : (isset($plan['duration']) ? $plan['duration'] : '30 days');
                $price = isset($plan['price']) ? $plan['price'] : (isset($plan['variation_amount']) ? $plan['variation_amount'] : 0);
                $costPrice = isset($plan['cost_price']) ? $plan['cost_price'] : null;
                
                $stmt->execute([
                    $providerId,
                    $network,
                    $planId,
                    $planName,
                    $planType,
                    $dataAmount,
                    $validity,
                    $price,
                    $costPrice
                ]);
            }
            
            // Fetch the newly cached plans
            $stmt = $db->prepare("SELECT * FROM data_plans WHERE provider_id = ? AND network = ? AND is_active = 1");
            $stmt->execute([$providerId, $network]);
            $newPlans = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'network' => $network,
                    'plans' => $newPlans
                ]
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'No plans available for this network'
            ]);
        }
    } else {
        http_response_code(400);
        $message = isset($result['message']) ? $result['message'] : 'Failed to fetch data plans';
        echo json_encode([
            'success' => false,
            'message' => $message
        ]);
    }
    
} catch (Exception $e) {
    error_log("Get Data Plans Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
?>
