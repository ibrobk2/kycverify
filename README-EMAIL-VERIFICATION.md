# Email Verification with OTP

This document explains how to implement and use the email verification feature with OTP (One-Time Password) codes.

## Features Implemented

1. **Email Verification during Signup**: New users must verify their email address before they can log in.
2. **OTP Generation and Sending**: A 6-digit OTP code is generated and sent to the user's email during signup.
3. **OTP Verification**: Users must enter the OTP code to verify their email address.
4. **Resend OTP**: Users can request a new OTP code if they don't receive the original one.
5. **Login Protection**: Users cannot log in until their email is verified.

## Files Created/Modified

### Backend (PHP)
- `api/signup-with-otp.php` - Modified signup endpoint that generates and sends OTP
- `api/verify-otp.php` - Endpoint to verify OTP codes
- `api/resend-otp.php` - Endpoint to resend OTP codes
- `api/login.php` - Modified to require email verification
- `api/send-email.php` - Utility functions for sending emails
- `database/schema.sql` - Updated database schema with OTP verification table
- `database/migrate.php` - Migration script to create OTP table

### Frontend (JavaScript)
- `assets/js/script.js` - Updated with OTP verification functionality

### Database
- New `otp_verifications` table to store OTP codes

## How It Works

1. **User Signup**:
   - User fills out signup form with name, email, and password
   - Account is created with `email_verified = FALSE`
   - 6-digit OTP code is generated and sent to user's email
   - User ID is stored in localStorage for OTP verification

2. **OTP Verification**:
   - User receives OTP code via email
   - User enters OTP code in verification modal
   - OTP is verified against database record
   - If valid, user's `email_verified` is set to `TRUE` and OTP record is deleted

3. **Resend OTP**:
   - If user doesn't receive OTP, they can request a new one
   - Previous OTP is deleted and new OTP is generated and sent

4. **Login**:
   - User can only log in if their email is verified (`email_verified = TRUE`)

## Database Schema Changes

A new table `otp_verifications` is added:

```sql
CREATE TABLE IF NOT EXISTS otp_verifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    otp_code VARCHAR(6) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_otp_code (otp_code),
    INDEX idx_expires_at (expires_at)
);
```

## Running the Migration

To apply the database changes, run:

```bash
php database/migrate.php
```

## Testing the Feature

1. Go to the signup page and create a new account
2. Check your email for the OTP code
3. Enter the OTP code in the verification modal
4. Try to log in with your new account
5. Test the "Resend Code" functionality if needed

## Configuration

The email settings are configured in `config/config.php`:
- SMTP_HOST
- SMTP_PORT
- SMTP_USERNAME
- SMTP_PASSWORD
- APP_NAME
- SUPPORT_EMAIL

## Security Features

- OTP codes expire after 10 minutes
- Each OTP can only be used once
- Previous OTPs are deleted when new ones are generated
- Database transactions ensure data consistency
- Email verification is required for login
