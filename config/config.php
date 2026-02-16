<?php
// Application Configuration

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'lil_done');
define('DB_USER', 'root');
define('DB_PASS', '');

// API Configuration
define('JWT_SECRET', 'your-secret-key-change-in-production-85fa8961'); // Changed for security
define('JWT_ALGORITHM', 'HS256');
define('ADMIN_TOKEN_SECRET', 'admin-secret-key-change-in-production-92bc');

// Robosttech API Configuration
define('ROBOSTTECH_BASE_URL', 'https://api.robosttech.com/v1'); // Verify actual URL from docs
define('ROBOSTTECH_API_KEY', 'your-robosttech-api-key'); // Placeholder

// DataVerify API Configuration (Defaults/Fallbacks - Admin settings take precedence)
define('DATAVERIFY_BASE_URL', 'https://api.dataverify.com.ng/v1'); 
define('DATAVERIFY_API_KEY', 'dataverifyf8bda8e3e2754622348cf7eb8d5ac632'); 

// Verification Provider (robosttech or dataverify)
// This can also be moved to database settings in future, but for now it's a code switch.
define('VERIFICATION_PROVIDER', 'dataverify');

// Gafiapay API Configuration
define('GAFIAPAY_BASE_URL', 'https://api.gafiapay.com/v1'); // Verify actual URL
define('GAFIAPAY_API_KEY', 'your-gafiapay-api-key'); // Placeholder
define('GAFIAPAY_SECRET', 'your-gafiapay-secret'); // Placeholder

// PaymentPoint API Configuration (Legacy - kept for reference)
define('PAYMENTPOINT_BASE_URL', 'https://api.paymentpoint.co/v1'); // Verify actual URL
define('PAYMENTPOINT_API_KEY', 'your-paymentpoint-api-key'); // Placeholder
define('PAYMENTPOINT_SECRET', 'your-paymentpoint-secret'); // Placeholder

// KatPay API Configuration (Active Payment Gateway)
define('KATPAY_BASE_URL', 'https://api.katpay.co/v1');
define('KATPAY_API_KEY', 'your-katpay-public-key'); // Replace with your KatPay public key
define('KATPAY_API_SECRET', 'your-katpay-api-secret'); // Replace with your KatPay API secret
define('KATPAY_MERCHANT_ID', 'your-katpay-merchant-id'); // Replace with your KatPay merchant ID
define('KATPAY_WEBHOOK_SECRET', 'your-katpay-webhook-secret'); // Replace with your KatPay webhook secret

// Payment Gateway Selection (katpay or paymentpoint)
define('PAYMENT_GATEWAY', 'katpay');

// Service Costs (Fallback/Initial)
define('NIN_VERIFICATION_COST', 50);
define('BVN_VERIFICATION_COST', 30);
define('BIRTH_ATTESTATION_COST', 100);
define('IPE_CLEARANCE_COST', 200);

// Payment Configuration
define('PAYSTACK_PUBLIC_KEY', 'pk_test_your_paystack_public_key');
define('PAYSTACK_SECRET_KEY', 'sk_test_your_paystack_secret_key');

// Application Settings
define('APP_NAME', 'Agentify Verification Service');
define('APP_URL', 'https://agentify.com.ng');
define('SUPPORT_EMAIL', 'support@agentify.com.ng');
define('SUPPORT_PHONE', '+234-800-123-4567');

// VTU Service Settings
define('VTU_ENABLED', true);
define('VTU_MIN_AIRTIME_AMOUNT', 50);
define('VTU_MAX_AIRTIME_AMOUNT', 50000);
define('VTU_AIRTIME_COMMISSION_PERCENT', 2.0);
define('VTU_DATA_COMMISSION_PERCENT', 3.0);

// Security Settings
define('SESSION_TIMEOUT', 3600); // 1 hour
define('MAX_LOGIN_ATTEMPTS', 5);
define('RATE_LIMIT_REQUESTS', 100); // per hour
define('RATE_LIMIT_WINDOW', 3600); // 1 hour in seconds

// File Upload Settings
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('UPLOAD_PATH', '../uploads/');

// Email Configuration
define('SMTP_HOST', 'mail.agentify.com.ng');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'support@agentify.com.ng');
define('SMTP_PASSWORD', 'Agentify@Support');

// Development Settings
define('DEBUG_MODE', true);
define('LOG_ERRORS', true);
define('ERROR_LOG_PATH', '../logs/error.log');

// Time Zone
date_default_timezone_set('Africa/Lagos');

// Error Reporting
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', ERROR_LOG_PATH);
}
?>
