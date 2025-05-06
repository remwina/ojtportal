<?php
class DatabaseConfig {
    private static $instance = null;
    private $config;

    private function __construct() {
        $this->config = [
            'host' => 'localhost:3307',
            'username' => 'root',
            'password' => '',
            'dbname' => 'joblisting'
        ];
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new DatabaseConfig();
        }
        return self::$instance;
    }

    public function getConfig() {
        return $this->config;
    }

    private function __clone() {}

    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
?>