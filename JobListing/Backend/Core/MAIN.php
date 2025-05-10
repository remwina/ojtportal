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
    
    // Skip CSRF check for these actions
    $skipCSRFCheck = ['login', 'register', 'getCSRFToken', 'checkLastUpdate'];
    
    // Validate CSRF token for POST requests except skipped actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !in_array($action, $skipCSRFCheck)) {
        // Check for token in headers (convert header name to uppercase as per PHP standard)
        $headers = array_change_key_case(getallheaders(), CASE_UPPER);
        $token = $_POST['csrf_token'] ?? $headers['X-CSRF-TOKEN'] ?? null;
        
        if (!$token) {
            throw new Exception("CSRF token missing");
        }
        if (!TokenHandler::verifyToken($token)) {
            throw new Exception("Invalid security token");
        }
    }

    if (!$action) {
        throw new Exception("No action specified");
    }
    
    $response = null;

    switch($action) {
        case 'getCSRFToken':
            $token = TokenHandler::generateToken();
            $response = [
                'success' => true,
                'token' => $token
            ];
            break;

        case 'csrf_init':
            $token = TokenHandler::generateToken();
            $response = [
                'success' => true,
                'token' => $token
            ];
            break;

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
                $data['course'] ?? '',
                $data['section'] ?? ''
            );
            
            if ($response['success']) {
                $response['csrf_token'] = TokenHandler::generateToken();
            }
            break;

        case 'forgot_password':
            require_once __DIR__ . '/../Shell/PasswordReset.php';
            $resetHandler = new PasswordReset();
            $response = $resetHandler->initiateReset($data['email'] ?? '');
            break;

        case 'reset_password':
            require_once __DIR__ . '/../Shell/PasswordReset.php';
            $resetHandler = new PasswordReset();
            $response = $resetHandler->resetPassword(
                $data['token'] ?? '',
                $data['password'] ?? ''
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
                'success' => true,
                'isAdmin' => isset($_SESSION['usertype']) && $_SESSION['usertype'] === 'admin'
            ];
            break;

        case 'checkAuth':
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            $response = [
                'success' => true,
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
            if (!isset($data['id'])) {
                throw new Exception("Job ID is required");
            }
            $dbOps = new SQL_Operations();
            $conn = $dbOps->getConnection();
            $stmt = $conn->prepare("CALL sp_get_job_details(?)");
            $stmt->bind_param('i', $data['id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $job = $result->fetch_assoc();
            if (!$job) {
                throw new Exception("Job listing not found");
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

        case 'getJobListing':
            if (!isset($_GET['id'])) {
                throw new Exception("Job ID is required");
            }
            $dbOps = new SQL_Operations();
            $conn = $dbOps->getConnection();
            $stmt = $conn->prepare("SELECT * FROM job_listings WHERE id = ?");
            $stmt->bind_param("i", $_GET['id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $response = [
                'success' => true,
                'data' => $result->fetch_assoc()
            ];
            break;

        case 'addJobListing':
            if (!isset($_POST['title'], $_POST['company_id'], $_POST['description'], $_POST['location'], $_POST['job_type'], $_POST['slots'], $_POST['status'])) {
                throw new Exception("Missing required fields");
            }
            
            try {
                // Validate company exists
                $dbOps = new SQL_Operations();
                $conn = $dbOps->getConnection();
                
                $checkStmt = $conn->prepare("SELECT id FROM companies WHERE id = ? AND status = 'active'");
                if (!$checkStmt) {
                    throw new Exception("Database error: " . $conn->error);
                }
                
                $checkStmt->bind_param("i", $_POST['company_id']);
                if (!$checkStmt->execute()) {
                    throw new Exception("Error checking company: " . $checkStmt->error);
                }
                
                $result = $checkStmt->get_result();
                if (!$result->fetch_assoc()) {
                    throw new Exception("Invalid or inactive company selected");
                }
                
                // Validate job type
                $validTypes = ['full-time', 'part-time', 'internship'];
                if (!in_array($_POST['job_type'], $validTypes)) {
                    throw new Exception("Invalid job type");
                }

                // Validate status
                $validStatuses = ['open', 'closed', 'draft'];
                if (!in_array($_POST['status'], $validStatuses)) {
                    throw new Exception("Invalid status");
                }

                // Validate slots
                if (!is_numeric($_POST['slots']) || $_POST['slots'] < 1) {
                    throw new Exception("Number of slots must be at least 1");
                }

                // Validate expiry date if provided
                if (!empty($_POST['expires_at'])) {
                    $expiryDate = new DateTime($_POST['expires_at']);
                    $today = new DateTime();
                    if ($expiryDate < $today) {
                        throw new Exception("Expiry date must be in the future");
                    }
                }
            } catch (Exception $e) {
                error_log("Job listing validation error: " . $e->getMessage());
                throw $e;
            }

            $stmt = $conn->prepare("CALL sp_add_job_listing(?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $expiresAt = isset($_POST['expires_at']) && !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;
            $requirements = isset($_POST['requirements']) ? $_POST['requirements'] : '';
            
            $stmt->bind_param("isssssiss", 
                $_POST['company_id'],
                $_POST['title'],
                $_POST['description'],
                $requirements,
                $_POST['location'],
                $_POST['job_type'],
                $_POST['slots'],
                $_POST['status'],
                $expiresAt
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to add job listing: " . $conn->error);
            }
            $result = $stmt->get_result();
            $jobId = $result->fetch_assoc()['job_id'];
            $response = [
                'success' => true,
                'job_id' => $jobId,
                'message' => 'Job listing added successfully'
            ];
            error_log("Job listing added successfully: ID " . $jobId);
            break;

        case 'updateJobListing':
            if (!isset($_POST['id'], $_POST['title'], $_POST['description'], $_POST['location'], $_POST['job_type'], $_POST['slots'], $_POST['status'])) {
                throw new Exception("Missing required fields");
            }
            
            $dbOps = new SQL_Operations();
            $conn = $dbOps->getConnection();

            // Check if job listing exists
            $checkStmt = $conn->prepare("SELECT id FROM job_listings WHERE id = ?");
            $checkStmt->bind_param("i", $_POST['id']);
            $checkStmt->execute();
            if (!$checkStmt->get_result()->fetch_assoc()) {
                throw new Exception("Job listing not found");
            }

            // Validate job type
            $validTypes = ['full-time', 'part-time', 'internship'];
            if (!in_array($_POST['job_type'], $validTypes)) {
                throw new Exception("Invalid job type");
            }

            // Validate status
            $validStatuses = ['open', 'closed', 'draft'];
            if (!in_array($_POST['status'], $validStatuses)) {
                throw new Exception("Invalid status");
            }

            // Validate slots
            if (!is_numeric($_POST['slots']) || $_POST['slots'] < 1) {
                throw new Exception("Number of slots must be at least 1");
            }

            // Validate expiry date if provided
            if (!empty($_POST['expires_at'])) {
                $expiryDate = new DateTime($_POST['expires_at']);
                $today = new DateTime();
                if ($expiryDate < $today) {
                    throw new Exception("Expiry date must be in the future");
                }
            }

            $stmt = $conn->prepare("CALL sp_update_job_listing(?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $expiresAt = isset($_POST['expires_at']) && !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;
            $requirements = isset($_POST['requirements']) ? $_POST['requirements'] : '';

            $stmt->bind_param("isssssiss",
                $_POST['id'],
                $_POST['title'],
                $_POST['description'],
                $requirements,
                $_POST['location'],
                $_POST['job_type'],
                $_POST['slots'],
                $_POST['status'],
                $expiresAt
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to update job listing: " . $conn->error);
            }
            $response = [
                'success' => true,
                'message' => 'Job listing updated successfully'
            ];
            break;

        case 'addCompany':
            if (!isset($_POST['name'], $_POST['address'])) {
                throw new Exception("Company name and address are required");
            }
            $dbOps = new SQL_Operations();
            $conn = $dbOps->getConnection();
            
            // Handle logo upload if present
            $logoData = null;
            $logoType = null;
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $logoData = file_get_contents($_FILES['logo']['tmp_name']);
                $logoType = $_FILES['logo']['type'];
            }

            $stmt = $conn->prepare("CALL sp_admin_add_company(?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssss", 
                $_POST['name'],
                $_POST['address'],
                $_POST['contact_person'],
                $_POST['contact_email'],
                $_POST['contact_phone'],
                $_POST['website'],
                $_POST['description'],
                $logoData,
                $logoType
            );
            if (!$stmt->execute()) {
                throw new Exception("Failed to add company: " . $conn->error);
            }
            $result = $stmt->get_result();
            $companyId = $result->fetch_assoc()['company_id'];
            $response = [
                'success' => true,
                'company_id' => $companyId,
                'message' => 'Company added successfully'
            ];
            break;

        case 'updateCompany':
            if (!isset($_POST['id'], $_POST['name'], $_POST['address'])) {
                throw new Exception("Missing required fields");
            }
            $dbOps = new SQL_Operations();
            $conn = $dbOps->getConnection();

            // Handle logo upload if present
            $logoData = null;
            $logoType = null;
            $hasNewLogo = false;
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $logoData = file_get_contents($_FILES['logo']['tmp_name']);
                $logoType = $_FILES['logo']['type'];
                
                // Validate image
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!in_array($logoType, $allowedTypes)) {
                    throw new Exception("Invalid logo format. Only JPG, PNG and GIF are allowed.");
                }
                
                // Set flag that we have a new logo
                $hasNewLogo = true;
            }

            // Make sure to set the character set to handle UTF-8
            $conn->set_charset('utf8mb4');
            
            // Prepare the SQL query based on whether we have a new logo
            if ($hasNewLogo) {
                $sql = "UPDATE companies SET 
                        name = ?, 
                        address = ?,
                        contact_person = ?,
                        contact_email = ?,
                        contact_phone = ?,
                        website = ?,
                        description = ?,
                        logo_data = ?,
                        logo_type = ?,
                        status = ?,
                        updated_at = CURRENT_TIMESTAMP
                        WHERE id = ?";
            } else {
                $sql = "UPDATE companies SET 
                        name = ?, 
                        address = ?,
                        contact_person = ?,
                        contact_email = ?,
                        contact_phone = ?,
                        website = ?,
                        description = ?,
                        status = ?,
                        updated_at = CURRENT_TIMESTAMP
                        WHERE id = ?";
            }
            
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Error preparing statement: " . $conn->error);
            }
            
            $status = $_POST['status'] ?? 'active';
            
            if ($hasNewLogo) {
                $stmt->bind_param("ssssssssssi", 
                    $_POST['name'],
                    $_POST['address'],
                    $_POST['contact_person'],
                    $_POST['contact_email'],
                    $_POST['contact_phone'],
                    $_POST['website'],
                    $_POST['description'],
                    $logoData,
                    $logoType,
                    $status,
                    $_POST['id']
                );
            } else {
                $stmt->bind_param("sssssssssi", 
                    $_POST['name'],
                    $_POST['address'],
                    $_POST['contact_person'],
                    $_POST['contact_email'],
                    $_POST['contact_phone'],
                    $_POST['website'],
                    $_POST['description'],
                    $status,
                    $_POST['id']
                );
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to update company: " . $conn->error);
            }
            
            $response = [
                'success' => true,
                'message' => 'Company updated successfully'
            ];
            break;

        case 'updateApplicationStatus':
            if (!isset($_POST['id'], $_POST['status'])) {
                throw new Exception("Application ID and status are required");
            }
            $dbOps = new SQL_Operations();
            $conn = $dbOps->getConnection();
            $stmt = $conn->prepare("CALL sp_admin_update_application_status(?, ?)");
            $stmt->bind_param("is", $_POST['id'], $_POST['status']);
            if (!$stmt->execute()) {
                throw new Exception("Failed to update application status: " . $conn->error);
            }
            $response = [
                'success' => true,
                'message' => 'Application status updated successfully'
            ];
            break;

        case 'getDashboardStats':
            $dbOps = new SQL_Operations();
            $conn = $dbOps->getConnection();
            $stmt = $conn->prepare("CALL sp_admin_get_dashboard_stats()");
            if (!$stmt->execute()) {
                throw new Exception("Failed to get dashboard stats: " . $conn->error);
            }
            $result = $stmt->get_result();
            $stats = $result->fetch_assoc();
            $response = [
                'success' => true,
                'stats' => $stats
            ];
            break;

        case 'deleteJobListing':
            if (!isset($_POST['id'])) {
                throw new Exception("Job ID is required");
            }
            
            $dbOps = new SQL_Operations();
            $conn = $dbOps->getConnection();

            // Check if job listing exists
            $checkStmt = $conn->prepare("SELECT id FROM job_listings WHERE id = ?");
            $checkStmt->bind_param("i", $_POST['id']);
            $checkStmt->execute();
            if (!$checkStmt->get_result()->fetch_assoc()) {
                throw new Exception("Job listing not found");
            }

            // Check if job listing has any applications
            $appStmt = $conn->prepare("SELECT COUNT(*) as count FROM job_applications WHERE job_id = ?");
            $appStmt->bind_param("i", $_POST['id']);
            $appStmt->execute();
            $result = $appStmt->get_result();
            $count = $result->fetch_assoc()['count'];
            
            if ($count > 0) {
                throw new Exception("Cannot delete job listing that has applications");
            }

            // Safe to delete
            $stmt = $conn->prepare("DELETE FROM job_listings WHERE id = ?");
            $stmt->bind_param("i", $_POST['id']);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to delete job listing");
            }
            
            $response = [
                'success' => true,
                'message' => 'Job listing deleted successfully'
            ];
            break;

        case 'deleteApplication':
            if (!isset($_POST['id'])) {
                throw new Exception("Application ID is required");
            }
            $dbOps = new SQL_Operations();
            $conn = $dbOps->getConnection();
            $stmt = $conn->prepare("DELETE FROM job_applications WHERE id = ?");
            $stmt->bind_param("i", $_POST['id']);
            $response = [
                'success' => $stmt->execute()
            ];
            break;

        case 'getCompany':
            $companyId = $_POST['id'] ?? $_GET['id'] ?? null;
            if (!$companyId) {
                throw new Exception("Company ID is required");
            }
            $dbOps = new SQL_Operations();
            $conn = $dbOps->getConnection();
            
            // Set UTF-8 character set
            $conn->set_charset('utf8mb4');
            
            $stmt = $conn->prepare("SELECT * FROM companies WHERE id = ?");
            if (!$stmt) {
                throw new Exception("Database error: " . $conn->error);
            }
            
            $stmt->bind_param("i", $companyId);
            if (!$stmt->execute()) {
                throw new Exception("Database error: " . $stmt->error);
            }
            
            $result = $stmt->get_result();
            $company = $result->fetch_assoc();
            
            if (!$company) {
                throw new Exception("Company not found");
            }
            
            // Remove any potential invalid UTF-8 characters
            array_walk_recursive($company, function(&$value) {
                if (is_string($value)) {
                    $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
                }
            });
            
            $response = [
                'success' => true,
                'data' => $company
            ];
            break;

        case 'deleteCompany':
            if (!isset($_POST['id'])) {
                throw new Exception("Company ID is required");
            }
            $dbOps = new SQL_Operations();
            $conn = $dbOps->getConnection();
            
            // First check if company exists
            $stmt = $conn->prepare("SELECT id FROM companies WHERE id = ?");
            $stmt->bind_param("i", $_POST['id']);
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                throw new Exception("Company not found");
            }
            
            // Now delete the company
            $stmt = $conn->prepare("DELETE FROM companies WHERE id = ?");
            $stmt->bind_param("i", $_POST['id']);
            if (!$stmt->execute()) {
                throw new Exception("Failed to delete company: " . $conn->error);
            }
            
            $response = [
                'success' => true,
                'message' => 'Company deleted successfully'
            ];
            break;

        case 'getUserDetails':
            if (!isset($_GET['id'])) {
                throw new Exception("User ID is required");
            }
            $dbOps = new SQL_Operations();
            $conn = $dbOps->getConnection();
            $stmt = $conn->prepare("SELECT u.*, c.name as course_name, d.name as department_name 
                                  FROM users u 
                                  LEFT JOIN courses c ON u.course_id = c.id 
                                  LEFT JOIN departments d ON c.department_id = d.id 
                                  WHERE u.id = ?");
            $stmt->bind_param("i", $_GET['id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $response = [
                'success' => true,
                'data' => $result->fetch_assoc()
            ];
            break;

        case 'updateUserStatus':
            if (!isset($_POST['id'], $_POST['status'])) {
                throw new Exception("Missing required fields");
            }
            $dbOps = new SQL_Operations();
            $conn = $dbOps->getConnection();
            $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $_POST['status'], $_POST['id']);
            $result = $stmt->execute();
            if (!$result) {
                error_log("Failed to update user status: " . $conn->error);
            }
            $response = [
                'success' => $result,
                'message' => $result ? 'User status updated successfully' : 'Failed to update user status'
            ];
            break;

        case 'changeAdminPassword':
            if (!isset($_SESSION['admin_id'])) {
                throw new Exception("Unauthorized access");
            }

            if (!isset($_POST['currentPassword'], $_POST['newPassword'], $_POST['confirmPassword'])) {
                throw new Exception("Missing required fields");
            }

            $currentPassword = $_POST['currentPassword'];
            $newPassword = $_POST['newPassword'];
            $confirmPassword = $_POST['confirmPassword'];

            // Validate new password
            $validator = new Validators();
            $validator->isValidPassword($newPassword, $confirmPassword);
            $validationResult = $validator->getErrors();
            
            if (!$validationResult['success']) {
                throw new Exception($validationResult['errors'][0]['message']);
            }

            // Verify current password
            $dbOps = new SQL_Operations();
            $conn = $dbOps->getConnection();
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->bind_param("i", $_SESSION['admin_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $userData = $result->fetch_assoc();

            if (!password_verify($currentPassword, $userData['password'])) {
                throw new Exception("Current password is incorrect");
            }

            // Update password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashedPassword, $_SESSION['admin_id']);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to update password");
            }

            $response = [
                'success' => true,
                'message' => 'Password updated successfully'
            ];
            break;

        case 'forcePasswordReset':
            if (!isset($_SESSION['admin_id'])) {
                throw new Exception("Unauthorized access");
            }

            if (!isset($_POST['user_id'])) {
                throw new Exception("User ID is required");
            }

            $dbOps = new SQL_Operations();
            $conn = $dbOps->getConnection();
            $stmt = $conn->prepare("CALL sp_admin_force_password_reset(?)");
            $stmt->bind_param("i", $_POST['user_id']);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to force password reset");
            }

            $response = [
                'success' => true,
                'message' => 'Password reset has been forced for this user'
            ];
            break;

        default:
            throw new Exception("Invalid action: " . $action);
    }

    // Handle checkLastUpdate action
    if ($action === 'checkLastUpdate') {
        $response = [
            'success' => true,
            'lastUpdate' => time()
        ];
    }

    if (!is_array($response)) {
        throw new Exception("Invalid response format");
    }

    // Include fresh CSRF token in all successful responses
    if ($response['success']) {
        $response['csrf_token'] = TokenHandler::generateToken();
    }

    echo json_encode($response, JSON_THROW_ON_ERROR);

} catch (Exception $e) {
    error_log($e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_THROW_ON_ERROR);
    exit();
} catch (Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => "Server error occurred. Please try again."
    ], JSON_THROW_ON_ERROR);
    exit();
}

?>