-- Lildone Concepts Database Schema
-- MySQL Database Schema for NIN Verification System

-- Create database
CREATE DATABASE IF NOT EXISTS lil_done CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE lil_done;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NULL,
    company VARCHAR(100) NULL,
    wallet DECIMAL(10,2) DEFAULT 0.00,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    email_verified BOOLEAN DEFAULT FALSE,
    api_key VARCHAR(64) UNIQUE NULL,
    api_calls_limit INT DEFAULT 1000,
    api_calls_used INT DEFAULT 0,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_api_key (api_key),
    INDEX idx_status (status)
);

-- Verification logs table
CREATE TABLE IF NOT EXISTS verification_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    nin VARCHAR(11) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    status ENUM('pending', 'success', 'failed', 'timeout') DEFAULT 'pending',
    response_data JSON NULL,
    error_message TEXT NULL,
    processing_time DECIMAL(10,3) NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_nin (nin),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);

-- Verification results table
CREATE TABLE IF NOT EXISTS verification_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    nin VARCHAR(11) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    name VARCHAR(100) NOT NULL,
    date_of_birth DATE NULL,
    gender ENUM('Male', 'Female') NULL,
    address TEXT NULL,
    status ENUM('verified', 'unverified', 'partial') DEFAULT 'verified',
    confidence_score DECIMAL(3,2) DEFAULT 1.00,
    verified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_nin (nin),
    INDEX idx_verified_at (verified_at)
);

-- API usage logs table
CREATE TABLE IF NOT EXISTS api_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    endpoint VARCHAR(100) NOT NULL,
    method VARCHAR(10) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT NULL,
    request_data JSON NULL,
    response_status INT NOT NULL,
    response_time DECIMAL(10,3) NULL,
    error_message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_endpoint (endpoint),
    INDEX idx_created_at (created_at),
    INDEX idx_response_status (response_status)
);

-- System settings table
CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT NOT NULL,
    description TEXT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key)
);

-- Insert default system settings
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('api_rate_limit', '100', 'API calls per minute per user'),
('verification_timeout', '30', 'Verification timeout in seconds'),
('max_daily_verifications', '500', 'Maximum verifications per user per day'),
('maintenance_mode', 'false', 'System maintenance mode'),
('email_notifications', 'true', 'Enable email notifications')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

-- OTP verification table
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

-- Create indexes for better performance
CREATE INDEX idx_users_created_at ON users(created_at);
CREATE INDEX idx_verification_logs_nin_phone ON verification_logs(nin, phone);
CREATE INDEX idx_api_logs_user_endpoint ON api_logs(user_id, endpoint);

-- BVN Modification Uploads table
CREATE TABLE IF NOT EXISTS bvn_modification_uploads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    reference VARCHAR(50) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_reference (reference),
    INDEX idx_status (status)
);
