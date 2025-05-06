<?php
require_once 'Auth.php';

$auth = new Auth();

if (!$auth->check()) {
    header('Location: Login.php');
    exit();
}

require_once __DIR__ . '/../Backend/Core/Config/DataManagement/DB_Operations.php';

class UsersManager {
    private $conn;

    public function __construct() {
        $db = new SQL_Operations();
        $this->conn = $db->getConnection();
    }

    public function getAllUsers() {
        $result = $this->conn->query("SELECT id, srcode, firstname, lastname, email, usertype, status FROM users");
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}

$manager = new UsersManager();
$users = $manager->getAllUsers();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin - Users</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body>
    <div class="container mt-5">
        <h1>Users</h1>
        <table class="table table-striped mt-4">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>SR Code</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Email</th>
                    <th>User Type</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo htmlspecialchars($user['id']); ?></td>
                    <td><?php echo htmlspecialchars($user['srcode']); ?></td>
                    <td><?php echo htmlspecialchars($user['firstname']); ?></td>
                    <td><?php echo htmlspecialchars($user['lastname']); ?></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td><?php echo htmlspecialchars($user['usertype']); ?></td>
                    <td><?php echo htmlspecialchars($user['status']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <a href="Dashboard.php" class="btn btn-secondary mt-3">Back to Dashboard</a>
    </div>
</body>
</html>
