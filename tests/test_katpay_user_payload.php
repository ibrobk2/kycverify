<?php
/**
 * Test KatPay Webhook with User-provided sample payload
 */

// Mock Stream for php://input
class StreamMock {
    public $context;
    public static $content = '';
    
    public function stream_open($path, $mode, $options, &$opened_path) { return true; }
    public function stream_read($count) {
        $ret = substr(self::$content, 0, $count);
        self::$content = substr(self::$content, $count);
        return $ret;
    }
    public function stream_eof() { return strlen(self::$content) === 0; }
    public function stream_stat() { return []; }
}

if (in_array('php', stream_get_wrappers())) {
    stream_wrapper_unregister("php");
}
stream_wrapper_register("php", "StreamMock");

// 1. Setup Environment
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../api/KatPayService.php';

// Fetch the secret using reflection
$service = new KatPayService();
$reflection = new ReflectionClass($service);
$property = $reflection->getProperty('webhookSecret');
$property->setAccessible(true);
$secret = $property->getValue($service);

// 2. Sample Payload from User
$timestamp = 1678901234;
$payloadData = [
  "event_type" => "virtual_account.payment_received",
  "event_id" => "evt_1678901234_a1b2c3d4",
  "timestamp" => $timestamp,
  "data" => [
    "virtual_account" => [
      "account_number" => "6601021884",
      "account_name" => "Abubkar Ahmed",
      "bank_name" => "PalmPay",
      "provider_reference" => "ref_123456"
    ],
    "customer" => [
      "email" => "ibrobk12@gmail.com",
      "phone" => "08135363778",
      "name" => "Abubkar Ahmed"
    ],
    "transaction" => [
      "order_no" => "ORD-123456789",
      "order_status" => "SUCCESS",
      "order_amount" => 100.00,
      "order_amount_cents" => 500000,
      "currency" => "NGN",
      "created_time" => 1678901200,
      "update_time" => 1678901234,
      "reference" => "SYNHZD2GYFVPVP50IC0FXGDOQCOSZWKEDVD6X1UPWJFLYGXI95",
      "session_id" => "sess_123456"
    ],
    "payer" => [
      "account_number" => "0123456789",
      "account_name" => "Jane Doe",
      "bank_name" => "GTBank"
    ],
    "merchant" => [
      "merchant_id" => "MERCH-123456",
      "business_name" => "My Business"
    ]
  ]
];

$payload = json_encode($payloadData);
StreamMock::$content = $payload;

// 3. Sign Payload
$signedPayload = $timestamp . '.' . $payload;
$signature = hash_hmac('sha256', $signedPayload, $secret);

// 4. Set Server Variables
$_SERVER['HTTP_X_KATPAY_SIGNATURE'] = $signature;
$_SERVER['HTTP_X_KATPAY_TIMESTAMP'] = $timestamp;
$_SERVER['REQUEST_METHOD'] = 'POST';

echo "Testing Webhook with User-provided payload...\n";

// 5. Run Webhook
ob_start();
try {
    include __DIR__ . '/../api/webhook/katpay.php';
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
$output = ob_get_clean();

echo "Response Body: " . $output . "\n";
echo "Testing Complete.\n";
