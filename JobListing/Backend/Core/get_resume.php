<?php
require_once 'Config/DataManagement/DB_Operations.php';

// Ensure there's a resume ID
if (!isset($_GET['id'])) {
    http_response_code(400);
    die('Resume ID is required');
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
    }    // Set the content type
    header('Content-Type: ' . $resume['resume_type']);
    
    // Set content disposition based on whether it's a download request
    if (isset($_GET['download'])) {
        header('Content-Disposition: attachment; filename="' . $resume['resume_name'] . '"');
    } else {
        header('Content-Disposition: inline; filename="' . $resume['resume_name'] . '"');
    }
    
    // Output the resume data
    echo $resume['resume_data'];
} catch (Exception $e) {
    http_response_code(500);
    die('Error retrieving resume: ' . $e->getMessage());
}
