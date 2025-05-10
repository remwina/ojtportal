<?php
require_once 'Auth.php';

$auth = new Auth();

if (!$auth->check() || $auth->usertype() !== 'admin') {
    header('Location: ../Frontend/login.html');
    exit();
}

// Get admin name from session
$adminName = isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : 'Admin';

require_once __DIR__ . '/../Backend/Core/Config/DataManagement/DB_Operations.php';

class AdminDashboard {
    private $dbOps;
    private $conn;

    public function __construct() {
        $this->dbOps = new SQL_Operations();
        $this->conn = $this->dbOps->getConnection();
    }

    public function getStats() {
        $stats = [];

        // Basic metrics
        $result = $this->conn->query("SELECT COUNT(*) as count FROM job_listings");
        $stats['total_jobs'] = $result->fetch_assoc()['count'];

        $result = $this->conn->query("SELECT COUNT(*) as count FROM companies WHERE status = 'active'");
        $stats['total_companies'] = $result->fetch_assoc()['count'];

        $result = $this->conn->query("SELECT COUNT(*) as count FROM job_applications");
        $stats['total_applications'] = $result->fetch_assoc()['count'];

        $result = $this->conn->query("SELECT COUNT(*) as count FROM users");
        $stats['total_users'] = $result->fetch_assoc()['count'];

        // Job status distribution
        $result = $this->conn->query("SELECT 
            SUM(CASE WHEN status = 'open' AND (expires_at IS NULL OR expires_at >= CURDATE()) THEN 1 ELSE 0 END) as active_jobs,
            SUM(CASE WHEN status = 'closed' OR expires_at < CURDATE() THEN 1 ELSE 0 END) as inactive_jobs
            FROM job_listings");
        $job_status = $result->fetch_assoc();
        $stats['active_jobs'] = $job_status['active_jobs'];
        $stats['inactive_jobs'] = $job_status['inactive_jobs'];

        // Application status distribution
        $result = $this->conn->query("SELECT 
            status,
            COUNT(*) as count
            FROM job_applications
            GROUP BY status");
        $app_status = [];
        while ($row = $result->fetch_assoc()) {
            $app_status[$row['status']] = $row['count'];
        }
        $stats['application_status'] = $app_status;

        // Recent activity metrics (last 7 days)
        $result = $this->conn->query("SELECT 
            (SELECT COUNT(*) FROM job_listings WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)) as new_jobs,
            (SELECT COUNT(*) FROM job_applications WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)) as new_applications,
            (SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)) as new_users");
        $recent = $result->fetch_assoc();
        $stats['recent_activity'] = $recent;

        // Top departments by applications
        $result = $this->conn->query("SELECT 
            d.name as department_name,
            COUNT(ja.id) as application_count
            FROM departments d
            JOIN courses c ON c.department_id = d.id
            JOIN users u ON u.course_id = c.id
            JOIN job_applications ja ON ja.user_id = u.id
            GROUP BY d.id, d.name
            ORDER BY application_count DESC
            LIMIT 5");
        $top_departments = [];
        while ($row = $result->fetch_assoc()) {
            $top_departments[$row['department_name']] = $row['application_count'];
        }
        $stats['top_departments'] = $top_departments;

        return $stats;
    }

    public function __destruct() {
        // Connection will be closed by SQL_Operations
    }
}

$dashboard = new AdminDashboard();
$stats = $dashboard->getStats();// You can replace this with dynamic admin name if available
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" />
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
                    <a href="Dashboard.php" class="nav-link active">
                        <i class="bi bi-house-door-fill"></i> Dashboard
                    </a>
                    <a href="JobListings.php" class="nav-link">
                        <i class="bi bi-briefcase-fill"></i> Job Listings
                    </a>
                    <a href="Applications.php" class="nav-link">
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
                <!-- Welcome Section -->
                <div class="welcome-section">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2>Welcome, <?php echo htmlspecialchars($adminName); ?>!</h2>
                            <p class="text-muted mb-0">Manage the job listings and applications efficiently.</p>
                        </div>
                        <div class="profile-section">
                            <i class="bi bi-person-circle profile-icon"></i>
                            <span class="profile-name"><?php echo htmlspecialchars($adminName); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row">
                    <div class="col-md-3">
                        <div class="stats-card bg-primary bg-opacity-10">
                            <i class="bi bi-briefcase-fill stats-icon"></i>
                            <div class="stats-number text-primary"><?php echo $stats['total_jobs']; ?></div>
                            <div>Total Jobs</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card bg-success bg-opacity-10">
                            <i class="bi bi-building-fill stats-icon"></i>
                            <div class="stats-number text-success"><?php echo $stats['total_companies']; ?></div>
                            <div>Total Companies</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card bg-info bg-opacity-10">
                            <i class="bi bi-file-earmark-text-fill stats-icon"></i>
                            <div class="stats-number text-info"><?php echo $stats['total_applications']; ?></div>
                            <div>Total Applications</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card bg-warning bg-opacity-10">
                            <i class="bi bi-people-fill stats-icon"></i>
                            <div class="stats-number text-warning"><?php echo $stats['total_users']; ?></div>
                            <div>Total Users</div>
                        </div>
                    </div>
                </div>

