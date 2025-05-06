<?php
require_once 'Auth.php';

$auth = new Auth();

if (!$auth->check()) {
    header('Location: Login.php');
    exit();
}

require_once __DIR__ . '/../Backend/Core/Config/DataManagement/DB_Operations.php';

class CompaniesManager {
    private $conn;

    public function __construct() {
        $db = new SQL_Operations();
        $this->conn = $db->getConnection();
    }

    public function getAllCompanies() {
        $result = $this->conn->query("SELECT * FROM companies");
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}

$manager = new CompaniesManager();
$companies = $manager->getAllCompanies();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin - Companies</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body>
    <div class="container mt-5">
        <h1>Companies</h1>
        <table class="table table-striped mt-4">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Status</th>
                    <th>Logo Path</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($companies as $company): ?>
                <tr>
                    <td><?php echo htmlspecialchars($company['id']); ?></td>
                    <td><?php echo htmlspecialchars($company['name']); ?></td>
                    <td><?php echo htmlspecialchars($company['status']); ?></td>
                    <td><?php echo htmlspecialchars($company['logo_path']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <a href="Dashboard.php" class="btn btn-secondary mt-3">Back to Dashboard</a>
    </div>
</body>
</html>
