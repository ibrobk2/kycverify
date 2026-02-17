<?php
/**
 * Migration: Create service_transactions table
 * Unified table for all service transactions (NIN, BVN, IPE, Birth Attestation, etc.)
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Create service_transactions table
    $db->exec("
        CREATE TABLE IF NOT EXISTS service_transactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            service_type VARCHAR(50) NOT NULL COMMENT 'nin_verification, bvn_verification, bvn_slip, bvn_modification, ipe_clearance, birth_attestation, bvn_upload',
            reference_number VARCHAR(100) DEFAULT NULL,
            status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
            amount DECIMAL(10,2) DEFAULT 0.00,
            request_data TEXT DEFAULT NULL COMMENT 'Original request payload (JSON)',
            response_data TEXT DEFAULT NULL COMMENT 'API response or result data (JSON)',
            error_message TEXT DEFAULT NULL,
            provider VARCHAR(50) DEFAULT NULL,
            admin_notes TEXT DEFAULT NULL,
            updated_by INT DEFAULT NULL COMMENT 'Admin who last updated status',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id),
            INDEX idx_service_type (service_type),
            INDEX idx_status (status),
            INDEX idx_reference (reference_number),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    echo "✅ service_transactions table created successfully.\n";
    
    // Migrate existing verification_logs entries into service_transactions
    $tableCheck = $db->query("SHOW TABLES LIKE 'verification_logs'");
    if ($tableCheck->rowCount() > 0) {
        // Check if already migrated
        $count = $db->query("SELECT COUNT(*) FROM service_transactions")->fetchColumn();
        if ($count == 0) {
            $db->exec("
                INSERT INTO service_transactions (user_id, service_type, reference_number, status, request_data, response_data, error_message, provider, created_at)
                SELECT 
                    user_id,
                    service_type,
                    reference_number,
                    CASE 
                        WHEN status = 'success' THEN 'completed'
                        WHEN status = 'failed' THEN 'failed'
                        WHEN status = 'pending' THEN 'pending'
                        ELSE 'pending'
                    END,
                    NULL,
                    response_data,
                    error_message,
                    provider,
                    created_at
                FROM verification_logs
            ");
            echo "✅ Migrated existing verification_logs entries.\n";
        } else {
            echo "⏭️ service_transactions already has data, skipping migration.\n";
        }
    }
    
    // Migrate birth_attestations if table exists
    $tableCheck = $db->query("SHOW TABLES LIKE 'birth_attestations'");
    if ($tableCheck->rowCount() > 0) {
        $existing = $db->query("SELECT COUNT(*) FROM service_transactions WHERE service_type = 'birth_attestation'")->fetchColumn();
        if ($existing == 0) {
            // Check columns exist
            try {
                $db->exec("
                    INSERT INTO service_transactions (user_id, service_type, reference_number, status, created_at)
                    SELECT 
                        user_id,
                        'birth_attestation',
                        reference_code,
                        CASE 
                            WHEN status = 'approved' THEN 'completed'
                            WHEN status = 'rejected' THEN 'failed'
                            ELSE 'pending'
                        END,
                        submitted_at
                    FROM birth_attestations
                ");
                echo "✅ Migrated existing birth_attestations entries.\n";
            } catch (Exception $e) {
                echo "⚠️ Could not migrate birth_attestations: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n🎉 Migration completed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
}
?>
