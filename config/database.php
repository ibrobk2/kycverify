<?php
class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $conn;
    
    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
                ]
            );
            
            // Create tables if they don't exist
            $this->createTables();
            
        } catch(PDOException $exception) {
            error_log("Connection error: " . $exception->getMessage());
            // Don't throw exception to user, just return null or handle gracefully in caller
            // But for now, we keep existing behavior but maybe cleaner
            throw new Exception("Database connection failed");
        }
        
        return $this->conn;
    }
    
    private function createTables() {
        try {
            // Users table
            $usersTable = "
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
                    virtual_account_number VARCHAR(20) NULL,
                    bank_name VARCHAR(100) NULL,
                    account_name VARCHAR(100) NULL,
                    api_key VARCHAR(64) UNIQUE NULL,
                    api_calls_limit INT DEFAULT 1000,
                    api_calls_used INT DEFAULT 0,
                    last_login TIMESTAMP NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )
            ";
            
            // Pricing table
            $pricingTable = "
                CREATE TABLE IF NOT EXISTS pricing (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    service_name VARCHAR(50) UNIQUE NOT NULL,
                    price DECIMAL(10,2) NOT NULL,
                    description TEXT NULL,
                    currency VARCHAR(3) DEFAULT 'NGN',
                    status ENUM('active', 'inactive') DEFAULT 'active',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )
            ";

            // Verification logs table
            $verificationLogsTable = "
                CREATE TABLE IF NOT EXISTS verification_logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    service_type VARCHAR(50) NOT NULL,
                    reference_number VARCHAR(50) NULL,
                    status ENUM('pending', 'success', 'failed') DEFAULT 'pending',
                    response_data TEXT NULL,
                    error_message TEXT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                )
            ";
            
            // Verification results table (Simplified for generic use)
            $verificationResultsTable = "
                CREATE TABLE IF NOT EXISTS verification_results (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    service_type VARCHAR(50) NOT NULL,
                    identifier VARCHAR(50) NOT NULL,
                    data TEXT NULL,
                    status ENUM('verified', 'unverified') DEFAULT 'verified',
                    verified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                )
            ";

            // VTU Providers table
            $vtuProvidersTable = "
                CREATE TABLE IF NOT EXISTS vtu_providers (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(100) NOT NULL,
                    code VARCHAR(50) UNIQUE NOT NULL,
                    api_url VARCHAR(255) NOT NULL,
                    api_key VARCHAR(255) NULL,
                    api_secret VARCHAR(255) NULL,
                    balance DECIMAL(10,2) DEFAULT 0.00,
                    status ENUM('active', 'inactive') DEFAULT 'active',
                    is_default BOOLEAN DEFAULT FALSE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )
            ";

            // API Configurations table
            $apiConfigsTable = "
                CREATE TABLE IF NOT EXISTS api_configurations (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    service_name VARCHAR(50) UNIQUE NOT NULL,
                    api_key TEXT NULL,
                    api_secret TEXT NULL,
                    merchant_id VARCHAR(255) NULL,
                    webhook_secret TEXT NULL,
                    base_url VARCHAR(255) NULL,
                    status ENUM('active', 'inactive') DEFAULT 'active',
                    settings TEXT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )
            ";

            // Birth Attestations table
            $birthAttestationsTable = "
                CREATE TABLE IF NOT EXISTS birth_attestations (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    title VARCHAR(20) NULL,
                    nin VARCHAR(20) NULL,
                    surname VARCHAR(100) NOT NULL,
                    first_name VARCHAR(100) NOT NULL,
                    middle_name VARCHAR(100) NULL,
                    gender ENUM('male', 'female', 'other') NOT NULL,
                    new_date_of_birth DATE NOT NULL,
                    phone_number VARCHAR(20) NOT NULL,
                    marital_status VARCHAR(50) NULL,
                    town_city_residence VARCHAR(100) NULL,
                    state_residence VARCHAR(100) NULL,
                    lga_residence VARCHAR(100) NULL,
                    address_residence TEXT NULL,
                    state_origin VARCHAR(100) NULL,
                    lga_origin VARCHAR(100) NULL,
                    father_surname VARCHAR(100) NULL,
                    father_first_name VARCHAR(100) NULL,
                    father_state VARCHAR(100) NULL,
                    father_lga VARCHAR(100) NULL,
                    father_town VARCHAR(100) NULL,
                    mother_surname VARCHAR(100) NULL,
                    mother_first_name VARCHAR(100) NULL,
                    mother_maiden_name VARCHAR(100) NULL,
                    mother_state VARCHAR(100) NULL,
                    mother_lga VARCHAR(100) NULL,
                    mother_town VARCHAR(100) NULL,
                    reference_code VARCHAR(50) UNIQUE NOT NULL,
                    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                )
            ";

            $this->conn->exec($usersTable);
            $this->conn->exec($pricingTable);
            $this->conn->exec($verificationLogsTable);
            $this->conn->exec($verificationResultsTable);
            $this->conn->exec($vtuProvidersTable);
            $this->conn->exec($apiConfigsTable);
            $this->conn->exec($birthAttestationsTable);
            
            // API usage logs table
            $apiLogsTable = "
                CREATE TABLE IF NOT EXISTS api_logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NULL,
                    endpoint VARCHAR(100) NOT NULL,
                    method VARCHAR(10) NOT NULL,
                    ip_address VARCHAR(45) NOT NULL,
                    user_agent TEXT NULL,
                    request_data TEXT NULL,
                    response_status INT NOT NULL,
                    response_time DECIMAL(10,3) NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
                )
            ";

            // Wallet transactions table
            $walletTransactionsTable = "
                CREATE TABLE IF NOT EXISTS wallet_transactions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    amount DECIMAL(10,2) NOT NULL,
                    transaction_type ENUM('credit', 'debit') NOT NULL,
                    description TEXT NULL,
                    reference VARCHAR(100) NULL,
                    previous_balance DECIMAL(10,2) DEFAULT NULL,
                    new_balance DECIMAL(10,2) DEFAULT NULL,
                    status VARCHAR(20) DEFAULT 'completed',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                )
            ";
            
            // BVN Modification Uploads table
            $bvnModUploadsTable = "
                CREATE TABLE IF NOT EXISTS bvn_modification_uploads (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    member_id VARCHAR(50) NULL,
                    first_name VARCHAR(100) NULL,
                    last_name VARCHAR(100) NULL,
                    phone VARCHAR(20) NULL,
                    email VARCHAR(100) NULL,
                    service_type VARCHAR(50) NULL,
                    amount DECIMAL(10,2) NULL,
                    status ENUM('pending', 'processing', 'completed', 'rejected') DEFAULT 'pending',
                    reference VARCHAR(100) UNIQUE NULL,
                    file_path VARCHAR(255) NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                )
            ";
            
            // Service transactions table
            $serviceTransactionsTable = "
                CREATE TABLE IF NOT EXISTS service_transactions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    service_type VARCHAR(50) NOT NULL,
                    reference_number VARCHAR(100) NULL,
                    status VARCHAR(20) DEFAULT 'pending',
                    amount DECIMAL(10,2) DEFAULT 0.00,
                    request_data TEXT NULL,
                    response_data TEXT NULL,
                    error_message TEXT NULL,
                    admin_notes TEXT NULL,
                    provider VARCHAR(50) NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                )
            ";

            // NIN Verification Logs table
            $ninVerificationLogsTable = "
                CREATE TABLE IF NOT EXISTS nin_verification_logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    nin VARCHAR(20) NOT NULL,
                    status ENUM('pending', 'success', 'failed') DEFAULT 'pending',
                    response_data TEXT NULL,
                    error_message TEXT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                )
            ";

            // VTU API Settings table
            $vtuSettingsTable = "
                CREATE TABLE IF NOT EXISTS vtu_api_settings (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    setting_key VARCHAR(50) UNIQUE NOT NULL,
                    setting_value TEXT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )
            ";

            // VTU Transactions table
            $vtuTransactionsTable = "
                CREATE TABLE IF NOT EXISTS vtu_transactions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    provider_id INT NOT NULL,
                    transaction_ref VARCHAR(50) UNIQUE NOT NULL,
                    provider_ref VARCHAR(50) NULL,
                    transaction_type ENUM('AIRTIME', 'DATA') NOT NULL,
                    network VARCHAR(20) NOT NULL,
                    phone_number VARCHAR(20) NOT NULL,
                    amount DECIMAL(10,2) NOT NULL,
                    commission DECIMAL(10,2) DEFAULT 0.00,
                    plan_id VARCHAR(50) NULL,
                    plan_name VARCHAR(100) NULL,
                    data_amount VARCHAR(50) NULL,
                    status ENUM('PENDING', 'PROCESSING', 'SUCCESS', 'FAILED') DEFAULT 'PENDING',
                    status_message TEXT NULL,
                    wallet_balance_before DECIMAL(10,2) NULL,
                    wallet_balance_after DECIMAL(10,2) NULL,
                    api_request TEXT NULL,
                    api_response TEXT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    completed_at TIMESTAMP NULL,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                )
            ";

            // Data Plans table
            $dataPlansTable = "
                CREATE TABLE IF NOT EXISTS data_plans (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    provider_id INT NOT NULL,
                    network VARCHAR(20) NOT NULL,
                    plan_id VARCHAR(50) NOT NULL,
                    plan_name VARCHAR(100) NOT NULL,
                    plan_type VARCHAR(50) DEFAULT 'DATA',
                    data_amount VARCHAR(50) NULL,
                    validity VARCHAR(50) NULL,
                    price DECIMAL(10,2) NOT NULL,
                    cost_price DECIMAL(10,2) NULL,
                    is_active BOOLEAN DEFAULT TRUE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY network_plan (provider_id, network, plan_id)
                )
            ";

            // Execute table creation queries
            $this->conn->exec($usersTable);
            $this->conn->exec($pricingTable);
            $this->conn->exec($verificationLogsTable);
            $this->conn->exec($verificationResultsTable);
            $this->conn->exec($apiLogsTable);
            $this->conn->exec($walletTransactionsTable);
            $this->conn->exec($vtuProvidersTable);
            $this->conn->exec($apiConfigsTable);
            $this->conn->exec($birthAttestationsTable);
            $this->conn->exec($bvnModUploadsTable);
            $this->conn->exec($serviceTransactionsTable);
            $this->conn->exec($ninVerificationLogsTable);
            $this->conn->exec($vtuSettingsTable);
            $this->conn->exec($vtuTransactionsTable);
            $this->conn->exec($dataPlansTable);
            
            // Seed pricing table if empty
            $this->seedPricing();

            // Migration: Add virtual account columns if they don't exist
            $this->migrateVirtualAccountColumns();

            // Migration: Add description column to pricing table if it doesn't exist
            $this->migratePricingDescriptionColumn();

            // Migration: Fix transaction tables schema
            $this->migrateTransactionTables();

        } catch(PDOException $exception) {
            error_log("Table creation error: " . $exception->getMessage());
            // throw new Exception("Failed to create database tables");
        }
    }

    private function migrateTransactionTables() {
        try {
            // 1. Wallet Transactions Migration
            $tableName = 'wallet_transactions';
            
            // Handle 'details' to 'description' rename or add 'description'
            $stmt = $this->conn->prepare("SHOW COLUMNS FROM $tableName LIKE 'description'");
            $stmt->execute();
            if (!$stmt->fetch()) {
                $stmt = $this->conn->prepare("SHOW COLUMNS FROM $tableName LIKE 'details'");
                $stmt->execute();
                if ($stmt->fetch()) {
                    $this->conn->exec("ALTER TABLE $tableName CHANGE COLUMN details description TEXT NULL");
                } else {
                    $this->conn->exec("ALTER TABLE $tableName ADD COLUMN description TEXT NULL AFTER transaction_type");
                }
            }

            // Add other missing columns
            $cols = [
                'previous_balance' => 'DECIMAL(10,2) DEFAULT NULL AFTER reference',
                'new_balance' => 'DECIMAL(10,2) DEFAULT NULL AFTER previous_balance',
                'status' => "VARCHAR(20) DEFAULT 'completed' AFTER new_balance"
            ];
            foreach ($cols as $col => $def) {
                $stmt = $this->conn->prepare("SHOW COLUMNS FROM $tableName LIKE ?");
                $stmt->execute([$col]);
                if (!$stmt->fetch()) {
                    $this->conn->exec("ALTER TABLE $tableName ADD COLUMN $col $def");
                }
            }

            // Ensure admin_id is nullable if it exists
            $stmt = $this->conn->prepare("SHOW COLUMNS FROM $tableName LIKE 'admin_id'");
            $stmt->execute();
            if ($row = $stmt->fetch()) {
                if ($row['Null'] === 'NO') {
                    // Try to make it nullable. Note: FK might need to be dropped and recreated if it causes issues, 
                    // but usually MySQL allows making a FK column nullable.
                    try {
                        $this->conn->exec("ALTER TABLE $tableName MODIFY COLUMN admin_id INT(11) NULL");
                    } catch (PDOException $e) {
                        error_log("Could not make admin_id nullable: " . $e->getMessage());
                    }
                }
            }

            // 2. Service Transactions Migration
            $tableName = 'service_transactions';
            $stmt = $this->conn->prepare("SHOW COLUMNS FROM $tableName LIKE 'admin_notes'");
            $stmt->execute();
            if (!$stmt->fetch()) {
                $this->conn->exec("ALTER TABLE $tableName ADD COLUMN admin_notes TEXT NULL AFTER error_message");
            }

        } catch (PDOException $e) {
            error_log("Transaction tables migration error: " . $e->getMessage());
        }
    }
    private function migrateVirtualAccountColumns() {
        try {
            $columns = [
                'virtual_account_number' => 'VARCHAR(20) NULL',
                'bank_name' => 'VARCHAR(100) NULL',
                'account_name' => 'VARCHAR(100) NULL'
            ];

            foreach ($columns as $column => $definition) {
                $stmt = $this->conn->prepare("SHOW COLUMNS FROM users LIKE ?");
                $stmt->execute([$column]);
                if (!$stmt->fetch()) {
                    $this->conn->exec("ALTER TABLE users ADD COLUMN $column $definition AFTER email_verified");
                }
            }
        } catch (PDOException $e) {
            error_log("Migration error: " . $e->getMessage());
        }
    }

    private function migratePricingDescriptionColumn() {
        try {
            $stmt = $this->conn->prepare("SHOW COLUMNS FROM pricing LIKE ?");
            $stmt->execute(['description']);
            if (!$stmt->fetch()) {
                $this->conn->exec("ALTER TABLE pricing ADD COLUMN description TEXT NULL AFTER price");
            }
        } catch (PDOException $e) {
            error_log("Pricing migration error: " . $e->getMessage());
        }
    }

    private function seedPricing() {
        try {
            $services = [
                ['nin_verification', 50.00],
                ['bvn_verification', 30.00],
                ['birth_attestation', 100.00],
                ['ipe_clearance', 200.00],
                ['nin_mod_dob', 40000.00],
                ['nin_mod_address', 6000.00],
                ['nin_mod_phone', 6000.00],
                ['nin_mod_name', 6000.00]
            ];
            
            $check = $this->conn->prepare("SELECT id FROM pricing WHERE service_name = ?");
            $insert = $this->conn->prepare("INSERT INTO pricing (service_name, price) VALUES (?, ?)");
            
            foreach ($services as $service) {
                $check->execute([$service[0]]);
                if (!$check->fetch()) {
                    $insert->execute($service);
                }
            }
        } catch (PDOException $e) {
            error_log("Pricing seed error: " . $e->getMessage());
        }
    }
}
?>
