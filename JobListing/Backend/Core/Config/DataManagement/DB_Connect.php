<?php
require_once __DIR__ . '/Config.php';

class DBConn {
    private static $instance = null;
    private static $conn = null;
    private static $isClosed = false;

    private function __construct($dbConnect = null) {
        if (self::$conn !== null) {
            return;
        }

        $config = $dbConnect ?? DatabaseConfig::getInstance()->getConfig();

        self::$conn = new mysqli(
            $config['host'], 
            $config['username'], 
            $config['password']
        );

        if (self::$conn->connect_error) {
            throw new Exception("Connection failed: " . self::$conn->connect_error);
        }

        // Set proper charset and collation for UTF-8
        self::$conn->set_charset('utf8mb4');
        self::$conn->query("SET NAMES utf8mb4");
        self::$conn->query("SET CHARACTER SET utf8mb4");
        self::$conn->query("SET COLLATION_CONNECTION = utf8mb4_unicode_ci");

        self::$conn->query("CREATE DATABASE IF NOT EXISTS " . $config['dbname']);
        
        if (!self::$conn->select_db($config['dbname'])) {
            throw new Exception("Could not select database: " . self::$conn->error);
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        if (self::$isClosed || self::$conn === null) {
            throw new Exception("Attempting to use a closed database connection");
        }
        return self::$conn;
    }

    public function close() {
        if (!self::$isClosed && self::$conn !== null) {
            self::$conn->close();
            self::$conn = null;
            self::$isClosed = true;
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