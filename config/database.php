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
                    details TEXT NULL,
                    reference VARCHAR(100) NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                )
            ";
            
            // Execute table creation queries
            $this->conn->exec($usersTable);
            $this->conn->exec($pricingTable);
            $this->conn->exec($verificationLogsTable);
            $this->conn->exec($verificationResultsTable);
            $this->conn->exec($apiLogsTable);
            $this->conn->exec($walletTransactionsTable);
            
            // Seed pricing table if empty
            $this->seedPricing();

            // Migration: Add virtual account columns if they don't exist
            $this->migrateVirtualAccountColumns();

        } catch(PDOException $exception) {
            error_log("Table creation error: " . $exception->getMessage());
            // throw new Exception("Failed to create database tables");
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

    private function seedPricing() {
        try {
            $stmt = $this->conn->query("SELECT COUNT(*) FROM pricing");
            if ($stmt->fetchColumn() == 0) {
                $services = [
                    ['nin_verification', 50.00],
                    ['bvn_verification', 30.00],
                    ['birth_attestation', 100.00],
                    ['ipe_clearance', 200.00]
                ];
                
                $insert = $this->conn->prepare("INSERT INTO pricing (service_name, price) VALUES (?, ?)");
                foreach ($services as $service) {
                    $insert->execute($service);
                }
            }
        } catch (PDOException $e) {
            error_log("Pricing seed error: " . $e->getMessage());
        }
    }
}
?>
