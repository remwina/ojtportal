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
            // Start session if not already started
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            // Validate input first
            $this->validator->isValidEmail($email);
            $this->validator->isValidLoginPassword($password);

            $validationResult = $this->validator->getErrors();
            if (!$validationResult['success']) {
                return $validationResult;
            }

            // First check if user exists and is active
            $user = $this->db->authenticate($email);
            
            if (!$user) {
                return [
                    'success' => false,
                    'errors' => [
                        ['field' => 'email', 'message' => 'Invalid email or password']
                    ]
                ];
            }

            // Then verify password
            if (!password_verify($password, $user['password'])) {
                return [
                    'success' => false,
                    'errors' => [
                        ['field' => 'password', 'message' => 'Invalid email or password']
                    ]
                ];
            }

            // Check account status
            if ($user['status'] === 'inactive') {
                return [
                    'success' => false,
                    'message' => 'Your account has been deactivated. Please contact the administrator.'
                ];
            }
            
            // Set all necessary session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['student_id'] = $user['id'];
            $_SESSION['usertype'] = $user['usertype'];
            $_SESSION['srcode'] = $user['srcode'];
            $_SESSION['student_name'] = $user['firstname'] . ' ' . $user['lastname'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['course_id'] = $user['course_id'];
            $_SESSION['section'] = $user['section'];
            
            // Set admin_id for admin users
            if ($user['usertype'] === 'admin') {
                $_SESSION['admin_id'] = $user['id'];
            }
            
            // Determine redirect based on user type
            $redirect = $user['usertype'] === 'admin' ? 
                       '../Admin/Dashboard.php' : 
                       '../Dashboard/dashboard.php';
            
            return [
                'success' => true,
                'message' => 'Login successful',
                'usertype' => $user['usertype'],
                'redirect' => $redirect
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