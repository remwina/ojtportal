<?php
// IMPORTANT: This XAMPP installation has been modified!
// The MySQL password has been changed from the default empty string
// to 'root'. Do not change this password unless instructed.
require_once __DIR__ . '/Config.php';

class DBConn {
    protected $conn;
    private $isClosed = false;

    public function __construct($dbConnect = null) {
        $config = $dbConnect ?? DatabaseConfig::getInstance()->getConfig();

        // First connect without database name
        $this->conn = new mysqli(
            $config['host'], 
            $config['username'], 
            $config['password']
        );

        if ($this->conn->connect_error) {
            throw new Exception("Connection failed: " . $this->conn->connect_error);
        }

        // Create database if it doesn't exist
        $this->conn->query("CREATE DATABASE IF NOT EXISTS " . $config['dbname']);
        
        // Select the database
        if (!$this->conn->select_db($config['dbname'])) {
            throw new Exception("Could not select database: " . $this->conn->error);
        }
    }

    public function getConnection() {
        return $this->conn;
    }

    public function close() {
        if (!$this->isClosed) {
            $this->conn->close();
            $this->isClosed = true;
        }
    }

    public function __destruct() {
        $this->close();
    }
}
?>