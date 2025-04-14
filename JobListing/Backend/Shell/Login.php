<?php
require_once __DIR__ . '/../Core/Config/DataManagement/Database.php';
require_once __DIR__ . '/../Core/Validators.php';
require_once __DIR__ . '/../Core/REGEX.php';
require_once __DIR__ . '/../Core/Config/DataManagement/DB_Operations.php';

class Login {
    private $validator;

    public function __construct() {
        $this->validator = new Validators();
    }

    public function loginUser($srcode, $password) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->validator->clearAllErrors();

        $this->validator->isValidSRCode($srcode);
        
        $validationErrors = $this->validator->getErrors();
        if (!$validationErrors['success']) {
            return $validationErrors;
        }

        $user = DB_Operations::findUserBySRCode($srcode);
        if (!$user) {
            return [
                "success" => false,
                "errors" => [["field" => "srcode", "message" => "Invalid SR Code or password"]]
            ];
        }

        if (!password_verify($password, $user->password)) {
            return [
                "success" => false,
                "errors" => [["field" => "password", "message" => "Invalid SR Code or password"]]
            ];
        }

        $_SESSION['user_id'] = $user->id;
        $_SESSION['usertype'] = $user->usertype;
        $_SESSION['srcode'] = $user->srcode;

        return [
            "success" => true,
            "message" => "Login successful! Welcome back!",
            "redirect" => "../Frontend/placeholder.html"
        ];
    }
}
?> 