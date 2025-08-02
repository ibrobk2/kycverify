<?php
class Database {
    private $host = 'localhost';
    private $db_name = 'lil_done';
    private $username = 'root';
    private $password = '';
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
                    api_key VARCHAR(64) UNIQUE NULL,
                    api_calls_limit INT DEFAULT 1000,
                    api_calls_used INT DEFAULT 0,
                    last_login TIMESTAMP NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )
            ";
            
            // Verification logs table
            $verificationLogsTable = "
                CREATE TABLE IF NOT EXISTS verification_logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    nin VARCHAR(11) NOT NULL,
                    phone VARCHAR(20) NOT NULL,
                    status ENUM('pending', 'success', 'failed') DEFAULT 'pending',
                    response_data TEXT NULL,
                    error_message TEXT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                )
            ";
            
            // Verification results table
            $verificationResultsTable = "
                CREATE TABLE IF NOT EXISTS verification_results (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    nin VARCHAR(11) NOT NULL,
                    phone VARCHAR(20) NOT NULL,
                    name VARCHAR(100) NOT NULL,
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
            
            // Execute table creation queries
            $this->conn->exec($usersTable);
            $this->conn->exec($verificationLogsTable);
            $this->conn->exec($verificationResultsTable);
            $this->conn->exec($apiLogsTable);
            
        } catch(PDOException $exception) {
            error_log("Table creation error: " . $exception->getMessage());
            throw new Exception("Failed to create database tables");
        }
    }
}
?>
