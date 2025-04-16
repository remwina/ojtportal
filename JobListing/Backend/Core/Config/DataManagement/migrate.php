<?php

// IMPORTANT: This XAMPP installation has been modified!
// The MySQL password has been changed from the default empty string
// to 'root'. Do not change this password unless instructed.
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/../Migrations/create_users_table.php';
require_once __DIR__ . '/Models/User.php';
require_once __DIR__ . '/DB_Operations.php';

try {
    echo "Running migrations...\n";
    
    // Get the command line argument if it exists
    $action = isset($argv[1]) ? strtolower($argv[1]) : 'up';
    
    // Create database operations instance with correct config
    $dbOps = new SQL_Operations([
        'host' => 'localhost',
        'username' => 'root',
        'password' => 'root',  // Modified XAMPP: Password is 'root', not empty string
        'dbname' => 'joblisting'
    ]);
    
    // Initialize migration with database connection
    $migration = new CreateUsersTable($dbOps);
    
    if ($action === 'down') {
        $migration->down();
        echo "Migration rolled back successfully!\n";
    } else {
        $migration->up();
        echo "Migration completed!\n";
        
        // Create default admin user
        try {
            $adminData = [
                'usertype' => 'admin',
                'srcode' => '21-00001',
                'email' => 'admin@admin.com',
                'password' => 'admin123',
                'status' => 'active'
            ];
            
            if (!$dbOps->checkEmailExists('admin@admin.com') && 
                !$dbOps->checkSRCodeExists('21-00001')) {
                $dbOps->createUser($adminData);
                echo "Default admin user created successfully!\n";
                echo "Email: admin@admin.com\n";
                echo "Password: admin123\n";
            } else {
                echo "Default admin user already exists - skipping creation\n";
            }
        } catch (Exception $e) {
            echo "Warning: Could not create default admin user: " . $e->getMessage() . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
?>