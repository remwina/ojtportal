<?php
require_once 'DB_Connect.php';

class SQL_Operations {
    private $conn;

    public function __construct($config = null) {
        if ($config instanceof DBConn) {
            $this->conn = $config;
        } else {
            $this->conn = new DBConn();
        }
    }

    public function getConnection() {
        if (!$this->conn) {
            throw new Exception("Database connection not initialized");
        }
        return $this->conn->getConnection();
    }

    public function authenticate($email) {
        try {
            $conn = $this->getConnection();
            $sql = "SELECT 
                    u.id,
                    u.srcode,
                    u.email,
                    u.password,
                    u.usertype,
                    u.status
                    FROM users u
                    WHERE u.email = ? AND u.status = 'active'
                    LIMIT 1";

            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Failed to prepare authentication query");
            }

            $stmt->bind_param('s', $email);
            if (!$stmt->execute()) {
                throw new Exception("Authentication query failed");
            }

            $result = $stmt->get_result();
            return $result->fetch_assoc();
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function checkEmailExists($email) {
        try {
            return $this->checkExists('users', ['email' => $email]);
        } catch (Exception $e) {
            throw new Exception("Email verification failed");
        }
    }

    public function checkSRCodeExists($srcode) {
        try {
            return $this->checkExists('users', ['srcode' => $srcode]);
        } catch (Exception $e) {
            throw new Exception("SR Code verification failed");
        }
    }

    public function createUser($userData) {
        $conn = $this->getConnection();
        $conn->begin_transaction();

        try {
            $requiredFields = ['srcode', 'email', 'password', 'usertype', 'status'];
            foreach ($requiredFields as $field) {
                if (!isset($userData[$field]) || trim($userData[$field]) === '') {
                    throw new Exception("Missing required field: $field");
                }
            }

            $stmt = $conn->prepare("INSERT INTO users (srcode, email, password, usertype, status) VALUES (?, ?, ?, ?, ?)");
            if (!$stmt) {
                throw new Exception("Failed to prepare user creation query");
            }

            $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);
            $stmt->bind_param('sssss', 
                $userData['srcode'],
                $userData['email'],
                $hashedPassword,
                $userData['usertype'],
                $userData['status']
            );

            if (!$stmt->execute()) {
                throw new Exception("Failed to create user: " . $stmt->error);
            }

            $userId = $conn->insert_id;
            $stmt->close();

            $conn->commit();
            return [
                'success' => true,
                'user_id' => $userId,
                'message' => 'User created successfully'
            ];
        } catch (Exception $e) {
            $conn->rollback();
            throw new Exception("Registration failed: " . $e->getMessage());
        }
    }

    private function checkExists($table, $conditions) {
        $conn = $this->getConnection();
        $where = [];
        $params = [];
        $types = '';
        
        foreach ($conditions as $field => $value) {
            $where[] = "$field = ?";
            $params[] = $value;
            $types .= is_int($value) ? 'i' : 's';
        }
        
        $sql = "SELECT 1 FROM $table WHERE " . implode(' AND ', $where) . " LIMIT 1";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Failed to prepare existence check query");
        }

        $stmt->bind_param($types, ...$params);
        if (!$stmt->execute()) {
            throw new Exception("Failed to execute existence check");
        }

        $stmt->store_result();
        return $stmt->num_rows > 0;
    }

    public function initDatabase() {
        $conn = $this->getConnection();
        try {
            $sql = "CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                srcode VARCHAR(9) UNIQUE NOT NULL,
                email VARCHAR(255) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                usertype ENUM('admin', 'user', 'none') NOT NULL DEFAULT 'none',
                status ENUM('active', 'inactive') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                deleted_at TIMESTAMP NULL,
                INDEX idx_email_status (email, status),
                INDEX idx_srcode_status (srcode, status)
            )";

            if (!$conn->query($sql)) {
                throw new Exception("Failed to create users table: " . $conn->error);
            }

            if (!$this->checkEmailExists('admin@admin.com')) {
                $this->createUser([
                    'srcode' => '21-00001',
                    'email' => 'admin@admin.com',
                    'password' => 'Admin@123',
                    'usertype' => 'admin',
                    'status' => 'active'
                ]);
            }
            
            return ["success" => true, "message" => "Database initialized successfully"];
        } catch (Exception $e) {
            throw new Exception("Database initialization failed: " . $e->getMessage());
        }
    }

    public function close() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
?>