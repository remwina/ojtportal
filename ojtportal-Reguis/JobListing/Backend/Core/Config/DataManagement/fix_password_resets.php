<?php
require_once 'DB_Operations.php';

try {
    $dbOps = new SQL_Operations();
    $conn = $dbOps->getConnection();
    
    // First check if the column exists
    $result = $conn->query("SHOW COLUMNS FROM password_resets LIKE 'used'");
    if ($result->num_rows === 0) {
        // Add the column if it doesn't exist
        $sql = "ALTER TABLE password_resets ADD COLUMN used TINYINT(1) DEFAULT 0";
        if ($conn->query($sql)) {
            echo "Successfully added 'used' column to password_resets table";
        } else {
            echo "Error adding column: " . $conn->error;
        }
    } else {
        echo "'used' column already exists in password_resets table";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>