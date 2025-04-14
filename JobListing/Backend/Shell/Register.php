<?php
require_once __DIR__ . '/../Core/Config/DataManagement/Database.php';
require_once __DIR__ . '/../Core/Validators.php';
require_once __DIR__ . '/../Core/REGEX.php';
require_once __DIR__ . '/../Core/Config/DataManagement/DB_Operations.php';

class UserReg {
    private $validator;

    public function __construct() {
        $this->validator = new Validators();
    }

    public function registerUser($usertype, $srcode, $email, $password, $conpass = null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->validator->clearAllErrors();

        $this->validator->isValidUsertype($usertype);
        $this->validator->isValidSRCode($srcode);
        $this->validator->isValidEmail($email);
        $this->validator->isValidPassword($password);
        $this->validator->isValidConfirmPassword($conpass);

        if ($conpass !== null && $password !== $conpass) {
            return [
                "success" => false,
                "errors" => [["field" => "conpass", "message" => "Passwords do not match"]]
            ];
        }

        $validationErrors = $this->validator->getErrors();
        if (!$validationErrors['success']) {
            return $validationErrors;
        }

        if (DB_Operations::checkEmailExists($email)) {
            return [
                "success" => false,
                "errors" => [["field" => "email", "message" => "Email already exists"]]
            ];
        }

        if (DB_Operations::checkSRCodeExists($srcode)) {
            return [
                "success" => false,
                "errors" => [["field" => "srcode", "message" => "SR Code already exists"]]
            ];
        }

        return DB_Operations::createUser([
            'usertype' => $usertype,
            'srcode' => $srcode,
            'email' => $email,
            'password' => $password
        ]);
    }
}
?>