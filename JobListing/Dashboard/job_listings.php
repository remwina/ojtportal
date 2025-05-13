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
function time_ago($datetime)
{
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
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="../Assets/Styles/admin.css">
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
                    <div class="logo-subtext">On the Job Training Portal</div>
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
                <!-- Page Header -->
                <div class="section-header mb-4">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-briefcase-fill"></i>
                        <div>
                            <h4>Available Positions</h4>
                            <p>Find and apply for job opportunities</p>
                        </div>
                    </div>
                    <div class="profile-section">
                        <i class="bi bi-person-circle profile-icon"></i>
                        <span class="ms-2"><?php echo htmlspecialchars($student_name); ?></span>
                    </div>
                </div>                <!-- Jobs Content -->
                <div class="jobs-wrapper">
                    <?php if (empty($jobs)): ?>
                        <div class="empty-state text-center p-5">
                            <i class="bi bi-building text-primary display-1 mb-4"></i>
                            <h4 class="mb-3">No Job Listings Available</h4>
                            <p class="text-muted mb-4">There are no job listings available at the moment.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table id="jobListingsTable" class="table table-hover align-middle">
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
                                            <div class="location-type-cell">
                                                <p class="mb-0">
                                                    <span class="d-block mb-1">
                                                        <i class="bi bi-geo-alt"></i>
                                                        <?php echo htmlspecialchars($job['location']); ?>
                                                    </span>
                                                    <span class="badge <?php echo $job['work_mode'] == 'Remote' ? 'badge-remote' : 'badge-onsite'; ?>">
                                                        <i class="bi <?php echo $job['work_mode'] == 'Remote' ? 'bi-laptop' : 'bi-building'; ?>"></i>
                                                        <?php echo htmlspecialchars($job['work_mode']); ?>
                                                    </span>
                                                </p>
                                            </div>
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
                                    </tr>                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="../Assets/Scripts/csrf.js"></script>
    <script src="../Assets/Scripts/alert.js"></script>
    <script src="../Assets/Scripts/loading.js"></script>
    <script src="../Assets/Scripts/user.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', async function() {
            // Initialize DataTable with custom configuration
            $('#jobListingsTable').DataTable({
                pageLength: 10,
                order: [
                    [4, 'desc']
                ], // Sort by posted date descending
                language: {
                    emptyTable: "No job listings available at the moment",
                    info: "Showing _START_ to _END_ of _TOTAL_ positions",
                    infoEmpty: "No positions available",
                    lengthMenu: "Show _MENU_ positions per page",
                    loadingRecords: "Loading...",
                    processing: "Processing...",
                    zeroRecords: "No matching positions found",
                    search: "Search positions:"
                },
                columnDefs: [{
                        orderable: false,
                        targets: 5
                    } // Disable sorting on Actions column
                ],
                dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                    "<'row'<'col-sm-12'tr>>" +
                    "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>"
            });

            // Initialize CSRF protection
            await CSRFManager.init();
        });
    </script>
</body>

</html>