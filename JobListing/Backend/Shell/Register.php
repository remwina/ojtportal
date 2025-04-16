<?php
require_once __DIR__ . '/../Core/Config/DataManagement/DB_Connect.php';
require_once __DIR__ . '/../Core/Config/DataManagement/DB_Operations.php';
require_once __DIR__ . '/../Core/Validators.php';
require_once __DIR__ . '/../Core/REGEX.php';

class UserReg {
    private $validator;
    private $db;

    public function __construct() {
        $this->validator = new Validators();
        $this->db = new SQL_Operations();  // Will use DatabaseConfig singleton by default
    }

    public function registerUser($usertype, $srcode, $email, $password, $conpass = null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->validator->clearAllErrors();

        // Validate all inputs
        $this->validator->isValidUsertype($usertype);
        $this->validator->isValidEmail($email);
        $this->validator->isValidSRCode($srcode);
        $this->validator->isValidPassword($password, $conpass);

        $validationResult = $this->validator->getErrors();
        if (!$validationResult['success']) {
            return $validationResult;
        }

        // Check for existing email or SR Code
        if ($this->db->checkEmailExists($email)) {
            return [
                "success" => false,
                "errors" => [["field" => "email", "message" => "Email already exists"]]
            ];
        }

        if ($this->db->checkSRCodeExists($srcode)) {
            return [
                "success" => false,
                "errors" => [["field" => "srcode", "message" => "SR Code already exists"]]
            ];
        }

        try {
            return $this->db->createUser([
                'usertype' => $usertype,
                'srcode' => $srcode,
                'email' => $email,
                'password' => $password,
                'status' => 'active'
            ]);
        } catch (Exception $e) {
            return [
                "success" => false,
                "errors" => [["field" => "general", "message" => "Registration failed: " . $e->getMessage()]]
            ];
        }
    }
}
?>