<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('location: ../Frontend/login.html');
    exit();
}

require_once 'Auth.php';

$auth = new Auth();

if (!$auth->check()) {
    header('Location: Login.php');
    exit();
}

require_once __DIR__ . '/../Backend/Core/Security/TokenHandler.php';
require_once __DIR__ . '/../Backend/Core/Config/DataManagement/DB_Operations.php';

class ApplicationsManager {
    private $dbOps;
    private $conn;

    public function __construct() {
        $this->dbOps = new SQL_Operations();
        $this->conn = $this->dbOps->getConnection();
    }

    public function getAllApplications() {
        $result = $this->conn->query("SELECT ja.*, jl.title, c.name as company_name, u.firstname, u.lastname, u.email
                                    FROM job_applications ja 
                                    JOIN job_listings jl ON ja.job_id = jl.id
                                    JOIN companies c ON jl.company_id = c.id
                                    JOIN users u ON ja.user_id = u.id
                                    ORDER BY ja.created_at DESC");
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function __destruct() {
        // Connection will be closed by SQL_Operations
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" />
    <link rel="stylesheet" href="../Assets/Styles/admin.css" />
    <!-- Add SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <div class="logo-container">
                    <img src="../Dashboard/BatStateU-NEU-Logo.png" alt="BatStateU-NEU Logo" class="logo">
                    <div class="logo-text">BatState-U NEU</div>
                    <div class="logo-subtext">Admin Portal</div>
                </div>
                <div class="d-flex flex-column">
                    <a href="Dashboard.php" class="nav-link">
                        <i class="bi bi-house-door-fill"></i> Dashboard
                    </a>
                    <a href="JobListings.php" class="nav-link">
                        <i class="bi bi-briefcase-fill"></i> Job Listings
                    </a>
                    <a href="Applications.php" class="nav-link active">
                        <i class="bi bi-file-earmark-text-fill"></i> Applications
                    </a>
                    <a href="Companies.php" class="nav-link">
                        <i class="bi bi-building-fill"></i> Companies
                    </a>
                    <a href="Users.php" class="nav-link">
                        <i class="bi bi-people-fill"></i> Users
                    </a>
                    <a href="logout.php" class="nav-link">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 p-4 main-content">
                <!-- Section Header -->
                <div class="section-header d-flex justify-content-between align-items-center mb-4">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-file-earmark-text-fill me-2"></i>
                        <h4 class="mb-0">Applications Management</h4>
                    </div>
                </div>

                <!-- Applications Table -->
                <div class="table-responsive">
                    <table id="applicationsTable" class="table table-hover">
                        <thead>
                            <tr>
                                <th>Applicant</th>
                                <th>Job Title</th>
                                <th>Company</th>
                                <th>Applied Date</th>
                                <th>Status</th>
                                <th>Resume</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($applications as $app): ?>
                                <tr>
                                    <td>
                                        <div>
                                            <?php echo htmlspecialchars($app['firstname'] . ' ' . $app['lastname']); ?>
                                        </div>
                                        <div class="text-muted small">
                                            <?php echo htmlspecialchars($app['email']); ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($app['title']); ?></td>
                                    <td><?php echo htmlspecialchars($app['company_name']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($app['created_at'])); ?></td>
                                    <td>
                                        <select class="form-select form-select-sm status-select" 
                                                data-id="<?php echo $app['id']; ?>">
                                            <option value="pending" <?php echo $app['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="reviewing" <?php echo $app['status'] === 'reviewing' ? 'selected' : ''; ?>>Reviewing</option>
                                            <option value="interview" <?php echo $app['status'] === 'interview' ? 'selected' : ''; ?>>Interview</option>
                                            <option value="accepted" <?php echo $app['status'] === 'accepted' ? 'selected' : ''; ?>>Accepted</option>
                                            <option value="rejected" <?php echo $app['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                        </select>
                                    </td>
                                    <td>
                                        <?php if ($app['resume_path']): ?>
                                            <a href="<?php echo htmlspecialchars($app['resume_path']); ?>" 
                                               class="btn btn-sm btn-outline-primary"
                                               target="_blank">
                                                <i class="bi bi-file-earmark-pdf"></i> View
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">No resume</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary me-2 view-btn" 
                                                data-id="<?php echo $app['id']; ?>"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#viewApplicationModal">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger delete-btn"
                                                data-id="<?php echo $app['id']; ?>">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- View Application Modal -->
    <div class="modal fade" id="viewApplicationModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Application Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Application details will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Load scripts in correct order -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="../Assets/Scripts/csrf.js"></script>
    <script src="../Assets/Scripts/admin.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', async function() {
            // Destroy existing DataTable instance if it exists
            if ($.fn.DataTable.isDataTable('#applicationsTable')) {
                $('#applicationsTable').DataTable().destroy();
            }
            
            // Initialize fresh DataTable instance
            $('#applicationsTable').DataTable({
                order: [[3, 'desc']], // Sort by date column descending
                pageLength: 10,
                language: {
                    search: "Filter records:"
                }
            });

            // Initialize CSRF token management
            await CSRFManager.init();
        });
    </script>
</body>
</html>
