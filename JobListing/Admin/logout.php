<?php
require_once 'Auth.php';

$auth = new Auth();
$auth->logout();

header('Location: ../Frontend/login.html');
exit();
?>
