<?php

$hostname = "localhost";     
$username = "root";          
$password = "";              
$database = "ojt_portal"; 

$conn = mysqli_connect($hostname, $username, $password, $database);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8");
?>