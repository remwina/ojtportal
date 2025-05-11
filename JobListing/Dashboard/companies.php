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
                <!-- Search and Profile -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="search-container">
                        <div class="search-bar">
                            <input type="text" class="form-control" id="companySearch" placeholder="Search companies...">
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
                    <i class="bi bi-building-fill"></i>
                    <h4 class="mb-0">Partner Companies</h4>
                </div>

                <!-- Companies Table -->
                <div class="card">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Company</th>
                                    <th>Description</th>
                                    <th>Address</th>
                                    <th>Contact</th>
                                    <th>Open Positions</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($companies)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            <i class="bi bi-info-circle"></i> No partner companies available at the moment.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($companies as $company): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="<?php echo htmlspecialchars('../Backend/Core/get_company_logo.php?id=' . $company['id']); ?>" 
                                                         alt="<?php echo htmlspecialchars($company['name']); ?> Logo" 
                                                         class="company-logo me-3">
                                                    <div>
                                                        <h6 class="mb-0"><?php echo htmlspecialchars($company['name']); ?></h6>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <p class="text-muted mb-0 description-text">
                                                    <?php echo htmlspecialchars(substr($company['description'], 0, 100) . (strlen($company['description']) > 100 ? '...' : '')); ?>
                                                </p>
                                            </td>
                                            <td>
                                                <p class="text-muted mb-0">
                                                    <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($company['address']); ?>
                                                </p>
                                            </td>
                                            <td>
                                                <?php if ($company['contact_email']): ?>
                                                    <p class="mb-0"><i class="bi bi-envelope"></i> <?php echo htmlspecialchars($company['contact_email']); ?></p>
                                                <?php endif; ?>
                                                <?php if ($company['contact_phone']): ?>
                                                    <p class="mb-0"><i class="bi bi-telephone"></i> <?php echo htmlspecialchars($company['contact_phone']); ?></p>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary">
                                                    <?php echo $company['open_positions']; ?> Open Position<?php echo $company['open_positions'] != 1 ? 's' : ''; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($company['website']): ?>
                                                    <a href="<?php echo htmlspecialchars($company['website']); ?>" 
                                                       class="btn btn-primary btn-sm" 
                                                       target="_blank">
                                                        <i class="bi bi-globe"></i> Website
                                                    </a>
                                                <?php endif; ?>
                                                <a href="job_listings.php?company=<?php echo $company['id']; ?>" 
                                                   class="btn btn-outline-primary btn-sm">
                                                    <i class="bi bi-briefcase"></i> View Jobs
                                                </a>
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
    <script src="../Assets/Scripts/companies.js"></script>
</body>
</html>