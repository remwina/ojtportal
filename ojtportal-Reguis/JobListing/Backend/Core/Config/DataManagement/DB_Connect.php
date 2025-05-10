<?php
require_once __DIR__ . '/Config.php';

class DBConn {
    protected $conn;
    private $isClosed = false;

    public function __construct($dbConnect = null) {
        $config = $dbConnect ?? DatabaseConfig::getInstance()->getConfig();

        $this->conn = new mysqli(
            $config['host'], 
            $config['username'], 
            $config['password']
        );

        if ($this->conn->connect_error) {
            throw new Exception("Connection failed: " . $this->conn->connect_error);
        }

        $this->conn->query("CREATE DATABASE IF NOT EXISTS " . $config['dbname']);
        
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