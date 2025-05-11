<?php
require_once __DIR__ . '/../Core/Config/DataManagement/DB_Connect.php';
require_once __DIR__ . '/../Core/Validators.php';
require_once __DIR__ . '/../Core/REGEX.php';
require_once __DIR__ . '/../Core/Config/DataManagement/DB_Operations.php';

class Login {
    private $validator;
    private $db;
    private $conn;

    public function __construct() {
        $this->validator = new Validators();
        $this->db = new SQL_Operations();
        $this->conn = $this->db->getConnection();
    }

    public function loginUser($email, $password) {
        try {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            // Basic validation first
            if (empty($email) || empty($password)) {
                return [
                    'success' => false,
                    'errors' => [
                        ['field' => empty($email) ? 'email' : 'password', 
                         'message' => 'This field is required']
                    ]
                ];
            }

            // First check administrators table
            $adminStmt = $this->conn->prepare("SELECT * FROM administrators WHERE email = ?");
            $adminStmt->bind_param("s", $email);
            $adminStmt->execute();
            $user = $adminStmt->get_result()->fetch_assoc();
            $isAdmin = true;

            // If not found in administrators, check users table
            if (!$user) {
                $isAdmin = false;
                $stmt = $this->conn->prepare("SELECT * FROM users WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $user = $stmt->get_result()->fetch_assoc();
            }
            
            // Validate credentials with more specific error messages first
            if (!$user) {
                return [
                    'success' => false,
                    'error_type' => 'email_not_found',
                    'errors' => [
                        ['field' => 'email', 'message' => 'No account found with this email address']
                    ]
                ];
            }

            if (!password_verify($password, $user['password'])) {
                return [
                    'success' => false,
                    'error_type' => 'invalid_password',
                    'errors' => [
                        ['field' => 'password', 'message' => 'Incorrect password']
                    ]
                ];
            }

            // Only check for deactivation after credentials are validated
            if ($user['status'] === 'inactive') {
                return [
                    'success' => false,
                    'isDeactivated' => true
                ];
            }

            // Set session variables
            if ($isAdmin) {
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['srcode'] = $user['srcode'];
                $_SESSION['admin_name'] = $user['name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['is_super_admin'] = $user['is_super_admin'] ? true : false;
                $_SESSION['usertype'] = 'admin';
            } else {
                $_SESSION['student_id'] = $user['id'];
                $_SESSION['usertype'] = $user['usertype'];
                $_SESSION['srcode'] = $user['srcode'];
                $_SESSION['student_name'] = $user['firstname'] . ' ' . $user['lastname'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['course_id'] = $user['course_id'];
                $_SESSION['section'] = $user['section'];
            }

            return [
                'success' => true,
                'usertype' => $isAdmin ? 'admin' : $user['usertype'],
                'redirect' => $isAdmin ? '../Admin/Dashboard.php' : '../Dashboard/dashboard.php'
            ];

        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred while logging in. Please try again.'
            ];
        }
    }
}
?>