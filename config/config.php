<?php
// Application Configuration

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'lil_done');
define('DB_USER', 'root');
define('DB_PASS', '');

// API Configuration
define('JWT_SECRET', 'your-secret-key-change-in-production');
define('JWT_ALGORITHM', 'HS256');

// Service Costs (in Naira)
define('NIN_VERIFICATION_COST', 50);
define('BVN_VERIFICATION_COST', 30);
define('BIRTH_ATTESTATION_COST', 100);
define('IPE_CLEARANCE_COST', 200);

// Payment Configuration
define('PAYSTACK_PUBLIC_KEY', 'pk_test_your_paystack_public_key');
define('PAYSTACK_SECRET_KEY', 'sk_test_your_paystack_secret_key');

// Application Settings
define('APP_NAME', 'Lildone Verification Service');
define('APP_URL', 'https://lildone.com');
define('SUPPORT_EMAIL', 'support@lildone.com');
define('SUPPORT_PHONE', '+234-800-123-4567');

// Security Settings
define('SESSION_TIMEOUT', 3600); // 1 hour
define('MAX_LOGIN_ATTEMPTS', 5);
define('RATE_LIMIT_REQUESTS', 100); // per hour
define('RATE_LIMIT_WINDOW', 3600); // 1 hour in seconds

// File Upload Settings
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('UPLOAD_PATH', '../uploads/');

// Email Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');

// Development Settings
define('DEBUG_MODE', true);
define('LOG_ERRORS', true);
define('ERROR_LOG_PATH', '../logs/error.log');

// API Endpoints
define('NIN_API_URL', 'https://api.example.com/nin/verify');
define('BVN_API_URL', 'https://api.example.com/bvn/verify');
define('NIN_API_KEY', 'your-nin-api-key');
define('BVN_API_KEY', 'your-bvn-api-key');

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
