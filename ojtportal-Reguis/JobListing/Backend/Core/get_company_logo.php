<?php
require_once 'Config/DataManagement/DB_Connect.php';
require_once 'Config/DataManagement/DB_Operations.php';

if (!isset($_GET['id'])) {
    header('HTTP/1.0 400 Bad Request');
    exit('Company ID is required');
}

try {
    $dbOps = new SQL_Operations();
    $conn = $dbOps->getConnection();
    
    $stmt = $conn->prepare("SELECT logo_data, logo_type FROM companies WHERE id = ?");
    $stmt->bind_param("i", $_GET['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row && $row['logo_data'] && $row['logo_type']) {
        // Clear any previous output
        ob_clean();
        
        // Set proper headers
        header('Content-Type: ' . $row['logo_type']);
        header('Content-Length: ' . strlen($row['logo_data']));
        header('Cache-Control: public, max-age=86400');
        
        // Output the image data
        echo $row['logo_data'];
    } else {
        // Return default logo if no logo found
        $defaultLogo = file_get_contents('../../Assets/Images/company_default.png');
        if ($defaultLogo === false) {
            throw new Exception("Default logo file not found");
        }
        
        // Clear any previous output
        ob_clean();
        
        // Set proper headers for default image
        header('Content-Type: image/png');
        header('Content-Length: ' . strlen($defaultLogo));
        header('Cache-Control: public, max-age=86400');
        
        echo $defaultLogo;
    }
} catch (Exception $e) {
    error_log("Error retrieving company logo: " . $e->getMessage());
    
    // Return default logo in case of error
    try {
        $defaultLogo = file_get_contents('../../Assets/Images/company_default.png');
        if ($defaultLogo === false) {
            throw new Exception("Default logo file not found");
        }
        
        // Clear any previous output
        ob_clean();
        
        // Set proper headers for default image
        header('Content-Type: image/png');
        header('Content-Length: ' . strlen($defaultLogo));
        header('Cache-Control: public, max-age=86400');
        
        echo $defaultLogo;
    } catch (Exception $innerE) {
        error_log("Error serving default logo: " . $innerE->getMessage());
        header('HTTP/1.0 500 Internal Server Error');
        exit('Unable to serve image');
    }
}
