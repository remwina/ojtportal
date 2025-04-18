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
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->validator->clearAllErrors();

        $this->validator->isValidEmail($email);
        $this->validator->isValidLoginPassword($password);
        
        $validationErrors = $this->validator->getErrors();
        if (!$validationErrors['success']) {
            return $validationErrors;
        }

        $user = $this->db->authenticate($email);
        if (!$user) {
            return [
                "success" => false,
                "errors" => [["field" => "email", "message" => "User not found"]]
            ];
        }

        if (!password_verify($password, $user['password'])) {
            return [
                "success" => false,
                "errors" => [["field" => "password", "message" => "Incorrect password"]]
            ];
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['usertype'] = $user['usertype'];
        $_SESSION['srcode'] = $user['srcode'];

        $redirect = $user['usertype'] === 'admin' ? "../Frontend/Placeholder1-Admin.html" : "../Frontend/Placeholder3-User.html";

        return [
            "success" => true,
            "message" => "Login successful! Welcome back!",
            "redirect" => $redirect
        ];
    }
}
?>