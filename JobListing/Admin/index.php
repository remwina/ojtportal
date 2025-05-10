<?php
require_once 'Auth.php';

$auth = new Auth();

if ($auth->check()) {
    if ($auth->usertype() === 'admin') {
        header('Location: Dashboard.php');
    } else {
        // Redirect non-admin users to frontend or user dashboard
        header('Location: ../Frontend/index.html');
    }
} else {
    header('Location: ../Frontend/login.html');
}
exit();
?>
