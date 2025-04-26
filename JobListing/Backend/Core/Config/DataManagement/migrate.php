<?php
require_once __DIR__ . '/DB_Operations.php';
require_once __DIR__ . '/../Migrations/create_users_table.php';

class MigrationManager {
    private $dbOps;
    private $output = [];

    public function __construct() {
        $this->dbOps = new SQL_Operations();
    }

    private function log($message) {
        $this->output[] = $message;
    }

    public function migrate($action = 'up') {
        try {
            $migration = new CreateUsersTable($this->dbOps);
            
            if ($action === 'down') {
                $migration->down();
                $this->log("Migration rolled back");
            } else {
                $migration->up();
                $this->log("Migration completed");
                $this->createDefaultAdmin();
            }
            
            return [
                'success' => true,
                'message' => 'Migration ' . ($action === 'down' ? 'rollback' : 'execution') . ' completed',
                'logs' => $this->output
            ];
        } catch (Exception $e) {
            $this->log($e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'logs' => $this->output
            ];
        }
    }

    private function createDefaultAdmin() {
        try {
            if (!$this->dbOps->checkEmailExists('admin@admin.com')) {
                $adminData = [
                    'usertype' => 'admin',
                    'srcode' => '21-00001',
                    'firstname' => 'Admin',
                    'lastname' => 'User',
                    'email' => 'admin@admin.com',
                    'password' => 'Admin@123',
                    'course' => 1,
                    'section' => 'N/A',
                    'status' => 'active'
                ];

                $result = $this->dbOps->createUser($adminData);
                if (!$result['success']) {
                    $this->log("Failed to create admin user");
                }
            }
        } catch (Exception $e) {
            $this->log("Admin user creation failed: " . $e->getMessage());
        }
    }
}

if (php_sapi_name() === 'cli') {
    $action = isset($argv[1]) ? strtolower($argv[1]) : 'up';
    $manager = new MigrationManager();
    $result = $manager->migrate($action);
    exit($result['success'] ? 0 : 1);
}

if (isset($_SERVER['REQUEST_METHOD'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? 'up';
    $manager = new MigrationManager();
    echo json_encode($manager->migrate($action));
}