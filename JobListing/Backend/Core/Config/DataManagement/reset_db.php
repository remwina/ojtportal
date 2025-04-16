<?php
require_once __DIR__ . '/DB_Operations.php';

try {
    $dbOps = new SQL_Operations();
    $conn = $dbOps->getConnection();
    
    // Drop users table
    $conn->query("DROP TABLE IF EXISTS users");
    
    // Reinitialize database with fresh tables and default admin
    $result = $dbOps->initDatabase();
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Database has been reset and reinitialized with default admin user'
    ]);

} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>