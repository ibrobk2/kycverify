<?php
require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    $sql = "
    -- Verifications table for tracking user verification processes
    CREATE TABLE IF NOT EXISTS verifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        verification_type ENUM('email', 'phone', 'document', 'bvn', 'nin', 'address', 'biometric') NOT NULL,
        verification_status ENUM('pending', 'processing', 'verified', 'rejected', 'expired') DEFAULT 'pending',
        
        -- Verification data storage
        verification_data JSON,
        reference_code VARCHAR(50) UNIQUE NOT NULL,
        
        -- Document verification fields
        document_type VARCHAR(50),
        document_number VARCHAR(100),
        document_expiry DATE,
        document_front_path VARCHAR(255),
        document_back_path VARCHAR(255),
        
        -- BVN/NIN verification fields
        bvn_number VARCHAR(11),
        nin_number VARCHAR(11),
        
        -- Contact verification fields
        email_address VARCHAR(255),
        phone_number VARCHAR(20),
        
        -- Verification metadata
        verification_method VARCHAR(50),
        verification_provider VARCHAR(100),
        confidence_score DECIMAL(3,2),
        
        -- Timestamps and tracking
        initiated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        completed_at TIMESTAMP NULL,
        expires_at TIMESTAMP NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        -- Admin tracking
        verified_by INT NULL,
        rejection_reason TEXT,
        
        -- Indexes and constraints
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (verified_by) REFERENCES admins(id) ON DELETE SET NULL,
        INDEX idx_user_id (user_id),
        INDEX idx_verification_type (verification_type),
        INDEX idx_verification_status (verification_status),
        INDEX idx_reference_code (reference_code),
        INDEX idx_created_at (initiated_at),
        INDEX idx_expires_at (expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    
    -- Verification logs table for audit trail
    CREATE TABLE IF NOT EXISTS verification_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        verification_id INT NOT NULL,
        action VARCHAR(50) NOT NULL,
        action_by INT NULL,
        action_details JSON,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        
        FOREIGN KEY (verification_id) REFERENCES verifications(id) ON DELETE CASCADE,
        FOREIGN KEY (action_by) REFERENCES admins(id) ON DELETE SET NULL,
        INDEX idx_verification_id (verification_id),
        INDEX idx_action (action),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $db->exec($sql);
    echo "Verifications tables created successfully.\n";
} catch (PDOException $e) {
    echo "Error creating verifications tables: " . $e->getMessage() . "\n";
}
?>
