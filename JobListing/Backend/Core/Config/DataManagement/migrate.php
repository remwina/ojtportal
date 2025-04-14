<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/../Migrations/create_users_table.php';
require_once __DIR__ . '/Models/User.php';

use App\Core\Config\Models\User;

try {
    echo "Running migrations...\n";
    
    // Get the command line argument if it exists
    $action = isset($argv[1]) ? strtolower($argv[1]) : 'up';
    
    // Run the users table migration
    $migration = new CreateUsersTable();
    
    if ($action === 'down') {
        $migration->down();
        echo "Migration rolled back successfully!\n";
    } else {
        $migration->up();
        echo "Migration completed!\n";
        
        // Create default admin user
        try {
            // Check if admin already exists by either email OR srcode
            if (!User::where('email', 'admin@admin.com')->exists() && 
                !User::where('srcode', '21-00001')->exists()) {
                $adminData = [
                    'usertype' => 'admin',
                    'srcode' => '21-00001',
                    'email' => 'admin@admin.com',
                    'password' => password_hash('admin123', PASSWORD_DEFAULT),
                    'status' => 'active'
                ];
                
                User::create($adminData);
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