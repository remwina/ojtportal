<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/../Migrations/create_users_table.php';
require_once __DIR__ . '/Models/User.php';
require_once __DIR__ . '/DB_Operations.php';

try {
    echo "Running migrations...\n";
    
    $action = isset($argv[1]) ? strtolower($argv[1]) : 'up';
    
    $dbOps = new SQL_Operations([
        'host' => 'localhost',
        'username' => 'root',
        'password' => 'root',
        'dbname' => 'joblisting'
    ]);
    
    $migration = new CreateUsersTable($dbOps);
    
    if ($action === 'down') {
        $migration->down();
        echo "Migration rolled back successfully!\n";
    } else {
        $migration->up();
        echo "Migration completed!\n";
        
        try {
            $adminData = [
                'usertype' => 'admin',
                'srcode' => '21-00001',
                'email' => 'admin@admin.com',
                'password' => 'Admin@123',
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