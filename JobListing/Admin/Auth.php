<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../Backend/Shell/Login.php';
require_once __DIR__ . '/../Backend/Core/Security/TokenHandler.php';

class Auth {
    private $loginHandler;

    public function __construct() {
        $this->loginHandler = new Login();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function login($email, $password) {
        $result = $this->loginHandler->loginUser($email, $password);
        if ($result['success']) {
            // Session variables are set in loginUser method
            return true;
        }
        return false;
    }

    public function check() {
        if (!isset($_SESSION['admin_id'])) {
            return false;
        }
        
        // Check if admin is still active in database
        require_once __DIR__ . '/../Backend/Core/Config/DataManagement/DB_Operations.php';
        $dbOps = new SQL_Operations();
        $conn = $dbOps->getConnection();
        
        $stmt = $conn->prepare("SELECT status FROM administrators WHERE id = ? AND status = 'active'");
        $stmt->bind_param("i", $_SESSION['admin_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $this->logout();
            return false;
        }
        
        // Initialize CSRF token if not present
        if (!isset($_SESSION['csrf_token'])) {
            TokenHandler::generateToken();
        }
        
        return true;
    }

    public function usertype() {
        return isset($_SESSION['admin_id']) ? 'admin' : null;
    }

    public function name() {
        return $_SESSION['admin_name'] ?? 'Admin';
    }

    public function logout() {
        TokenHandler::removeToken();
        session_unset();
        session_destroy();
    }
}
?>
