<?php
require_once __DIR__ . '/Config/DataManagement/DB_Connect.php';
require_once __DIR__ . '/Config/DataManagement/DB_Operations.php';
require_once __DIR__ . '/Security/TokenHandler.php';
require_once __DIR__ . '/../Shell/Login.php';
require_once __DIR__ . '/../Shell/Register.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

try {    
    $action = $_SERVER['REQUEST_METHOD'] === 'POST' ? ($_POST['action'] ?? null) : ($_GET['action'] ?? null);
    $data = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !in_array($action, ['login', 'csrf_init'])) {
        if (!isset($_POST['csrf_token'])) {
            throw new Exception("CSRF token missing");
        }
        if (!TokenHandler::verifyToken($_POST['csrf_token'])) {
            throw new Exception("Invalid security token");
        }
    }

    if (!$action) {
        throw new Exception("No action specified");
    }
    
    $response = null;
    
    switch($action) {
        case 'init_db':
            try {
                $dbOps = new SQL_Operations();
                $result = $dbOps->initDatabase();
                $response = [
                    "success" => true,
                    "message" => "Database initialized successfully"
                ];
            } catch (Exception $e) {
                throw new Exception("Database initialization failed: " . $e->getMessage());
            }
            break;
            
        case 'csrf_init':
            $response = [
                'success' => true,
                'csrf_token' => TokenHandler::generateToken()
            ];
            break;
            
        case 'csrf':
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $response = [
                    'success' => true,
                    'csrf_token' => TokenHandler::generateToken()
                ];
            } else {
                throw new Exception("CSRF token can only be requested via GET");
            }
            break;
            
        case 'login':
            $loginShell = new Login();
            $response = $loginShell->loginUser(
                $data['email'] ?? '',
                $data['password'] ?? ''
            );
            
            if ($response['success']) {
                $response['csrf_token'] = TokenHandler::generateToken();
            }
            break;
            
        case 'register':
            $registerShell = new UserReg();
            $response = $registerShell->registerUser(
                $data['usertype'] ?? 'none',
                $data['srcode'] ?? '',
                $data['email'] ?? '',
                $data['password'] ?? '',
                $data['confirm_password'] ?? ''
            );
            
            if ($response['success']) {
                $response['csrf_token'] = TokenHandler::generateToken();
            }
            break;
            
        case 'checkAdmin':
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            $response = [
                'isAdmin' => isset($_SESSION['usertype']) && $_SESSION['usertype'] === 'admin'
            ];
            break;

        case 'checkAuth':
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            $response = [
                'isAuthenticated' => isset($_SESSION['user_id']),
                'usertype' => $_SESSION['usertype'] ?? null
            ];
            break;
            
        default:
            throw new Exception("Invalid action: " . $action);
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>
