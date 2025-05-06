<?php
require_once 'Auth.php';

$auth = new Auth();

if (!$auth->check()) {
    header('Location: Login.php');
    exit();
}

require_once __DIR__ . '/../Backend/Core/Config/DataManagement/DB_Operations.php';

class ApplicationsManager {
    private $conn;

    public function __construct() {
        $db = new SQL_Operations();
        $this->conn = $db->getConnection();
    }

    public function getAllApplications() {
        $query = "SELECT ja.*, u.firstname, u.lastname, jl.title, c.name as company_name
                  FROM job_applications ja
                  JOIN users u ON ja.user_id = u.id
                  JOIN job_listings jl ON ja.job_id = jl.id
                  JOIN companies c ON jl.company_id = c.id";
        $result = $this->conn->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}

$manager = new ApplicationsManager();
$applications = $manager->getAllApplications();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin - Applications</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body>
    <div class="container mt-5">
        <h1>Job Applications</h1>
        <table class="table table-striped mt-4">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Applicant</th>
                    <th>Job Title</th>
                    <th>Company</th>
                    <th>Status</th>
                    <th>Applied At</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($applications as $app): ?>
                <tr>
                    <td><?php echo htmlspecialchars($app['id']); ?></td>
                    <td><?php echo htmlspecialchars($app['firstname'] . ' ' . $app['lastname']); ?></td>
                    <td><?php echo htmlspecialchars($app['title']); ?></td>
                    <td><?php echo htmlspecialchars($app['company_name']); ?></td>
                    <td><?php echo htmlspecialchars($app['status']); ?></td>
                    <td><?php echo htmlspecialchars($app['created_at']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <a href="Dashboard.php" class="btn btn-secondary mt-3">Back to Dashboard</a>
    </div>
</body>
</html>
