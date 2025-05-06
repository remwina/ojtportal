<?php
require_once 'DB_Connect.php';
require_once 'DatabaseSchema.php';

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
        $conn = $this->getConnection();
        $sql = "SELECT u.id, u.srcode, u.firstname, u.lastname, u.email, u.password, u.usertype, u.status 
                FROM users u WHERE u.email = ? AND u.status = 'active' LIMIT 1";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Failed to prepare authentication query");
        }

        $stmt->bind_param('s', $email);
        if (!$stmt->execute()) {
            throw new Exception("Authentication failed");
        }

        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function checkEmailExists($email) {
        return $this->checkExists('users', ['email' => $email]);
    }

    public function checkSRCodeExists($srcode) {
        return $this->checkExists('users', ['srcode' => $srcode]);
    }

    public function createUser($userData) {
        $conn = $this->getConnection();
        $conn->begin_transaction();

        try {
            $requiredFields = ['srcode', 'firstname', 'lastname', 'email', 'password', 'course', 'section', 'usertype', 'status'];
            foreach ($requiredFields as $field) {
                if (!isset($userData[$field]) || trim($userData[$field]) === '') {
                    throw new Exception("Missing required field: $field");
                }
            }

            $stmt = $conn->prepare("INSERT INTO users (srcode, firstname, lastname, email, password, course_id, section, usertype, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if (!$stmt) {
                throw new Exception("Failed to prepare user creation query");
            }

            $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);
            $stmt->bind_param('sssssisss', 
                $userData['srcode'],
                $userData['firstname'],
                $userData['lastname'],
                $userData['email'],
                $hashedPassword,
                $userData['course'],
                $userData['section'],
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
        try {
            $conn = $this->getConnection();
            DatabaseSchema::initializeDatabase($conn);
            
            if (!$this->checkEmailExists('admin@admin.com')) {
                $this->createUser(DatabaseSchema::getDefaultAdmin());
            }
            
            return ["success" => true, "message" => "Database initialized successfully"];
        } catch (Exception $e) {
            throw new Exception("Database initialization failed: " . $e->getMessage());
        }
    }

    public function resetDatabase() {
        try {
            $conn = $this->getConnection();
            DatabaseSchema::resetDatabase($conn);
            
            if (!$this->checkEmailExists('admin@admin.com')) {
                $this->createUser(DatabaseSchema::getDefaultAdmin());
            }
            
            return ["success" => true, "message" => "Database reset successfully"];
        } catch (Exception $e) {
            throw new Exception("Database reset failed: " . $e->getMessage());
        }
    }

    public function close() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
?>