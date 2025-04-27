<?php
require_once __DIR__ . '/DB_Operations.php';

header('Content-Type: application/json');

try {
    $dbOps = new SQL_Operations();
    $result = $dbOps->resetDatabase();
    echo json_encode($result);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>