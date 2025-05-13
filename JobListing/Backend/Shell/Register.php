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

        // Run all validations
        $this->validator->isValidUsertype($usertype);
        $this->validator->isValidEmail($email);
        $this->validator->isValidSRCode($srcode);
        $this->validator->isValidPassword($password, $conpass);
        $this->validator->isValidUserInfo($firstname, $lastname, $course_id, $section);

        // Get validation result after all checks
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
            $userId = $this->insertUser($usertype, $srcode, $email, $password, $firstname, $lastname, $course_id, $section);
            error_log("Registration successful for user: " . $email);
            return [
                "success" => true,
                "message" => "Registration successful",
                "user_id" => $userId
            ];
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            return [
                "success" => false,
                "errors" => [["field" => "general", "message" => "Registration failed: " . $e->getMessage()]]
            ];
        }
    }

    private function insertUser($usertype, $srcode, $email, $password, $firstname, $lastname, $course_id, $section) {
        try {
            $conn = $this->db->getConnection();
            
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("CALL sp_create_user(?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $status = 'active';
            $stmt->bind_param("sssssisss", 
                $srcode, 
                $firstname, 
                $lastname, 
                $email, 
                $hashedPassword, 
                $course_id, 
                $section, 
                $usertype, 
                $status
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to create user");
            }
            
            $result = $stmt->get_result();
            if (!$result) {
                throw new Exception("Failed to get user ID after creation");
            }
            
            $userData = $result->fetch_assoc();
            return $userData['user_id'];
            
        } catch (Exception $e) {
            error_log("User insertion error: " . $e->getMessage());
            throw new Exception("An error occurred during registration");
        }
    }
}
?>