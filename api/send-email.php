<?php
require_once '../config/config.php';
require_once '../phpmailer/PHPMailer.php';
require_once '../phpmailer/SMTP.php';
require_once '../phpmailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function sendEmail($to, $subject, $message) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Use PHPMailer::ENCRYPTION_SMTPS for SSL
        $mail->SMTPDebug  = DEBUG_MODE ? SMTP::DEBUG_SERVER : SMTP::DEBUG_OFF;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';
        
        // Recipients
        $mail->setFrom(SMTP_USERNAME, APP_NAME);
        $mail->addAddress($to);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;
        
        // Send email
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Failed to send email to: " . $to . ". Error: " . $mail->ErrorInfo);
        return false;
    }
}

function generateOTP() {
    return rand(100000, 999999);
}

function sendOTP($email, $otp) {
    $subject = "Email Verification - " . APP_NAME;
    $message = "
    <html>
    <head>
        <title>Email Verification</title>
    </head>
    <body>
        <h2>Email Verification</h2>
        <p>Thank you for signing up with " . APP_NAME . ".</p>
        <p>Your OTP code for email verification is: <strong>" . $otp . "</strong></p>
        <p>This code will expire in 10 minutes.</p>
        <p>If you didn't request this, please ignore this email.</p>
        <br>
        <p>Best regards,<br>" . APP_NAME . " Team</p>
    </body>
    </html>
    ";
    
    return sendEmail($email, $subject, $message);
}
?>
