<?php

// IMPORTANT: This XAMPP installation has been modified!
// The MySQL password has been changed from the default empty string
// to 'root'. Do not change this password unless instructed.
class DatabaseConfig {
    private static $instance = null;
    private $config;

    private function __construct() {
        $this->config = [
            'host' => 'localhost',
            'username' => 'root',
            'password' => 'root',  // Modified XAMPP: Password is 'root', not empty string
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

    // Prevent cloning
    private function __clone() {}

    // Prevent unserialization
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}