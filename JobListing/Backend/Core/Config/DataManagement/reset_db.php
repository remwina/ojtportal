<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once __DIR__ . '/Database.php';
    require_once __DIR__ . '/DB_Operations.php';

    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    $reset = isset($input['reset']) ? $input['reset'] : false;

    // Initialize database connection
    if ($reset) {
        $result = DB_Operations::setup(true);
        echo json_encode($result);
    } else {
        $result = DB_Operations::setup(false);
        echo json_encode($result);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 