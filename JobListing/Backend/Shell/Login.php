<?php
require_once __DIR__ . '/../Core/Config/DataManagement/DB_Connect.php';
require_once __DIR__ . '/../Core/Validators.php';
require_once __DIR__ . '/../Core/REGEX.php';
require_once __DIR__ . '/../Core/Config/DataManagement/DB_Operations.php';

class Login {
    private $validator;
    private $db;

    public function __construct() {
        $this->validator = new Validators();
        $this->db = new SQL_Operations();
    }

    public function loginUser($email, $password) {
        try {
            $dbOps = new SQL_Operations();
            $conn = $dbOps->getConnection();
            
            $stmt = $conn->prepare("CALL sp_authenticate_user(?)");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Invalid email or password'
                ];
            }
            
            if (!password_verify($password, $user['password'])) {
                return [
                    'success' => false,
                    'message' => 'Invalid email or password'
                ];
            }
            
            if ($user['status'] !== 'active') {
                return [
                    'success' => false,
                    'message' => 'Your account is not active'
                ];
            }
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['student_id'] = $user['id'];  // Adding this for dashboard compatibility
            $_SESSION['usertype'] = $user['usertype'];
            $_SESSION['srcode'] = $user['srcode'];
            $_SESSION['student_name'] = $user['firstname'] . ' ' . $user['lastname'];  // Adding full name to session
            
            return [
                'success' => true,
                'message' => 'Login successful',
                'usertype' => $user['usertype'],
                'redirect' => '../Dashboard/dashboard.php'  // Adding redirect URL
            ];
            
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred during login'
            ];
        }
    }
}
?>