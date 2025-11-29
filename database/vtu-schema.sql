-- VTU (Virtual Top-Up) Database Schema
-- MySQL Database Schema for VTU Services (Airtime and Data)

USE lil_done;

-- Table to store VTU API provider configurations
CREATE TABLE IF NOT EXISTS vtu_providers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provider_name VARCHAR(100) NOT NULL UNIQUE,
    provider_code VARCHAR(50) NOT NULL UNIQUE COMMENT 'Code identifier (ufardata, vtpass, etc.)',
    base_url VARCHAR(255) NOT NULL,
    api_key VARCHAR(255) DEFAULT NULL,
    api_secret VARCHAR(255) DEFAULT NULL,
    public_key VARCHAR(255) DEFAULT NULL COMMENT 'For providers that use public/private key',
    additional_config TEXT DEFAULT NULL COMMENT 'JSON for provider-specific configs',
    is_active BOOLEAN DEFAULT FALSE,
    is_enabled BOOLEAN DEFAULT TRUE,
    balance DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Provider balance if available',
    last_balance_check DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_provider_code (provider_code),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for global VTU settings
CREATE TABLE IF NOT EXISTS vtu_api_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    description VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default settings
INSERT INTO vtu_api_settings (setting_key, setting_value, description) VALUES
('active_provider_id', NULL, 'ID of the currently active VTU provider'),
('airtime_commission_percent', '2.0', 'Commission percentage on airtime purchases'),
('data_commission_percent', '3.0', 'Commission percentage on data purchases'),
('min_airtime_amount', '50', 'Minimum airtime purchase amount in Naira'),
('max_airtime_amount', '50000', 'Maximum airtime purchase amount in Naira'),
('vtu_enabled', '1', 'Enable/disable VTU services globally')
ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value);

-- Table to cache data plans from providers
CREATE TABLE IF NOT EXISTS data_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provider_id INT NOT NULL,
    network VARCHAR(50) NOT NULL COMMENT 'MTN, GLO, AIRTEL, 9MOBILE',
    plan_id VARCHAR(100) NOT NULL COMMENT 'Provider-specific plan ID',
    plan_name VARCHAR(255) NOT NULL,
    plan_type VARCHAR(50) DEFAULT 'DATA' COMMENT 'DATA, SME, GIFTING, etc.',
    data_amount VARCHAR(50) NOT NULL COMMENT 'e.g., 1GB, 2GB, 5GB',
    validity VARCHAR(50) NOT NULL COMMENT 'e.g., 30 days, 7 days',
    price DECIMAL(10,2) NOT NULL,
    cost_price DECIMAL(10,2) DEFAULT NULL COMMENT 'Provider cost price',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (provider_id) REFERENCES vtu_providers(id) ON DELETE CASCADE,
    INDEX idx_network (network),
    INDEX idx_provider_network (provider_id, network),
    INDEX idx_is_active (is_active),
    UNIQUE KEY unique_provider_plan (provider_id, network, plan_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table to log all VTU transactions
CREATE TABLE IF NOT EXISTS vtu_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    provider_id INT NOT NULL,
    transaction_ref VARCHAR(100) NOT NULL UNIQUE COMMENT 'Internal reference',
    provider_ref VARCHAR(100) DEFAULT NULL COMMENT 'Provider transaction reference',
    transaction_type ENUM('AIRTIME', 'DATA') NOT NULL,
    network VARCHAR(50) NOT NULL COMMENT 'MTN, GLO, AIRTEL, 9MOBILE',
    phone_number VARCHAR(20) NOT NULL,
    amount DECIMAL(10,2) NOT NULL COMMENT 'Amount paid by user',
    cost_price DECIMAL(10,2) DEFAULT NULL COMMENT 'Cost from provider',
    commission DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Commission earned',
    
    -- For data purchases
    plan_id VARCHAR(100) DEFAULT NULL,
    plan_name VARCHAR(255) DEFAULT NULL,
    data_amount VARCHAR(50) DEFAULT NULL,
    
    status ENUM('PENDING', 'PROCESSING', 'SUCCESS', 'FAILED', 'REFUNDED') DEFAULT 'PENDING',
    status_message TEXT DEFAULT NULL,
    
    -- Wallet tracking
    wallet_balance_before DECIMAL(15,2) DEFAULT NULL,
    wallet_balance_after DECIMAL(15,2) DEFAULT NULL,
    
    -- API response
    api_request TEXT DEFAULT NULL COMMENT 'JSON request sent to provider',
    api_response TEXT DEFAULT NULL COMMENT 'JSON response from provider',
    
    -- Timestamps
    initiated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (provider_id) REFERENCES vtu_providers(id),
    INDEX idx_user_id (user_id),
    INDEX idx_transaction_ref (transaction_ref),
    INDEX idx_provider_ref (provider_ref),
    INDEX idx_status (status),
    INDEX idx_transaction_type (transaction_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add VTU service pricing to pricing table (if pricing table exists)
-- This assumes you have a pricing table from previous implementations
INSERT IGNORE INTO pricing (service_name, service_code, price, description, is_active) VALUES
('Airtime Purchase', 'VTU_AIRTIME', 0.00, 'Buy airtime for any Nigerian network (price varies)', 1),
('Data Purchase', 'VTU_DATA', 0.00, 'Buy data bundles for any Nigerian network (price varies)', 1);
