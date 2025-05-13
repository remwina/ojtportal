<?php 
class Validators {
    private $errors;
    private $collectedErrors;
    private $db;

    public function __construct() {
        $this->clearAllErrors();
        require_once __DIR__ . '/Config/DataManagement/DB_Operations.php';
        $this->db = new SQL_Operations();
    }

    private function addToCollectedErrors() {
        if (!empty($this->errors)) {
            $this->collectedErrors = array_values(array_merge($this->collectedErrors ?? [], array_filter($this->errors)));
        }
    }

    public function getErrors() {
        return empty($this->collectedErrors) 
            ? ["success" => true] 
            : ["success" => false, "errors" => array_values($this->collectedErrors)];
    }

    public function clearAllErrors() {
        $this->errors = [];
        $this->collectedErrors = [];
    }

    public function isValidUsertype($usertype) {
        $this->errors = [];
        $validTypes = ['user', 'none']; // Remove admin from default valid types

        // If trying to register as admin
        if (strtolower($usertype) === 'admin') {
            require_once __DIR__ . '/../../Admin/Admins.php';
            
            // Skip validation for initial admin setup if no admins exist
            $adminManager = new AdminsManager();
            $admins = $adminManager->getAllAdmins();
            $hasExistingAdmins = !empty($admins);
            
            if ($hasExistingAdmins && !$adminManager->canCreateAdmin($_SESSION['admin_id'] ?? 0)) {
                $this->errors[] = ["field" => "usertype", "message" => "Only super administrators can create admin accounts"];
                $this->addToCollectedErrors();
                return false;
            }
            return true; // Admin registration authorized
        }

        // Normal user type validation
        if (empty($usertype) || $usertype === 'none') {
            $this->errors[] = ["field" => "usertype", "message" => "Please select a user type"];
        } elseif (!in_array(strtolower($usertype), $validTypes)) {
            $this->errors[] = ["field" => "usertype", "message" => "Invalid user type selected"];
        }
        
        $this->addToCollectedErrors();
        return empty($this->errors);
    }

    public function isValidEmail($email) {
        $this->errors = [];
        if (empty($email)) {
            $this->errors[] = ["field" => "email", "message" => "Email is required"];
        } elseif (!preg_match(EMAIL_FORMAT, $email)) {
            $this->errors[] = ["field" => "email", "message" => "Invalid email format"];
        }
        $this->addToCollectedErrors();
        return empty($this->errors);
    }

    public function isValidSRCode($srcode) {
        $this->errors = [];
        if (empty($srcode)) {
            $this->errors[] = ["field" => "srcode", "message" => "SR Code is required"];
        } elseif (!preg_match(SRCODE_FORMAT, $srcode)) {
            $this->errors[] = ["field" => "srcode", "message" => "Invalid SR Code format. Use XX-XXXXX format"];
        }
        $this->addToCollectedErrors();
        return empty($this->errors);
    }

    public function isValidPassword($password, $confirmPassword = null) {
        $this->errors = [];
        
        if (empty($password)) {
            $this->errors[] = ["field" => "password", "message" => "Password is required"];
            return false;
        }

        $missing = [];
        
        if (!preg_match(UPPERCASE_FORMAT, $password)) {
            $missing[] = "one uppercase letter";
        }
        if (!preg_match(LOWERCASE_FORMAT, $password)) {
            $missing[] = "one lowercase letter";
        }
        if (!preg_match(DIGIT_FORMAT, $password)) {
            $missing[] = "one number";
        }
        if (!preg_match(SPECIAL_CHAR_FORMAT, $password)) {
            $missing[] = "one special character";
        }
        
        if (count($missing) > 0) {
            $this->errors[] = ["field" => "password", "message" => "Password must contain " . implode(", ", $missing)];
            $this->addToCollectedErrors();
            return false;
        }

        if (strlen($password) < 6) {
            $this->errors[] = ["field" => "password", "message" => "Password must be at least 6 characters"];
            $this->addToCollectedErrors();
            return false;
        }

        if ($confirmPassword !== null) {
            if (empty($confirmPassword)) {
                $this->errors[] = ["field" => "confirm_password", "message" => "Please confirm your password"];
            } elseif ($password !== $confirmPassword) {
                $this->errors[] = ["field" => "confirm_password", "message" => "Passwords do not match"];
            }
            $this->addToCollectedErrors();
            return empty($this->errors);
        }

        return true;
    }

