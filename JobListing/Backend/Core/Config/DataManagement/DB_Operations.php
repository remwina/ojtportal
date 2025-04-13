<?php 

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/../Migrations/create_users_table.php';
require_once __DIR__ . '/Models/User.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use App\Core\Config\Models\User;

class DB_Operations {
    private static function initializeConnection() {
        try {
            $capsule = new Capsule;
            $capsule->addConnection([
                'driver'    => 'mysql',
                'host'      => 'localhost',
                'database'  => 'joblisting',
                'username'  => 'root',
                'password'  => 'root',  // Changed to match XAMPP default
                'charset'   => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix'    => '',
                'port'      => 3306,
                'options'   => [
                    \PDO::ATTR_PERSISTENT => true,
                    \PDO::ATTR_EMULATE_PREPARES => true,
                    \PDO::ATTR_STRINGIFY_FETCHES => false,
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
                ]
            ]);
            $capsule->setAsGlobal();
            $capsule->bootEloquent();
            return true;
        } catch (\Exception $e) {
            error_log("Database connection error: " . $e->getMessage());
            throw new \Exception("Database connection error: " . $e->getMessage());
        }
    }

    private static function createUserTable() {
        try {
            Capsule::schema()->create('users', function ($table) {
                $table->id();
                $table->string('srcode', 9)->unique();
                $table->string('email')->unique();
                $table->string('password');
                $table->enum('usertype', ['admin', 'user']);
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->timestamps();
                $table->softDeletes();
            });
            return true;
        } catch (\Exception $e) {
            throw new \Exception("Error creating users table: " . $e->getMessage());
        }
    }

    public static function initDatabase() {
        try {
            // First check if MySQL is running
            $socket = @fsockopen('127.0.0.1', 3306, $errno, $errstr, 5);
            if (!$socket) {
                throw new \Exception("MySQL server is not running. Please start MySQL in XAMPP.");
            }
            fclose($socket);

            $pdo = new \PDO(
                "mysql:host=127.0.0.1;port=3306",
                'root',
                'root',  // Updated password
                [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_PERSISTENT => false
                ]
            );
            
            // Try to select the database first
            try {
                $pdo->query("USE joblisting");
                error_log("Database 'joblisting' already exists");
            } catch (\Exception $e) {
                // Database doesn't exist, create it
                error_log("Creating database 'joblisting'");
                $pdo->exec("CREATE DATABASE IF NOT EXISTS joblisting CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            }

            // Initialize the Eloquent connection after database is created
            if (!self::initializeConnection()) {
                throw new \Exception("Failed to initialize database connection");
            }
            
            return [
                "success" => true,
                "message" => "Database initialized successfully"
            ];
        } catch (\Exception $e) {
            error_log("Database initialization error: " . $e->getMessage());
            throw new \Exception("Database initialization error: " . $e->getMessage());
        }
    }

    public static function setup($reset = false) {
        try {
            // Create database if it doesn't exist
            $pdo = new \PDO("mysql:host=localhost", "root", "root");
            $pdo->exec("CREATE DATABASE IF NOT EXISTS joblisting");
            
            // Initialize connection
            $capsule = new Capsule;
            $capsule->addConnection([
                'driver'    => 'mysql',
                'host'      => 'localhost',
                'database'  => 'joblisting',
                'username'  => 'root',
                'password'  => 'root',
                'charset'   => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix'    => '',
            ]);
            $capsule->setAsGlobal();
            $capsule->bootEloquent();

            // Drop tables if reset is true
            if ($reset) {
                Capsule::schema()->dropIfExists('users');
            }

            // Create tables if they don't exist
            if (!Capsule::schema()->hasTable('users')) {
                Capsule::schema()->create('users', function ($table) {
                    $table->id();
                    $table->string('srcode', 9)->unique();
                    $table->string('email')->unique();
                    $table->string('password');
                    $table->enum('usertype', ['admin', 'user']);
                    $table->enum('status', ['active', 'inactive'])->default('active');
                    $table->timestamps();
                    $table->softDeletes();
                });

                // Create default admin user
                self::createDefaultAdmin();
            }

            return [
                "success" => true,
                "message" => $reset ? "Database reset successfully" : "Database created successfully"
            ];
        } catch (\Exception $e) {
            return [
                "success" => false,
                "message" => "Database error: " . $e->getMessage()
            ];
        }
    }

    // User-related operations
    public static function checkEmailExists($email) {
        return User::where('email', $email)->exists();
    }

    public static function checkSRCodeExists($srcode) {
        return User::where('srcode', $srcode)->exists();
    }

    public static function createUser($data) {
        try {
            $user = User::create([
                'usertype' => $data['usertype'],
                'srcode' => $data['srcode'],
                'email' => $data['email'],
                'password' => $data['password'],
                'status' => 'active'
            ]);

            return [
                "success" => true,
                "message" => "Registration Success! Welcome " . ucfirst($data['usertype']) . "!",
                "redirect" => "../Frontend/login.html"
            ];
        } catch (\Exception $e) {
            return [
                "success" => false,
                "errors" => [["field" => "general", "message" => "Database error occurred: " . $e->getMessage()]]
            ];
        }
    }

    public static function findUserBySRCode($srcode) {
        return User::where('srcode', $srcode)
                  ->where('status', 'active')
                  ->whereNull('deleted_at')
                  ->first();
    }

    private static function createDefaultAdmin() {
        try {
            // Check if admin already exists
            if (!self::checkEmailExists('admin@admin.com')) {
                $adminData = [
                    'usertype' => 'admin',
                    'srcode' => 'ADMIN0001',
                    'email' => 'admin@admin.com',
                    'password' => password_hash('admin123', PASSWORD_DEFAULT),
                    'status' => 'active'
                ];
                
                User::create($adminData);
                error_log("Default admin user created successfully");
            }
        } catch (\Exception $e) {
            error_log("Error creating default admin: " . $e->getMessage());
        }
    }
}

?>