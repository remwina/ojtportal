<?php
require_once 'Auth.php';

$auth = new Auth();

if (!$auth->check() || $auth->usertype() !== 'admin') {
    header('Location: ../Frontend/login.html');
    exit();
}

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

        $result = $this->conn->query("SELECT COUNT(*) as count FROM job_listings");
        $stats['total_jobs'] = $result->fetch_assoc()['count'];

        $result = $this->conn->query("SELECT COUNT(*) as count FROM companies");
        $stats['total_companies'] = $result->fetch_assoc()['count'];

        $result = $this->conn->query("SELECT COUNT(*) as count FROM job_applications");
        $stats['total_applications'] = $result->fetch_assoc()['count'];

        $result = $this->conn->query("SELECT COUNT(*) as count FROM users");
        $stats['total_users'] = $result->fetch_assoc()['count'];

        return $stats;
    }

    public function __destruct() {
        // Connection will be closed by SQL_Operations
    }
}

$dashboard = new AdminDashboard();
$stats = $dashboard->getStats();

$admin_name = "Admin"; // You can replace this with dynamic admin name if available
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" />
    <style>
        :root {
            --primary-red: #C8102E;
            --soft-red: #E63946;
            --light-gray: #F8F9FA;
            --dark-gray: #495057;
            --pure-white: #FFFFFF;
        }
        
        body {
            background-color: var(--light-gray);
            color: var(--dark-gray);
        }
        
        .sidebar {
            background-color: var(--primary-red);
            min-height: 100vh;
            padding: 20px;
            position: fixed;
            top: 0;
            left: 0;
            width: inherit;
            overflow-y: auto;
        }
        
        .logo-container {
            text-align: center;
            margin-bottom: 30px;
            padding: 15px;
        }
        
        .logo {
            width: 150px;
            height: auto;
            margin-bottom: 15px;
        }
        
        .logo-text {
            font-size: 1.4rem;
            font-weight: bold;
            color: #ffffff;
            margin-bottom: 5px;
        }
        
        .logo-subtext {
            font-size: 1rem;
            color: #ffffff;
            font-weight: 500;
        }
        
        .nav-link {
            color: #ffffff;
            padding: 10px 15px;
            margin-bottom: 5px;
            border-radius: 5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .nav-link i {
            font-size: 1.2rem;
        }
        
        .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: #ffffff;
        }
        
        .nav-link.active {
            background-color: rgba(255, 255, 255, 0.2);
            color: #ffffff;
        }
        
        .stats-card {
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            transition: transform 0.3s, box-shadow 0.3s;
            border: none;
            position: relative;
            overflow: hidden;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .stats-icon {
            font-size: 2rem;
            margin-bottom: 10px;
            color: var(--primary-red);
        }
        
        .stats-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .section-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .section-header i {
            font-size: 1.5rem;
            margin-right: 10px;
            color: var(--primary-red);
        }
        
        .job-card {
            border: 1px solid #dee2e6;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            background: var(--pure-white);
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .job-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .company-logo {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .badge {
            padding: 8px 12px;
            border-radius: 20px;
            font-weight: 500;
        }

        .welcome-section {
            text-align: center;
            padding: 30px 0;
            background-color: var(--pure-white);
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .welcome-section h2 {
            color: var(--dark-gray);
            margin-bottom: 10px;
        }

        .welcome-section p {
            color: var (--dark-gray);
            font-size: 1.1rem;
            margin-bottom: 0;
        }

        .search-bar {
            width: 1100px;
            max-width: 100%;
            margin: 0 auto;
            display: flex;
            gap: 10px;
        }
        
        .search-container {
            width: 100%;
            display: flex;
            justify-content: center;
        }
        
        .search-button {
            padding: 0.375rem 0.75rem;
            background-color: var(--primary-red);
            color: var(--pure-white);
            border: none;
            border-radius: 0.375rem;
            cursor: pointer;
            transition: background-color 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
        }
        
        .search-button:hover {
            background-color: var(--soft-red);
        }

        .profile-section {
            display: flex;
            align-items: center;
            margin-left: -50px;
        }
        
        .profile-icon {
            font-size: 1.5rem;
            color: var(--primary-red);
            cursor: pointer;
        }

        .btn-outline-primary {
            color: var(--primary-red);
            border-color: var(--primary-red);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary-red);
            border-color: var (--primary-red);
            color: var (--pure-white);
        }

        .main-content {
            margin-left: 16.666667%;
        }
    </style>
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
                <!-- Search and Profile -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="search-container">
                        <div class="search-bar">
                            <input type="text" class="form-control" placeholder="Search...">
                            <button class="search-button">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="profile-section">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-person-circle profile-icon"></i>
                            <span class="ms-2"><?php echo htmlspecialchars($admin_name); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Welcome Section -->
                <div class="welcome-section">
                    <h2>Welcome, <?php echo htmlspecialchars($admin_name); ?>!</h2>
                    <p class="text-muted">Manage the job listings and applications efficiently.</p>
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