    public function isValidLoginPassword($password) {
        $this->errors = [];
        if (empty($password)) {
            $this->errors[] = ["field" => "password", "message" => "Password is required"];
        } elseif (!preg_match(PASSWORD_FORMAT, $password)) {
            $this->errors[] = ["field" => "password", "message" => "Invalid password format."];
        }
        $this->addToCollectedErrors();
        return empty($this->errors);
    }

    public function isValidUserInfo($firstname, $lastname, $course_id, $section) {
        $this->errors = [];
        
        if (empty($firstname)) {
            $this->errors[] = ["field" => "firstname", "message" => "First name is required"];
        } elseif (strlen($firstname) > 50) {
            $this->errors[] = ["field" => "firstname", "message" => "First name is too long (max 50 characters)"];
        }

        if (empty($lastname)) {
            $this->errors[] = ["field" => "lastname", "message" => "Last name is required"];
        } elseif (strlen($lastname) > 50) {
            $this->errors[] = ["field" => "lastname", "message" => "Last name is too long (max 50 characters)"];
        }

        if (empty($course_id)) {
            $this->errors[] = ["field" => "course", "message" => "Please select a course"];
        } elseif (!is_numeric($course_id)) {
            $this->errors[] = ["field" => "course", "message" => "Invalid course selection"];
        }

        if (empty($section)) {
            $this->errors[] = ["field" => "section", "message" => "Section is required"];
        } elseif (!preg_match(DIGIT_FORMAT, $section)) {
            $this->errors[] = ["field" => "section", "message" => "Section must be a number"];
        }

        $this->addToCollectedErrors();
        return empty($this->errors);
    }

    public function checkDuplicateUser($srcode, $email, $userId = null) {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("CALL sp_check_duplicate_user(?, ?, ?)");
        $stmt->bind_param("ssi", $srcode, $email, $userId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if ($result['duplicate']) {
            $this->errors[] = ["field" => "general", "message" => $result['message']];
            $this->addToCollectedErrors();
            return false;
        }
        return true;
    }

    public function checkDuplicateCompany($name, $companyId = null) {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("CALL sp_check_duplicate_company(?, ?)");
        $stmt->bind_param("si", $name, $companyId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if ($result['duplicate']) {
            $this->errors[] = ["field" => "name", "message" => $result['message']];
            $this->addToCollectedErrors();
            return false;
        }
        return true;
    }

    public function checkDuplicateJob($title, $companyId, $jobId = null) {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("CALL sp_check_duplicate_job(?, ?, ?)");
        $stmt->bind_param("sii", $title, $companyId, $jobId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if ($result['duplicate']) {
            $this->errors[] = ["field" => "title", "message" => $result['message']];
            $this->addToCollectedErrors();
            return false;
        }
        return true;
    }

    public function checkDuplicateAdmin($srcode, $email, $adminId = null) {
        $conn = $this->db->getConnection();
        
        // Call the stored procedure to check for duplicates
        $stmt = $conn->prepare("CALL sp_check_duplicate_admin(?, ?, ?)");
        $stmt->bind_param("ssi", $srcode, $email, $adminId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if ($result['duplicate']) {
            // Parse and add individual error messages
            $messages = explode('. ', trim($result['message']));
            foreach ($messages as $message) {
                if (!empty($message)) {
                    if (strpos($message, 'SR Code') !== false) {
                        $this->errors[] = ["field" => "srcode", "message" => $message];
                    } elseif (strpos($message, 'Email') !== false) {
                        $this->errors[] = ["field" => "email", "message" => $message];
                    } else {
                        $this->errors[] = ["field" => "general", "message" => $message];
                    }
                }
            }
            $this->addToCollectedErrors();
            return false;
        }
        return true;
    }
}
?>