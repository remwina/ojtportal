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
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = ["field" => "email", "message" => "Invalid email format"];
        }
        $this->addToCollectedErrors();
        return empty($this->errors);
    }

    public function isValidSRCode($srcode) {
        $this->errors = [];
        $pattern = '/^\d{2}-\d{5}$/';

        if (empty($srcode)) {
            $this->errors[] = ["field" => "srcode", "message" => "SR Code is required"];
        } elseif (!preg_match($pattern, $srcode)) {
            $this->errors[] = ["field" => "srcode", "message" => "Invalid SR Code format. Use XX-XXXXX format"];
        }
        $this->addToCollectedErrors();
        return empty($this->errors);
    }

    public function isValidPassword($password, $confirmPassword = null) {
        $this->errors = [];
        if (empty($password)) {
            $this->errors[] = ["field" => "password", "message" => "Password is required"];
        } elseif (strlen($password) < 6) {
            $this->errors[] = ["field" => "password", "message" => "Password must be at least 6 characters"];
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
}
?>