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
        $this->db = new SQL_Operations();
    }

    public function registerUser($usertype, $srcode, $email, $password, $conpass = null, $firstname = '', $lastname = '', $course_id = '', $section = '') {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->validator->clearAllErrors();

        $this->validator->isValidUsertype($usertype);
        $this->validator->isValidEmail($email);
        $this->validator->isValidSRCode($srcode);
        $this->validator->isValidPassword($password, $conpass);
        $this->validator->isValidUserInfo($firstname, $lastname, $course_id, $section);

        $validationResult = $this->validator->getErrors();
        if (!$validationResult['success']) {
            return $validationResult;
        }

        $emailExists = $this->db->checkEmailExists($email);
        $srcodeExists = $this->db->checkSRCodeExists($srcode);

        if ($emailExists || $srcodeExists) {
            $errors = [];
            if ($emailExists) {
                $errors[] = ["field" => "email", "message" => "Email already exists"];
            }
            if ($srcodeExists) {
                $errors[] = ["field" => "srcode", "message" => "SR Code already exists"];
            }
            return [
                "success" => false,
                "errors" => $errors
            ];
        }

        try {
            return $this->db->createUser([
                'usertype' => $usertype,
                'srcode' => $srcode,
                'firstname' => $firstname,
                'lastname' => $lastname,
                'email' => $email,
                'password' => $password,
                'course' => $course_id,
                'section' => $section,
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