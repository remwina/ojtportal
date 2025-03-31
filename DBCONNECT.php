<?php
// Database configuration
$hostname = "localhost";      // Your database host
$username = "root";          // Your database username
$password = "";              // Your database password
$database = "ojt_portal";    // Your database name

// Create database connection
$conn = mysqli_connect($hostname, $username, $password, $database);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset to UTF-8
mysqli_set_charset($conn, "utf8");
?>