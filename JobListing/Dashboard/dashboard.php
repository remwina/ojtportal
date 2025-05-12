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
    <link rel="stylesheet" href="../Assets/Styles/admin.css">
    <!-- Add SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 sidebar">                <div class="logo-container">
                    <img src="BatStateU-NEU-Logo.png" alt="BatStateU-NEU Logo" class="logo">
                    <div class="logo-text">BatState-U NEU</div>
                    <div class="logo-subtext">On the Job Training Portal</div>
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
            <!-- Section Header with Profile -->                <div class="section-header d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <div class="d-flex align-items-center">
                            <i class="bi bi-house-door-fill me-2"></i>
                            <div>
                            <h4 class="mb-0">Student Dashboard</h4>
                            <p class="text-muted mb-0">Overview of your OJT journey</p>
                        </div>
                        </div>
                    </div>
                    <div class="profile-section">
                        <i class="bi bi-person-circle profile-icon"></i>
                        <span class="ms-2"><?php echo htmlspecialchars($student_name); ?></span>
                    </div>
                </div>

            <!-- Statistics Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="icon-box bg-primary bg-opacity-10 rounded p-3 me-3">
                                    <i class="bi bi-briefcase-fill text-primary fs-4"></i>
                                </div>
                                <div>
                                    <h3 class="mb-1"><?php echo $stats['available_positions']; ?></h3>
                                    <div class="text-muted">Available Positions</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="icon-box bg-success bg-opacity-10 rounded p-3 me-3">
                                    <i class="bi bi-file-earmark-text-fill text-success fs-4"></i>
                                </div>
                                <div>
                                    <h3 class="mb-1"><?php echo $stats['active_applications']; ?></h3>
                                    <div class="text-muted">Active Applications</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="icon-box bg-info bg-opacity-10 rounded p-3 me-3">
                                    <i class="bi bi-building-fill text-info fs-4"></i>
                                </div>
                                <div>
                                    <h3 class="mb-1"><?php echo $stats['partner_companies']; ?></h3>
                                    <div class="text-muted">Partner Companies</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Featured Opportunities -->
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-white border-bottom">
                    <div>
                        <div class="d-flex align-items-center">
                            <i class="bi bi-star-fill text-warning me-2"></i>
                            <h5 class="mb-0">Featured Opportunities</h5>
                        </div>
                        <div class="text-muted small mt-1">Latest internship positions from our partner companies</div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <?php foreach ($featured_jobs as $job): ?>
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center mb-3">
                                            <img src="<?php echo htmlspecialchars('../Backend/Core/get_company_logo.php?id=' . $job['company_id']); ?>" 
                                                 alt="<?php echo htmlspecialchars($job['company_name']); ?> Logo" 
                                                 class="company-logo rounded me-3">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($job['title']); ?></h6>
                                                <small class="text-muted"><?php echo htmlspecialchars($job['company_name']); ?></small>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <?php 
                                            $tags = explode(',', $job['tags'] ?? '');
                                            foreach ($tags as $tag): ?>
                                                <span class="badge bg-primary bg-opacity-10 text-primary"><?php echo htmlspecialchars(trim($tag)); ?></span>
                                            <?php endforeach; ?>
                                            <span class="badge <?php echo $job['work_mode'] == 'remote' ? 'bg-secondary' : 'bg-success'; ?> bg-opacity-10 text-<?php echo $job['work_mode'] == 'remote' ? 'secondary' : 'success'; ?>">
                                                <?php echo htmlspecialchars(ucfirst($job['work_mode'])); ?>
                                            </span>
                                        </div>
                                        <p class="small text-muted mb-3"><?php echo htmlspecialchars(substr($job['description'], 0, 100)) . '...'; ?></p>
                                        <a href="job_listings.php" class="btn btn-primary btn-sm">
                                            <i class="bi bi-arrow-right me-1"></i>Apply Now
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Applications -->
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-white border-bottom">
                    <div>
                        <div class="d-flex align-items-center">
                            <i class="bi bi-clock-history text-primary me-2"></i>
                            <h5 class="mb-0">Recent Applications</h5>
                        </div>
                        <div class="text-muted small mt-1">Track your recent internship applications</div>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_applications)): ?>
                        <div class="empty-state text-center py-4">
                            <i class="bi bi-file-earmark-text display-4 text-muted mb-3"></i>
                            <h6 class="text-muted">No Applications Yet</h6>
                            <p class="text-muted mb-0">You haven't applied to any positions yet.</p> 
                            <a href="job_listings.php" class="btn btn-primary mt-3">
                                <i class="bi bi-briefcase me-2"></i>Browse Opportunities
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="border-0">Company</th>
                                        <th class="border-0">Position</th>
                                        <th class="border-0">Date Applied</th>
                                        <th class="border-0">Status</th>
                                        <th class="border-0 text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_applications as $app): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="<?php echo htmlspecialchars('../Backend/Core/get_company_logo.php?id=' . $app['company_id']); ?>" 
                                                         alt="<?php echo htmlspecialchars($app['company_name']); ?> Logo" 
                                                         class="company-logo rounded me-2" style="width: 32px; height: 32px;">
                                                    <div><?php echo htmlspecialchars($app['company_name']); ?></div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="fw-semibold"><?php echo htmlspecialchars($app['title']); ?></div>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <i class="bi bi-calendar-event me-1"></i>
                                                    <?php echo date('M j, Y', strtotime($app['created_at'])); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <?php 
                                                $status_class = '';
                                                $status_text = '';
                                                switch($app['status']) {
                                                    case 'pending': 
                                                        $status_class = 'bg-warning bg-opacity-10 text-warning'; 
                                                        $status_text = '<i class="bi bi-hourglass me-1"></i>Pending';
                                                        break;
                                                    case 'reviewing': 
                                                        $status_class = 'bg-info bg-opacity-10 text-info'; 
                                                        $status_text = '<i class="bi bi-eye me-1"></i>Reviewing';
                                                        break;
                                                    case 'interview': 
                                                        $status_class = 'bg-primary bg-opacity-10 text-primary'; 
                                                        $status_text = '<i class="bi bi-calendar2-check me-1"></i>Interview';
                                                        break;
                                                    case 'accepted': 
                                                        $status_class = 'bg-success bg-opacity-10 text-success'; 
                                                        $status_text = '<i class="bi bi-check-circle me-1"></i>Accepted';
                                                        break;
                                                    case 'rejected': 
                                                        $status_class = 'bg-danger bg-opacity-10 text-danger'; 
                                                        $status_text = '<i class="bi bi-x-circle me-1"></i>Rejected';
                                                        break;
                                                }
                                                ?>
                                                <span class="badge <?php echo $status_class; ?>">
                                                    <?php echo $status_text; ?>
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <a href="applications.php" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-arrow-right me-1"></i>View Details
                                                </a>
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
</div>

<!-- Load scripts in correct order -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../Assets/Scripts/csrf.js"></script>
<script src="../Assets/Scripts/user.js"></script>
</body>
</html>