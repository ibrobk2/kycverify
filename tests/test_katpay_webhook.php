<?php
// Mock file_get_contents('php://input')
class StreamMock {
    public $context;
    public static $content = '';
    
    public function stream_open($path, $mode, $options, &$opened_path) {
        return true;
    }
    
    public function stream_read($count) {
        $ret = substr(self::$content, 0, $count);
        self::$content = substr(self::$content, $count);
        return $ret;
    }
    
    public function stream_eof() {
        return strlen(self::$content) === 0;
    }
    
    public function stream_stat() {
        return [];
    }
}
stream_wrapper_unregister("php");
stream_wrapper_register("php", "StreamMock");

// Define constants if not already defined (to avoid warnings when including config)
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');

// Load config to access DB
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../api/KatPayService.php';

// Fetch the secret that the service would use
$service = new KatPayService();
// Check if we can access the secret via reflection since it's private
$reflection = new ReflectionClass($service);
$property = $reflection->getProperty('webhookSecret');
$property->setAccessible(true);
$secret = $property->getValue($service);

echo "Using Secret: " . substr($secret, 0, 10) . "...\n";

// Prepare Payload
$timestamp = time();
$payloadData = [
    "event_type" => "virtual_account.payment_received",
    "event_id" => "evt_" . time(),
    "timestamp" => $timestamp,
    "data" => [
        "transaction" => [
            "order_no" => "ORD-" . time(),
            "order_amount" => 100.00,
            "reference" => "REF-" . time(),
            "status" => "SUCCESS"
        ],
        "virtual_account" => [
            // We need a valid account number that exists in DB or create one
            // For this test, we might fail on 'User not found' which is fine to verify signature logic
            "account_number" => "1234567890" 
        ],
        "customer" => [
            "email" => "test@example.com"
        ]
    ]
];

$payload = json_encode($payloadData);
StreamMock::$content = $payload;

// Calculate Signature
$signedPayload = $timestamp . '.' . $payload;
$signature = hash_hmac('sha256', $signedPayload, $secret);

// Set Headers
$_SERVER['HTTP_X_KATPAY_SIGNATURE'] = $signature;
$_SERVER['HTTP_X_KATPAY_TIMESTAMP'] = $timestamp;
$_SERVER['REQUEST_METHOD'] = 'POST';

echo "Testing Webhook...\n";

// Capture Output
ob_start();
try {
    include __DIR__ . '/../api/webhook/katpay.php';
} catch (Error $e) {
    echo "Error: " . $e->getMessage();
}
$output = ob_get_clean();

echo "Output: " . $output . "\n";
echo "Done.\n";
?>
