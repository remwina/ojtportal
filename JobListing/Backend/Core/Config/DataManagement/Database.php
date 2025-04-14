<?php 

require_once __DIR__ . '/../../../../../vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;

$capsule = new Capsule;

$capsule->addConnection([
    'driver' => 'mysql',
    'host' => 'localhost',
    'database' => 'joblisting',
    'username' => 'root',
    'password' => 'root',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'port' => 3306,
    'options' => [
        \PDO::ATTR_PERSISTENT => false,  // Changed to false to avoid connection issues
        \PDO::ATTR_EMULATE_PREPARES => true,
        \PDO::ATTR_STRINGIFY_FETCHES => false
    ]
]);

// Make this Capsule instance available globally
$capsule->setAsGlobal();

// Setup the Eloquent ORM
$capsule->bootEloquent();

// Enable query log for debugging
$capsule->getConnection()->enableQueryLog();

?>