<?php 
class Validators {
    private $errors;
    private $collectedErrors;

    public function __construct() {
        $this->clearAllErrors();
    }

    private function addToCollectedErrors() {
        if (!empty($this->errors)) {
            $this->collectedErrors = array_merge($this->collectedErrors ?? [], $this->errors);
        }
    }

    public function getErrors() {
        return empty($this->collectedErrors) 
            ? ["success" => true] 
            : ["success" => false, "errors" => $this->collectedErrors];
    }

    public function clearAllErrors() {
        $this->errors = [];
        $this->collectedErrors = [];
    }

    public function isValidUsertype($usertype) {
        $this->errors = [];
        $validTypes = ['admin', 'user', 'none'];

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
        } else {
            if (!preg_match(UPPERCASE_FORMAT, $password)) {
                $this->errors[] = ["field" => "password", "message" => "Password must contain at least one uppercase letter"];
            }
            if (!preg_match(LOWERCASE_FORMAT, $password)) {
                $this->errors[] = ["field" => "password", "message" => "Password must contain at least one lowercase letter"];
            }
            if (!preg_match(DIGIT_FORMAT, $password)) {
                $this->errors[] = ["field" => "password", "message" => "Password must contain at least one number"];
            }
            if (!preg_match(SPECIAL_CHAR_FORMAT, $password)) {
                $this->errors[] = ["field" => "password", "message" => "Password must contain at least one special character"];
            }
            if (strlen($password) < 6) {
                $this->errors[] = ["field" => "password", "message" => "Password must be at least 6 characters"];
            }
        }

        if ($confirmPassword !== null) {
            if (empty($confirmPassword)) {
                $this->errors[] = ["field" => "confirm_password", "message" => "Please confirm your password"];
            } elseif ($password !== $confirmPassword) {
                $this->errors[] = ["field" => "confirm_password", "message" => "Passwords do not match"];
            }
        }
        $this->addToCollectedErrors();
        return empty($this->errors);
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

    public function isValidUserInfo($firstname, $lastname, $course_id, $section, $usertype = 'user') {
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

        if (strtolower($usertype) !== 'admin') {
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
        }

        $this->addToCollectedErrors();
        return empty($this->errors);
    }
}
?>