<?php
require_once __DIR__ . '/../Core/Config/DataManagement/DB_Connect.php';
require_once __DIR__ . '/../Core/Validators.php';
require_once __DIR__ . '/../Core/REGEX.php';
require_once __DIR__ . '/../Core/Config/DataManagement/DB_Operations.php';

class Login {
    private $db;
    private $conn;

    public function __construct() {
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

            // Check for account deactivation before validating password
            if ($user['status'] === 'inactive') {
                $deactivationMessage = '<div style="font-size: 1.1rem; margin-bottom: 1.5rem;">Your account has been deactivated for security purposes.</div>' .
                    '<div class="deactivation-notice" style="font-size: 1rem;">' .
                    '<h6 style="font-size: 1.1rem; font-weight: 600; margin: 1rem 0 0.75rem; color: #344767;">What this means:</h6>' .
                    '<ul style="margin-bottom: 1rem; padding-left: 1.5rem;">' .
                    '<li style="margin-bottom: 0.5rem;">You currently cannot access your account</li>' .
                    '<li style="margin-bottom: 0.5rem;">All your data and history are preserved</li>' .
                    '<li style="margin-bottom: 0.5rem;">Your account can be reactivated by an administrator</li>' .
                    '</ul>' .
                    '<h6 style="font-size: 1.1rem; font-weight: 600; margin: 1rem 0 0.75rem; color: #344767;">Next steps:</h6>' .
                    '<p style="font-size: 1rem; line-height: 1.5;">Please contact your system administrator to request reactivation.</p>' .
                    '</div>';
                
                return [
                    'success' => false,
                    'isDeactivated' => true,
                    'error_type' => 'account_deactivated',
                    'title' => 'Account Deactivated',
                    'message' => $deactivationMessage,
                    'icon' => 'warning',
                    'modalWidth' => '500px',
                    'confirmButtonText' => 'I Understand',
                    'confirmButtonColor' => '#6c757d'
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