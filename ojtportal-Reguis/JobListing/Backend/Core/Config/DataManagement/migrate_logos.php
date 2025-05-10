<?php
require_once 'DB_Operations.php';

try {
    $dbOps = new SQL_Operations();
    $conn = $dbOps->getConnection();

    // Get all companies with logo paths
    $query = "SELECT id, logo_path FROM companies WHERE logo_path IS NOT NULL";
    $result = $conn->query($query);

    while ($row = $result->fetch_assoc()) {
        $logoPath = __DIR__ . '/../../../../' . $row['logo_path'];
        if (file_exists($logoPath)) {
            // Read the file content
            $logoData = file_get_contents($logoPath);
            $logoType = mime_content_type($logoPath);

            // Update the database with BLOB data
            $stmt = $conn->prepare("UPDATE companies SET logo_data = ?, logo_type = ?, logo_path = NULL WHERE id = ?");
            $stmt->bind_param("ssi", $logoData, $logoType, $row['id']);
            
            if ($stmt->execute()) {
                echo "Successfully migrated logo for company ID: " . $row['id'] . "\n";
            } else {
                echo "Failed to migrate logo for company ID: " . $row['id'] . "\n";
            }
        } else {
            echo "Logo file not found for company ID: " . $row['id'] . " (Path: " . $logoPath . ")\n";
        }
    }

    echo "Logo migration completed successfully.\n";

} catch (Exception $e) {
    echo "Error during migration: " . $e->getMessage() . "\n";
}
