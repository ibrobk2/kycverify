<?php
require_once 'send-email.php';

// Test email functionality
$testEmail = "test@example.com";
$testOTP = generateOTP();

echo "Testing PHPMailer implementation...\n";
echo "Sending OTP: " . $testOTP . " to " . $testEmail . "\n";

$result = sendOTP($testEmail, $testOTP);

if ($result) {
    echo "Email sent successfully!\n";
} else {
    echo "Failed to send email.\n";
    echo "Check error logs for more details.\n";
}
?>
