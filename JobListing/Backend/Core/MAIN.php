<?php
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/error.log');

require_once __DIR__ . '/Config/DataManagement/DB_Connect.php';
require_once __DIR__ . '/Config/DataManagement/DB_Operations.php';
require_once __DIR__ . '/Security/TokenHandler.php';
require_once __DIR__ . '/../Shell/Login.php';
require_once __DIR__ . '/../Shell/Register.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

try {    
    $action = $_SERVER['REQUEST_METHOD'] === 'POST' ? ($_POST['action'] ?? null) : ($_GET['action'] ?? null);
    $data = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !in_array($action, ['login', 'csrf_init', 'init_db'])) {
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

    try {
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
                    $data['confirm_password'] ?? '',
                    $data['firstname'] ?? '',
                    $data['lastname'] ?? '',
                    $data['course'] ?? '',  // Now expecting course_id
                    $data['section'] ?? ''
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
    
            case 'getDepartments':
                $dbOps = new SQL_Operations();
                $conn = $dbOps->getConnection();
                $result = $conn->query("CALL sp_get_departments()");
                $departments = [];
                while ($row = $result->fetch_assoc()) {
                    $departments[] = $row;
                }
                $response = [
                    'success' => true,
                    'departments' => $departments
                ];
                break;
    
            case 'getCoursesByDepartment':
                if (!isset($data['department_id'])) {
                    throw new Exception("Department ID is required");
                }
                $dbOps = new SQL_Operations();
                $conn = $dbOps->getConnection();
                $stmt = $conn->prepare("CALL sp_get_courses_by_department(?)");
                $stmt->bind_param('i', $data['department_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                $courses = [];
                while ($row = $result->fetch_assoc()) {
                    $courses[] = $row;
                }
                $response = [
                    'success' => true,
                    'courses' => $courses
                ];
                break;
    
            case 'getJobListings':
                $dbOps = new SQL_Operations();
                $conn = $dbOps->getConnection();
                $result = $conn->query("CALL sp_get_job_listings()");
                $jobs = [];
                while ($row = $result->fetch_assoc()) {
                    $jobs[] = $row;
                }
                $response = [
                    'success' => true,
                    'jobs' => $jobs
                ];
                break;
    
            case 'getJobDetails':
                if (!isset($data['job_id'])) {
                    throw new Exception("Job ID is required");
                }
                $dbOps = new SQL_Operations();
                $conn = $dbOps->getConnection();
                $stmt = $conn->prepare("CALL sp_get_job_details(?)");
                $stmt->bind_param('i', $data['job_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                $job = $result->fetch_assoc();
                if (!$job) {
                    throw new Exception("Job not found");
                }
                $response = [
                    'success' => true,
                    'job' => $job
                ];
                break;
    
            case 'applyForJob':
                if (!isset($_SESSION['user_id'])) {
                    throw new Exception("You must be logged in to apply");
                }
                if (!isset($data['job_id'])) {
                    throw new Exception("Job ID is required");
                }
                
                $dbOps = new SQL_Operations();
                $conn = $dbOps->getConnection();
                
                // Check if already applied
                $stmt = $conn->prepare("SELECT id FROM job_applications WHERE user_id = ? AND job_id = ?");
                $stmt->bind_param('ii', $_SESSION['user_id'], $data['job_id']);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    throw new Exception("You have already applied for this job");
                }
                
                // Get user's resume
                $stmt = $conn->prepare("SELECT resume_path FROM student_resumes WHERE user_id = ?");
                $stmt->bind_param('i', $_SESSION['user_id']);
                $stmt->execute();
                $resume = $stmt->get_result()->fetch_assoc();
                if (!$resume) {
                    throw new Exception("Please upload your resume before applying");
                }
                
                // Create application using stored procedure
                $stmt = $conn->prepare("CALL sp_submit_application(?, ?, ?, ?)");
                $coverLetter = $data['cover_letter'] ?? null;
                $stmt->bind_param('iiss', $_SESSION['user_id'], $data['job_id'], $resume['resume_path'], $coverLetter);
                if (!$stmt->execute()) {
                    throw new Exception("Failed to submit application");
                }
                
                $response = [
                    'success' => true,
                    'message' => 'Application submitted successfully'
                ];
                break;
    
            case 'getUserApplications':
                if (!isset($_SESSION['user_id'])) {
                    throw new Exception("You must be logged in to view applications");
                }
                
                $dbOps = new SQL_Operations();
                $conn = $dbOps->getConnection();
                $stmt = $conn->prepare("CALL sp_get_user_applications(?)");
                $stmt->bind_param('i', $_SESSION['user_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                $applications = [];
                while ($row = $result->fetch_assoc()) {
                    $applications[] = $row;
                }
                $response = [
                    'success' => true,
                    'applications' => $applications
                ];
                break;
    
            default:
                throw new Exception("Invalid action: " . $action);
        }
        
        if (!is_array($response)) {
            throw new Exception("Invalid response format");
        }
        
        echo json_encode($response, JSON_THROW_ON_ERROR);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => $e->getMessage()
        ], JSON_THROW_ON_ERROR);
        exit();
    }
    
} catch (Throwable $e) {
    http_response_code(500);
    error_log($e->getMessage());
    echo json_encode([
        "success" => false,
        "message" => "Server error occurred. Please try again."
    ], JSON_THROW_ON_ERROR);
    exit();
}

?>