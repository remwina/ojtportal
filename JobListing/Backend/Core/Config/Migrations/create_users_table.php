<?php

require_once __DIR__ . '/../DataManagement/DB_Connect.php';
require_once __DIR__ . '/../DataManagement/DB_Operations.php';

class CreateUsersTable {
    private $dbOps;

    public function __construct($dbOps) {
        $this->dbOps = $dbOps;
    }

    public function up() {
        $conn = $this->dbOps->getConnection();
        
        try {
            // Create users table if it doesn't exist
            $sql = "CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                srcode VARCHAR(9) UNIQUE NOT NULL,
                email VARCHAR(255) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                usertype ENUM('admin', 'user', 'none') NOT NULL DEFAULT 'none',
                status ENUM('active', 'inactive') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                deleted_at TIMESTAMP NULL
            )";
            
            if ($conn->query($sql)) {
                echo "Users table created successfully!\n";
            } else {
                throw new Exception("Error creating users table: " . $conn->error);
            }
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
            throw $e;
        }
    }

    public function down() {
        $conn = $this->dbOps->getConnection();
        
        try {
            $sql = "DROP TABLE IF EXISTS users";
            if ($conn->query($sql)) {
                echo "Users table dropped successfully!\n";
            } else {
                throw new Exception("Error dropping users table: " . $conn->error);
            }
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
            throw $e;
        }
    }
}