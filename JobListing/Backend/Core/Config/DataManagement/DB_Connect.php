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

        // Set proper charset and collation for UTF-8
        $this->conn->set_charset('utf8mb4');
        $this->conn->query("SET NAMES utf8mb4");
        $this->conn->query("SET CHARACTER SET utf8mb4");
        $this->conn->query("SET COLLATION_CONNECTION = utf8mb4_unicode_ci");

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

    public static function sanitizeResponse($data) {
        if (is_array($data) || is_object($data)) {
            foreach ($data as &$value) {
                if (is_array($value) || is_object($value)) {
                    $value = self::sanitizeResponse($value);
                } elseif (is_string($value)) {
                    $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
                }
            }
        } elseif (is_string($data)) {
            $data = mb_convert_encoding($data, 'UTF-8', 'UTF-8');
        }
        return $data;
    }
}
?>