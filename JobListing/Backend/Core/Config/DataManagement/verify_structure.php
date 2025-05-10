<?php
require_once 'DB_Operations.php';

try {
    $dbOps = new SQL_Operations();
    $conn = $dbOps->getConnection();

    // Get table structure
    $result = $conn->query("SHOW COLUMNS FROM job_listings");
    if (!$result) {
        throw new Exception("Error getting table structure: " . $conn->error);
    }

    echo "Job Listings table structure:\n";
    echo "-----------------------------\n";
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
