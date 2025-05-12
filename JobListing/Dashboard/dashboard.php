<?php
session_start();
if (!isset($_SESSION['student_id'])) {
    header('location: ../Frontend/login.html');
    exit();
}

require_once '../Backend/Core/Config/DataManagement/DB_Operations.php';
$db = new SQL_Operations();
$conn = $db->getConnection();

// Get statistics
$stats = [];

// Count available positions
$result = $conn->query("SELECT COUNT(*) as count FROM job_listings WHERE status = 'open' AND (expires_at IS NULL OR expires_at >= CURDATE())");
$stats['available_positions'] = $result->fetch_assoc()['count'];

// Count user's active applications
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM job_applications WHERE user_id = ? AND status != 'rejected'");
$stmt->bind_param('i', $_SESSION['student_id']);
$stmt->execute();
$stats['active_applications'] = $stmt->get_result()->fetch_assoc()['count'];

// Count partner companies
$result = $conn->query("SELECT COUNT(*) as count FROM companies WHERE status = 'active'");
$stats['partner_companies'] = $result->fetch_assoc()['count'];

// Get recent applications
$stmt = $conn->prepare("SELECT ja.*, jl.title, c.name as company_name, c.id as company_id
                       FROM job_applications ja 
                       JOIN job_listings jl ON ja.job_id = jl.id 
                       JOIN companies c ON jl.company_id = c.id 
                       WHERE ja.user_id = ?
                       ORDER BY ja.created_at DESC LIMIT 5");
$stmt->bind_param('i', $_SESSION['student_id']);
$stmt->execute();
$recent_applications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get featured opportunities
$featured_jobs = $conn->query("SELECT jl.*, c.name as company_name, c.id as company_id
                              FROM job_listings jl 
                              JOIN companies c ON jl.company_id = c.id 
                              WHERE jl.status = 'open' 
                              AND (jl.expires_at IS NULL OR jl.expires_at >= CURDATE())
                              ORDER BY jl.created_at DESC LIMIT 4")->fetch_all(MYSQLI_ASSOC);

$student_name = $_SESSION['student_name'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../Assets/Styles/user.css">
    <!-- Add SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <div class="logo-container">
                    <img src="BatStateU-NEU-Logo.png" alt="BatStateU-NEU Logo" class="logo">
                    <div class="logo-text">BatState-U NEU</div>
                    <div class="logo-subtext">OJT Portal</div>
                </div>
                <div class="d-flex flex-column">
                    <a href="dashboard.php" class="nav-link active">
                        <i class="bi bi-house-door-fill"></i> Dashboard
                    </a>
                    <a href="job_listings.php" class="nav-link">
                        <i class="bi bi-briefcase-fill"></i> Job Listings
                    </a>
                    <a href="applications.php" class="nav-link">
                        <i class="bi bi-file-earmark-text-fill"></i> My Applications
                    </a>
                    <a href="companies.php" class="nav-link">
                        <i class="bi bi-building-fill"></i> Partner Companies
                    </a>
                    <a href="resume.php" class="nav-link">
                        <i class="bi bi-file-person-fill"></i> My Resume
                    </a>
                    <a href="profile.php" class="nav-link">
                        <i class="bi bi-person-fill"></i> My Profile
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
                            <input type="text" class="form-control" placeholder="Type to search...">
                            <button class="search-button">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="profile-section">
                        <a href="profile.php">
                            <i class="bi bi-person-circle profile-icon"></i>
                        </a>
                    </div>
                </div>

                <!-- Welcome Section -->
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-md-8">
                            <div class="welcome-section">
                                <h2>Welcome, <?php echo htmlspecialchars($student_name); ?>!</h2>
                                <p class="text-muted">Discover the ideal internship opportunity for you!</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row">
                    <div class="col-md-4">
                        <div class="stats-card bg-primary bg-opacity-10">
                            <i class="bi bi-briefcase-fill stats-icon"></i>
                            <div class="stats-number text-primary"><?php echo $stats['available_positions']; ?></div>
                            <div>Available Positions</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card bg-success bg-opacity-10">
                            <i class="bi bi-file-earmark-text-fill stats-icon"></i>
                            <div class="stats-number text-success"><?php echo $stats['active_applications']; ?></div>
                            <div>Active Applications</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card bg-info bg-opacity-10">
                            <i class="bi bi-building-fill stats-icon"></i>
                            <div class="stats-number text-info"><?php echo $stats['partner_companies']; ?></div>
                            <div>Partner Companies</div>
                        </div>
                    </div>
                </div>

                <!-- Featured Opportunities -->
                <div class="mt-4">
                    <div class="section-header">
                        <i class="bi bi-star-fill"></i>
                        <h4 class="mb-0">Featured Opportunities</h4>
                    </div>
                    <div class="row">
                        <?php foreach ($featured_jobs as $job): ?>
                            <div class="col-md-6">
                                <div class="job-card">
                                    <div class="d-flex align-items-center mb-3">
                                        <img src="<?php echo htmlspecialchars('../Backend/Core/get_company_logo.php?id=' . $job['company_id']); ?>" 
                                             alt="<?php echo htmlspecialchars($job['company_name']); ?> Logo" 
                                             class="company-logo me-3">
                                        <div>
                                            <h5 class="mb-1"><?php echo htmlspecialchars($job['title']); ?></h5>
                                            <p class="mb-0 text-muted"><?php echo htmlspecialchars($job['company_name']); ?></p>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <?php 
                                        $tags = explode(',', $job['tags'] ?? '');
                                        foreach ($tags as $tag): ?>
                                            <span class="badge bg-primary"><?php echo htmlspecialchars(trim($tag)); ?></span>
                                        <?php endforeach; ?>
                                        <span class="badge <?php echo $job['work_mode'] == 'remote' ? 'bg-secondary' : 'bg-success'; ?>">
                                            <?php echo htmlspecialchars(ucfirst($job['work_mode'])); ?>
                                        </span>
                                    </div>
                                    <p class="small mb-3"><?php echo htmlspecialchars(substr($job['description'], 0, 100)) . '...'; ?></p>
                                    <a href="job_listings.php" class="btn btn-outline-primary btn-sm">Apply Now</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Recent Applications -->
                <div class="mt-4">
                    <div class="section-header">
                        <i class="bi bi-clock-history"></i>
                        <h4 class="mb-0">Recent Applications</h4>
                    </div>
                    <?php if (empty($recent_applications)): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> You haven't applied to any positions yet. 
                            <a href="job_listings.php" class="alert-link">Browse available opportunities</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Company</th>
                                        <th>Position</th>
                                        <th>Date Applied</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_applications as $app): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($app['company_name']); ?></td>
                                            <td><?php echo htmlspecialchars($app['title']); ?></td>
                                            <td><?php echo date('M j, Y', strtotime($app['created_at'])); ?></td>
                                            <td>
                                                <?php 
                                                $status_class = '';
                                                switch($app['status']) {
                                                    case 'pending': $status_class = 'bg-warning'; break;
                                                    case 'reviewing': $status_class = 'bg-info'; break;
                                                    case 'interview': $status_class = 'bg-primary'; break;
                                                    case 'accepted': $status_class = 'bg-success'; break;
                                                    case 'rejected': $status_class = 'bg-danger'; break;
                                                }
                                                ?>
                                                <span class="badge <?php echo $status_class; ?>">
                                                    <?php echo ucfirst($app['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="applications.php" class="btn btn-sm btn-outline-primary">View Details</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/Scripts/csrf.js"></script>
    <script src="../Assets/Scripts/user.js"></script>
</body>
</html>