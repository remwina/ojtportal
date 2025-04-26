<?php
require_once __DIR__ . '/DB_Operations.php';
require_once __DIR__ . '/DatabaseSchema.php';

header('Content-Type: application/json');

try {
    $dbOps = new SQL_Operations();
    $conn = $dbOps->getConnection();
    
    foreach (array_reverse(array_keys(DatabaseSchema::getTableDefinitions())) as $table) {
        $conn->query("DROP TABLE IF EXISTS $table");
    }
    
    $result = $dbOps->initDatabase();
    echo json_encode([
        'success' => true,
        'message' => 'Database reset and reinitialized successfully'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>