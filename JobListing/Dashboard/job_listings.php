<?php
session_start();
if (!isset($_SESSION['student_id'])) {
    header('location: ../Frontend/login.html');
    exit();
}

require_once '../Backend/Core/Config/DataManagement/DB_Operations.php';
$db = new SQL_Operations();
$conn = $db->getConnection();

// Fetch all active job listings with company details
$query = "SELECT jl.*, c.name as company_name, c.id as company_id, c.address as location,
          (SELECT COUNT(*) FROM job_applications ja WHERE ja.job_id = jl.id) as application_count
          FROM job_listings jl 
          JOIN companies c ON jl.company_id = c.id 
          WHERE jl.status = 'open' 
          AND (jl.expires_at IS NULL OR jl.expires_at >= CURDATE())
          ORDER BY jl.created_at DESC";
$result = $conn->query($query);
$jobs = [];
while ($row = $result->fetch_assoc()) {
    $jobs[] = $row;
}

// Get the user's already applied job IDs
$stmt = $conn->prepare("SELECT job_id FROM job_applications WHERE user_id = ?");
$stmt->bind_param('i', $_SESSION['student_id']);
$stmt->execute();
$result = $stmt->get_result();
$applied_jobs = [];
while ($row = $result->fetch_assoc()) {
    $applied_jobs[] = $row['job_id'];
}

$student_name = $_SESSION['student_name'];

// Helper function to calculate time ago
function time_ago($datetime) {
    $now = new DateTime();
    $then = new DateTime($datetime);
    $diff = $now->getTimestamp() - $then->getTimestamp();
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) { // Less than 7 days
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return $then->format('M j, Y'); // e.g., "May 1, 2025"
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Listings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../Assets/Styles/user.css">
    </style>
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
                    <a href="dashboard.php" class="nav-link">
                        <i class="bi bi-house-door-fill"></i> Dashboard
                    </a>
                    <a href="job_listings.php" class="nav-link active">
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
                            <input type="text" class="form-control" id="jobSearch" placeholder="Search for jobs...">
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

                <!-- Section Header -->
                <div class="section-header">
                    <i class="bi bi-briefcase-fill"></i>
                    <h4 class="mb-0">Available Positions</h4>
                </div>
                
                <!-- Sort and Filter -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <select class="form-select w-auto" id="sortJobs">
                        <option value="latest">Sort by: Latest</option>
                        <option value="salary-high">Highest Salary</option>
                        <option value="salary-low">Lowest Salary</option>
                        <option value="alphabetical">A-Z</option>
                    </select>
                </div>

                <!-- Jobs Table -->
                <div class="card">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Position</th>
                                    <th>Requirements</th>
                                    <th>Location & Type</th>
                                    <th>Salary Range</th>
                                    <th>Posted</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($jobs)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            <i class="bi bi-info-circle"></i> No job listings available at the moment.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($jobs as $job): ?>
                                        <tr data-job-type="<?php echo htmlspecialchars($job['job_type']); ?>"
                                            data-work-mode="<?php echo htmlspecialchars($job['work_mode']); ?>"
                                            data-salary="<?php echo htmlspecialchars($job['salary_range']); ?>"
                                            data-job-id="<?php echo htmlspecialchars($job['id']); ?>">
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="<?php echo htmlspecialchars('../Backend/Core/get_company_logo.php?id=' . $job['company_id']); ?>" 
                                                         alt="<?php echo htmlspecialchars($job['company_name']); ?> Logo" 
                                                         class="company-logo me-3">
                                                    <div>
                                                        <h6 class="mb-0"><?php echo htmlspecialchars($job['title']); ?></h6>
                                                        <p class="mb-0 text-muted"><?php echo htmlspecialchars($job['company_name']); ?></p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="requirements-cell">
                                                    <p class="mb-0 small text-muted">
                                                        <?php echo htmlspecialchars(substr($job['requirements'], 0, 100) . (strlen($job['requirements']) > 100 ? '...' : '')); ?>
                                                    </p>
                                                </div>
                                            </td>
                                            <td>
                                                <p class="mb-1">
                                                    <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($job['location'] ?? 'Location not specified'); ?>
                                                </p>
                                                <span class="badge <?php echo $job['work_mode'] == 'Remote' ? 'badge-remote' : 'badge-onsite'; ?>">
                                                    <i class="bi <?php echo $job['work_mode'] == 'Remote' ? 'bi-laptop' : 'bi-building'; ?>"></i>
                                                    <?php echo htmlspecialchars($job['work_mode']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="salary-badge">
                                                    <i class="bi bi-currency-dollar"></i>
                                                    <?php echo htmlspecialchars($job['salary_range']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php echo time_ago($job['created_at']); ?>
                                            </td>
                                            <td>
                                                <?php if (in_array($job['id'], $applied_jobs)): ?>
                                                    <button class="btn btn-secondary btn-sm" disabled>
                                                        <i class="bi bi-check2-circle"></i> Applied
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn btn-primary apply-btn btn-sm" data-job-id="<?php echo $job['id']; ?>">
                                                        <i class="bi bi-send"></i> Apply Now
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/Scripts/csrf.js"></script>
    <script src="../Assets/Scripts/alert.js"></script>
    <script src="../Assets/Scripts/loading.js"></script>
    <script src="../Assets/Scripts/user.js"></script>
</body>
</html>