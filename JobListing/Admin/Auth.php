<?php
session_start();

require_once __DIR__ . '/../Backend/Shell/Login.php';

class Auth {
    private $loginHandler;

    public function __construct() {
        $this->loginHandler = new Login();
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
        return isset($_SESSION['user_id']);
    }

    public function usertype() {
        return $_SESSION['usertype'] ?? null;
    }

    public function logout() {
        session_unset();
        session_destroy();
    }
}
?>
