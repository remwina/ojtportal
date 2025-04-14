<?php 

class Validators {
    private $errors;
    private $collectedErrors;

    private function addToCollectedErrors()
    {
        if (!empty($this->errors)) {
            $this->collectedErrors = array_merge($this->collectedErrors, $this->errors);
        }
    }

    public function getErrors()
    {
        return empty($this->collectedErrors)
            ? ["success" => true]
            : ["success" => false, "errors" => $this->collectedErrors];
    }

    public function clearAllErrors()
    {
        $this->errors = [];
        $this->collectedErrors = [];
    }

    public function isValidUsertype($usertype) {
        $this->errors = [];

        if (empty($usertype)) {
            $this->errors[] = ["field" => "usertype", "message" => "User type is required"];
        } elseif (!in_array($usertype, ['admin', 'user'])) {
            $this->errors[] = ["field" => "usertype", "message" => "Invalid user type"];
        }

        $this->addToCollectedErrors();
        return $this->errors;
    }

    public function isValidSRCode($srcode){
        $this->errors = [];

        if (empty($srcode)) {
            $this->errors[] = ["field" => "srcode", "message" => "SR-Code is required"];
        } elseif (!preg_match(SRCODE_FORMAT, $srcode)) {
            $this->errors[] = ["field" => "srcode", "message" => "Invalid SR-Code format"];
        }

        $this->addToCollectedErrors();
        return $this->errors;
    }

    public function isValidEmail($email){
        $this->errors = [];

        if (empty($email)) {
            $this->errors[] = ["field" => "email", "message" => "Email is required"];
        } elseif (!preg_match(EMAIL_FORMAT, $email)) {
            $this->errors[] = ["field" => "email", "message" => "Invalid email format"];
        }

        $this->addToCollectedErrors();
        return $this->errors;
    }

    public function isValidPassword($password){
        $this->errors = [];

        if (empty($password)) {
            $this->errors[] = ["field" => "password", "message" => "Password is required"];
        } elseif (strlen($password) < 8) {
            $this->errors[] = ["field" => "password", "message" => "Password must be at least 8 characters long"];
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $this->errors[] = ["field" => "password", "message" => "Password must contain at least one uppercase letter"];
        } elseif (!preg_match('/[a-z]/', $password)) {
            $this->errors[] = ["field" => "password", "message" => "Password must contain at least one lowercase letter"];
        } elseif (!preg_match('/\d/', $password)) {
            $this->errors[] = ["field" => "password", "message" => "Password must contain at least one digit"];
        } elseif (!preg_match('/[\W_]/', $password)) {
            $this->errors[] = ["field" => "password", "message" => "Password must contain at least one special character"];
        }

        $this->addToCollectedErrors();
        return $this->errors;
    }

    public function isValidConfirmPassword($confirmPassword){
        $this->errors = [];

        if (empty($confirmPassword)) {
            $this->errors[] = ["field" => "conpass", "message" => "Confirm Password is required"];
        }

        $this->addToCollectedErrors();
        return $this->errors;
    }
}

?>