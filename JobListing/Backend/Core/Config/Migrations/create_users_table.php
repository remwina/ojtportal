<?php
require_once __DIR__ . '/../DataManagement/DB_Connect.php';
require_once __DIR__ . '/../DataManagement/DB_Operations.php';
require_once __DIR__ . '/../DataManagement/DatabaseSchema.php';

class CreateUsersTable {
    private $dbOps;

    public function __construct($dbOps) {
        $this->dbOps = $dbOps;
    }

    public function up() {
        $conn = $this->dbOps->getConnection();
        
        try {
            // Create tables using schema definitions
            foreach (DatabaseSchema::getTableDefinitions() as $tableName => $definition) {
                if (!$conn->query($definition)) {
                    throw new Exception("Error creating $tableName table: " . $conn->error);
                }
            }

            // Insert departments
            $stmt = $conn->prepare("INSERT INTO departments (name) VALUES (?)");
            foreach (DatabaseSchema::getDepartments() as $dept) {
                $stmt->bind_param('s', $dept);
                $stmt->execute();
            }
            $stmt->close();

            // Insert courses
            $stmt = $conn->prepare("INSERT INTO courses (name, department_id) VALUES (?, ?)");
            foreach (DatabaseSchema::getCourses() as $course) {
                $stmt->bind_param('si', $course[0], $course[1]);
                $stmt->execute();
            }
            $stmt->close();

            echo "All tables created and populated successfully!\n";
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
            throw $e;
        }
    }

    public function down() {
        $conn = $this->dbOps->getConnection();
        
        try {
            // Drop tables in reverse order due to foreign key constraints
            $tables = array_reverse(array_keys(DatabaseSchema::getTableDefinitions()));
            foreach ($tables as $table) {
                $conn->query("DROP TABLE IF EXISTS $table");
            }
            echo "All tables dropped successfully!\n";
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
            throw $e;
        }
    }
}