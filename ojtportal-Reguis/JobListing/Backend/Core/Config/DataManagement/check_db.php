<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'DB_Operations.php';

try {
    $dbOps = new SQL_Operations();
    $conn = $dbOps->getConnection();
    
    if (!$conn) {
        throw new Exception("Could not connect to database");
    }

    echo "Connected to database successfully!\n";

    $result = $conn->query("SHOW COLUMNS FROM job_listings");
    if (!$result) {
        throw new Exception("Error getting table structure: " . $conn->error);
    }

    echo "\nJob Listings table structure:\n";
    echo "-----------------------------\n";
    while ($row = $result->fetch_assoc()) {
        echo "{$row['Field']} - {$row['Type']} - {$row['Null']} - {$row['Default']}\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    if (isset($conn)) {
        echo "MySQL Error: " . $conn->error . "\n";
    }
    exit(1);
}
?>
