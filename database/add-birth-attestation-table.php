<?php
require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    $sql = "
    -- Birth Attestation table for storing birth attestation applications
    CREATE TABLE IF NOT EXISTS birth_attestations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,

        -- Personal Details
        title VARCHAR(10),
        nin VARCHAR(11),
        surname VARCHAR(100) NOT NULL,
        first_name VARCHAR(100) NOT NULL,
        middle_name VARCHAR(100),
        gender ENUM('Male', 'Female', 'Other'),
        new_date_of_birth DATE,
        phone_number VARCHAR(20),
        marital_status VARCHAR(20),

        -- Address Details
        town_city_residence VARCHAR(100),
        state_residence VARCHAR(100),
        lga_residence VARCHAR(100),
        address_residence TEXT,
        state_origin VARCHAR(100),
        lga_origin VARCHAR(100),

        -- Parents Details
        father_surname VARCHAR(100),
        father_first_name VARCHAR(100),
        father_state VARCHAR(100),
        father_lga VARCHAR(100),
        father_town VARCHAR(100),

        mother_surname VARCHAR(100),
        mother_first_name VARCHAR(100),
        mother_maiden_name VARCHAR(100),
        mother_state VARCHAR(100),
        mother_lga VARCHAR(100),
        mother_town VARCHAR(100),

        -- Metadata
        status ENUM('pending', 'processing', 'completed', 'rejected') DEFAULT 'pending',
        reference_code VARCHAR(50) UNIQUE NOT NULL,
        submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

        -- Foreign key constraint (assuming users table exists)
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_user_id (user_id),
        INDEX idx_status (status),
        INDEX idx_reference_code (reference_code),
        INDEX idx_submitted_at (submitted_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $db->exec($sql);
    echo "Birth attestations table created successfully.\n";
} catch (PDOException $e) {
    echo "Error creating birth attestations table: " . $e->getMessage() . "\n";
}
?>
