<?php
$servername = "localhost:3307";
$username = "root";
$password = "";
$dbname = "ojt_portal";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>