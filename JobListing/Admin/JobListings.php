<?php
require_once 'Auth.php';

$auth = new Auth();

if (!$auth->check()) {
    header('Location: Login.php');
    exit();
}

require_once __DIR__ . '/../Backend/Core/Config/DataManagement/DB_Operations.php';

class JobListingsManager {
    private $conn;

    public function __construct() {
        $db = new SQL_Operations();
        $this->conn = $db->getConnection();
    }

    public function getAllJobListings() {
        $result = $this->conn->query("SELECT jl.*, c.name as company_name FROM job_listings jl JOIN companies c ON jl.company_id = c.id");
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}

$manager = new JobListingsManager();
$jobListings = $manager->getAllJobListings();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin - Job Listings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body>
    <div class="container mt-5">
        <h1>Job Listings</h1>
        <table class="table table-striped mt-4">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Company</th>
                    <th>Status</th>
                    <th>Expires At</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($jobListings as $job): ?>
                <tr>
                    <td><?php echo htmlspecialchars($job['id']); ?></td>
                    <td><?php echo htmlspecialchars($job['title']); ?></td>
                    <td><?php echo htmlspecialchars($job['company_name']); ?></td>
                    <td><?php echo htmlspecialchars($job['status']); ?></td>
                    <td><?php echo htmlspecialchars($job['expires_at']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <a href="Dashboard.php" class="btn btn-secondary mt-3">Back to Dashboard</a>
    </div>
</body>
</html>
