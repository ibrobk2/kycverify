<?php
require_once __DIR__ . '/api/KatPayService.php';
require_once __DIR__ . '/config/config.php';

echo "Testing KatPay with Public Key as Merchant ID...\n";

$service = new KatPayService();

$payload = [
    'email' => 'test@example.com',
    'name' => 'Test User',
    'phoneNumber' => '08012345678',
    'bankCode' => ['PALMPAY'],
    'merchantID' => 'KAT8882490666'
];

echo "Payload Merchant ID: " . $payload['merchantID'] . "\n";
$result = $service->makeRequest('/virtual-accounts', $payload, 'POST');

if ($result['success']) {
    echo "SUCCESS: Account Generated!\n";
    print_r($result['data']);
} else {
    echo "FAILED: " . $result['message'] . "\n";
    if (isset($result['details'])) {
        print_r($result['details']);
    }
}
?>
