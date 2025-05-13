<?php
require_once 'Config/DataManagement/DB_Operations.php';

// Clean any existing output
if (ob_get_level()) ob_end_clean();

// Ensure there's a resume ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    die('Valid resume ID is required');
}

try {
    $dbOps = new SQL_Operations();
    $conn = $dbOps->getConnection();
    
    // Get the resume data
    $stmt = $conn->prepare("SELECT resume_data, resume_type, resume_name FROM student_resumes WHERE id = ?");
    $stmt->bind_param('i', $_GET['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $resume = $result->fetch_assoc();

    if (!$resume) {
        http_response_code(404);
        die('Resume not found');
    }

    // Check if resume data exists
    if (empty($resume['resume_data'])) {
        http_response_code(404);
        die('Resume data is empty');
    }

    // Check if resume type is valid
    if (empty($resume['resume_type'])) {
        $resume['resume_type'] = 'application/pdf'; // Default to PDF if type is missing
    }

    // Set headers
    header('Content-Type: ' . $resume['resume_type']);
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    
    // Set content disposition based on whether it's a download request
    $filename = $resume['resume_name'] ?? 'resume.pdf';
    if (isset($_GET['download'])) {
        header('Content-Disposition: attachment; filename="' . $filename . '"');
    } else {
        header('Content-Disposition: inline; filename="' . $filename . '"');
    }

    // Output the resume data
    echo $resume['resume_data'];
    exit();

} catch (Exception $e) {
    error_log('Error retrieving resume: ' . $e->getMessage());
    http_response_code(500);
    die('Error retrieving resume. Please try again later.');
}
