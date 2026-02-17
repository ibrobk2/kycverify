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
define('KATPAY_API_KEY', 'pk_live_yLoloF9JV0C7AUozJzd4bJU4hU6AV0nMf0FanZlMJGg9BRZhKngiOhVm6rHvNSjZ'); // Replace with your KatPay public key
define('KATPAY_API_SECRET', 'eyJpdiI6InpRMDQzdGlQckt4Ukc2dG9UU2JKaGc9PSIsInZhbHVlIjoiQy81dkttWC9EV0o1Q3h0bVkwKy9ES2xiOFNDREhLYU5maGdRZkFRSkx5a1NKaGNtcG9qNDFVQnBsaXBQcnFkck5IZjI2QmZXU1I4ZG5tVzV0TnhQTnA3VkZRVjVGM1BKNldEcjVmWkRyYy9jODlGa1VQZXlJUkM3SWIvZjBaSjUiLCJtYWMiOiIxODBjZjI3YmUwNTA0YmZjZGE2YTA3YzY1MDIwNjQ2ODJhMjEzM2U2YmFhOTQ1ZGU4ZjFmNTNjZDVkZDliNGUxIiwidGFnIjoiIn0='); // Replace with your KatPay API secret
define('KATPAY_MERCHANT_ID', 'KAT8882490666'); // Replace with your KatPay merchant ID
define('KATPAY_WEBHOOK_SECRET', 'eyJpdiI6InpRMDQzdGlQckt4Ukc2dG9UU2JKaGc9PSIsInZhbHVlIjoiQy81dkttWC9EV0o1Q3h0bVkwKy9ES2xiOFNDREhLYU5maGdRZkFRSkx5a1NKaGNtcG9qNDFVQnBsaXBQcnFkck5IZjI2QmZXU1I4ZG5tVzV0TnhQTnA3VkZRVjVGM1BKNldEcjVmWkRyYy9jODlGa1VQZXlJUkM3SWIvZjBaSjUiLCJtYWMiOiIxODBjZjI3YmUwNTA0YmZjZGE2YTA3YzY1MDIwNjQ2ODJhMjEzM2U2YmFhOTQ1ZGU4ZjFmNTNjZDVkZDliNGUxIiwidGFnIjoiIn0='); // Replace with your KatPay webhook secret

// Payment Gateway Selection (katpay or paymentpoint)
define('PAYMENT_GATEWAY', 'katpay');

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