                <!-- Job Status Distribution -->
                <div class="mt-4">
                    <div class="section-header mb-3">
                        <h5><i class="bi bi-pie-chart-fill"></i> Job Status Distribution</h5>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="stats-card bg-success bg-opacity-10">
                                <i class="bi bi-check-circle-fill stats-icon"></i>
                                <div class="stats-number text-success"><?php echo $stats['active_jobs']; ?></div>
                                <div>Active Jobs</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="stats-card bg-danger bg-opacity-10">
                                <i class="bi bi-x-circle-fill stats-icon"></i>
                                <div class="stats-number text-danger"><?php echo $stats['inactive_jobs']; ?></div>
                                <div>Inactive Jobs</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Application Status Distribution -->
                <div class="mt-4">
                    <div class="section-header mb-3">
                        <h5><i class="bi bi-graph-up"></i> Application Status Distribution</h5>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="stats-card bg-warning bg-opacity-10">
                                <i class="bi bi-hourglass-split stats-icon"></i>
                                <div class="stats-number text-warning"><?php echo $stats['application_status']['pending'] ?? 0; ?></div>
                                <div>Pending Applications</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stats-card bg-info bg-opacity-10">
                                <i class="bi bi-search stats-icon"></i>
                                <div class="stats-number text-info"><?php echo $stats['application_status']['reviewing'] ?? 0; ?></div>
                                <div>Under Review</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stats-card bg-primary bg-opacity-10">
                                <i class="bi bi-calendar-event stats-icon"></i>
                                <div class="stats-number text-primary"><?php echo $stats['application_status']['interview'] ?? 0; ?></div>
                                <div>Interview Scheduled</div>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="stats-card bg-success bg-opacity-10">
                                <i class="bi bi-check-circle stats-icon"></i>
                                <div class="stats-number text-success"><?php echo $stats['application_status']['accepted'] ?? 0; ?></div>
                                <div>Accepted Applications</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="stats-card bg-danger bg-opacity-10">
                                <i class="bi bi-x-circle stats-icon"></i>
                                <div class="stats-number text-danger"><?php echo $stats['application_status']['rejected'] ?? 0; ?></div>
                                <div>Rejected Applications</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="mt-4">
                    <div class="section-header mb-3">
                        <h5><i class="bi bi-activity"></i> Recent Activity (Last 7 Days)</h5>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="stats-card bg-primary bg-opacity-10">
                                <i class="bi bi-briefcase stats-icon"></i>
                                <div class="stats-number text-primary"><?php echo $stats['recent_activity']['new_jobs']; ?></div>
                                <div>New Job Listings</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stats-card bg-success bg-opacity-10">
                                <i class="bi bi-file-text stats-icon"></i>
                                <div class="stats-number text-success"><?php echo $stats['recent_activity']['new_applications']; ?></div>
                                <div>New Applications</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stats-card bg-info bg-opacity-10">
                                <i class="bi bi-person-plus stats-icon"></i>
                                <div class="stats-number text-info"><?php echo $stats['recent_activity']['new_users']; ?></div>
                                <div>New Users</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Departments -->
                <div class="mt-4">
                    <div class="section-header mb-3">
                        <h5><i class="bi bi-bar-chart-fill"></i> Top Departments by Applications</h5>
                    </div>
                    <div class="row">
                        <?php foreach ($stats['top_departments'] as $department => $count): ?>
                            <div class="col-md-4">
                                <div class="stats-card bg-secondary bg-opacity-10">
                                    <i class="bi bi-diagram-3 stats-icon"></i>
                                    <div class="stats-number text-secondary"><?php echo $count; ?></div>
                                    <div><?php echo htmlspecialchars($department); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Change Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="changePasswordForm">
                        <input type="hidden" name="action" value="changeAdminPassword">
                        <input type="hidden" name="csrf_token" id="csrf_token">
                        
                        <div class="mb-3">
                            <label for="currentPassword" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="currentPassword" name="currentPassword" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="newPassword" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="newPassword" name="newPassword" required>
                            <div class="password-requirements small text-muted mt-1">
                                Password must contain:
                                <ul>
                                    <li>At least 6 characters</li>
                                    <li>One uppercase letter</li>
                                    <li>One lowercase letter</li>
                                    <li>One number</li>
                                    <li>One special character</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirmPassword" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="changePasswordBtn">Change Password</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Load scripts in correct order -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/Scripts/csrf.js"></script>
    <script src="../Assets/Scripts/admin.js"></script>
</body>
</html>
