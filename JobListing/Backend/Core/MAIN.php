<?php
require_once __DIR__ . '/../Shell/Register.php';
require_once __DIR__ . '/../Shell/Login.php';
require_once __DIR__ . '/Security/TokenHandler.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" || $_SERVER['REQUEST_METHOD'] == "GET") {
    try {
        header('Content-Type: application/json');
        
        $Register = new UserReg();
        $Login = new Login();

        $action = null;
        $data = [];

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            if (isset($_POST['action'])) {
                $action = htmlspecialchars($_POST['action'], ENT_QUOTES, 'UTF-8');
                $data = $_POST;
            } else {
                $jsonData = json_decode(file_get_contents('php://input'), true);
                if ($jsonData === null && json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception("Invalid JSON data received");
                }
                $action = $jsonData['action'] ?? null;
                $data = $jsonData;
            }
        } else {
            $action = isset($_GET['action']) ? htmlspecialchars($_GET['action'], ENT_QUOTES, 'UTF-8') : null;
            $data = $_GET;
        }

        if (empty($action)) {
            throw new Exception("No action specified");
        }

        switch ($action) {
            case 'getToken':
                echo json_encode([
                    "success" => true,
                    "token" => TokenHandler::generateToken()
                ]);
                break;

            case 'register':
                $token = $data['csrf_token'] ?? '';
                if (!TokenHandler::validateToken($token)) {
                    throw new Exception("Invalid security token");
                }

                $usertype = $data['usertype'] ?? null;
                $srcode = $data['srcode'] ?? null;
                $email = $data['email'] ?? null;
                $password = $data['password'] ?? null;
                $conpass = $data['conpass'] ?? null;

                $result = $Register->registerUser($usertype, $srcode, $email, $password, $conpass);
                echo json_encode($result);
                break;

            case 'login':
                $token = $data['csrf_token'] ?? '';
                if (!TokenHandler::validateToken($token)) {
                    throw new Exception("Invalid security token");
                }

                $srcode = $data['srcode'] ?? null;
                $password = $data['password'] ?? null;

                if (empty($srcode) || empty($password)) {
                    throw new Exception("SR Code and password are required");
                }

                $result = $Login->loginUser($srcode, $password);
                echo json_encode($result);
                break;

            case 'checkAdmin':
                session_start();
                $response = array();
                
                if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') {
                    $response['isAdmin'] = true;
                } else {
                    $response['isAdmin'] = false;
                }
                
                echo json_encode($response);
                exit;
                break;

            default:
                throw new Exception("Invalid action: " . $action);
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => $e->getMessage()
        ]);
    } catch (Error $e) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Server error: " . $e->getMessage()
        ]);
    }
}
