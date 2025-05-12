<?php
session_start();
if (!isset($_SESSION['student_id'])) {
    header('location: ../Frontend/login.html');
    exit();
}

require_once '../Backend/Core/Config/DataManagement/DB_Operations.php';
$db = new SQL_Operations();
$conn = $db->getConnection();

// Fetch active companies with their job listings count
$query = "SELECT c.*, 
          (SELECT COUNT(*) FROM job_listings jl WHERE jl.company_id = c.id AND jl.status = 'open') as open_positions,
          (SELECT COUNT(*) FROM job_listings jl WHERE jl.company_id = c.id) as total_positions
          FROM companies c 
          WHERE c.status = 'active' 
          ORDER BY c.name ASC";
$result = $conn->query($query);
$companies = [];
while ($row = $result->fetch_assoc()) {
    $companies[] = $row;
}

$student_name = $_SESSION['student_name'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Partner Companies</title>
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
                    <a href="job_listings.php" class="nav-link">
                        <i class="bi bi-briefcase-fill"></i> Job Listings
                    </a>
                    <a href="applications.php" class="nav-link">
                        <i class="bi bi-file-earmark-text-fill"></i> My Applications
                    </a>
                    <a href="companies.php" class="nav-link active">
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
                <!-- Section Header with Profile -->
                <div class="section-header d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <div class="d-flex align-items-center">
                            <i class="bi bi-building-fill me-2"></i>
                            <h4 class="mb-0">Partner Companies</h4>
                        </div>
                        <div class="text-muted small mt-1">Explore our trusted partner companies offering internship opportunities</div>
                    </div>
                    <div class="profile-section">
                        <i class="bi bi-person-circle profile-icon"></i>
                        <span class="profile-name ms-2"><?php echo htmlspecialchars($student_name); ?></span>
                    </div>
                </div>

                <!-- Companies Content -->
                <div class="companies-wrapper">
                    <?php if (empty($companies)): ?>
                        <div class="empty-state text-center p-5">
                            <i class="bi bi-building text-primary display-1 mb-4"></i>
                            <h4 class="mb-3">No Partner Companies</h4>
                            <p class="text-muted mb-4">We don't have any partner companies registered at the moment.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table id="companiesTable" class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Company</th>
                                        <th>Description</th>
                                        <th>Contact Information</th>
                                        <th>Open Positions</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($companies as $company): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="<?php echo htmlspecialchars('../Backend/Core/get_company_logo.php?id=' . $company['id']); ?>" 
                                                         alt="<?php echo htmlspecialchars($company['name']); ?> Logo" 
                                                         class="company-logo-sm me-2">
                                                    <div>
                                                        <?php echo htmlspecialchars($company['name']); ?>
                                                        <div class="text-muted small">
                                                            <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($company['address']); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="description-text">
                                                    <?php echo htmlspecialchars(substr($company['description'], 0, 100) . (strlen($company['description']) > 100 ? '...' : '')); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($company['contact_email']): ?>
                                                    <div class="text-muted small">
                                                        <i class="bi bi-envelope"></i> <?php echo htmlspecialchars($company['contact_email']); ?>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ($company['contact_phone']): ?>
                                                    <div class="text-muted small">
                                                        <i class="bi bi-telephone"></i> <?php echo htmlspecialchars($company['contact_phone']); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary">
                                                    <?php echo $company['open_positions']; ?> Open Position<?php echo $company['open_positions'] != 1 ? 's' : ''; ?>
                                                </span>
                                                <div class="text-muted small mt-1">
                                                    <?php echo $company['total_positions']; ?> Total Position<?php echo $company['total_positions'] != 1 ? 's' : ''; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <?php if ($company['website']): ?>
                                                        <a href="<?php echo htmlspecialchars($company['website']); ?>" 
                                                           class="btn btn-sm btn-outline-secondary"
                                                           target="_blank">
                                                            <i class="bi bi-globe"></i> Website
                                                        </a>
                                                    <?php endif; ?>
                                                    <a href="job_listings.php?company=<?php echo $company['id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-briefcase"></i> View Jobs
                                                    </a>
                                                </div>
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

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="../Assets/Scripts/csrf.js"></script>
    <script src="../Assets/Scripts/user.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', async function() {
            // Ensure clean initialization by destroying any existing instance
            if ($.fn.DataTable.isDataTable('#companiesTable')) {
                $('#companiesTable').DataTable().destroy();
            }
            
            // Initialize DataTable with custom configuration
            $('#companiesTable').DataTable({
                pageLength: 10,
                order: [[0, 'asc']], // Sort by company name ascending
                language: {
                    lengthMenu: '_MENU_ entries per page'
                },
                dom: 'l<"mb-3">rt<"row"<"col-sm-5"i><"col-sm-7"p>>' // Only show length menu, table, info and pagination
            });

            // Initialize CSRF protection
            await CSRFManager.init();
        });
    </script>
</body>
</html>