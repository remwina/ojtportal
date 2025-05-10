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
$query = "SELECT jl.*, c.name as company_name, c.id as company_id,
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
        return $diff . ' second' . ($diff > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } else {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
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
        
        .filter-section {
            background: var(--pure-white);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .filter-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--dark-gray);
            display: flex;
            align-items: center;
        }
        
        .filter-title i {
            margin-right: 8px;
            color: var(--primary-red);
        }
        
        .filter-group {
            margin-bottom: 20px;
        }
        
        .filter-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 15px;
        }
        
        .clear-filters {
            color: var(--primary-red);
            background: none;
            border: none;
            font-size: 0.9rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .clear-filters:hover {
            color: var (--soft-red);
            text-decoration: underline;
        }
        
        .job-card {
            border: 1px solid #dee2e6;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            background: var(--pure-white);
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            position: relative;
        }
        
        .job-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            background-color: #F8F9FA;
        }
        
        .job-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--dark-gray);
            margin-bottom: 5px;
        }
        
        .company-name {
            font-size: 0.95rem;
            color: #6c757d;
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
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .badge i {
            font-size: 0.8rem;
        }
        
        .badge-web {
            background-color: #007BFF;
            color: white;
        }
        
        .badge-remote {
            background-color: #6C757D;
            color: white;
        }
        
        .badge-php {
            background-color: #17A2B8;
            color: white;
        }
        
        .badge-hybrid {
            background-color: #6F42C1;
            color: white;
        }
        
        .badge-testing {
            background-color: #20C997;
            color: white;
        }
        
        .job-details {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #dee2e6;
        }
        
        .job-details i {
            color: var(--primary-red);
            margin-right: 5px;
        }
        
        .job-details .salary {
            font-weight: 600;
            color: var(--dark-gray);
        }
        
        .bookmark-icon {
            position: absolute;
            top: 15px;
            right: 15px;
            color: #adb5bd;
            cursor: pointer;
            transition: color 0.2s;
        }
        
        .bookmark-icon:hover {
            color: var(--primary-red);
        }
        
        .apply-btn {
            background-color: var(--primary-red);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: background-color 0.2s;
        }
        
        .apply-btn:hover {
            background-color: var(--soft-red);
            color: white;
        }
        
        .sort-dropdown {
            margin-bottom: 20px;
        }
        
        .sort-dropdown .form-select {
            border-radius: 20px;
            padding-right: 30px;
        }

        .apply-filters-btn {
            background-color: var(--primary-red);
            border-color: var(--primary-red);
            color: var(--pure-white);
        }
        
        .apply-filters-btn:hover {
            background-color: var(--soft-red);
            border-color: var(--soft-red);
             color: var(--pure-white);
        }
        
        @media (max-width: 768px) {
            .filter-section {
                margin-bottom: 15px;
            }
            
            .filter-group {
                margin-bottom: 10px;
            }
            
            .job-card {
                margin-bottom: 15px;
            }
        }
  
        .main-content {
            margin-left: 16.666667%;
        }
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
                
                <!-- Sort Dropdown -->
                <div class="sort-dropdown">
                    <select class="form-select" id="sortJobs">
                        <option value="latest">Sort by: Latest</option>
                        <option value="salary-high">Highest Salary</option>
                        <option value="salary-low">Lowest Salary</option>
                        <option value="alphabetical">A-Z</option>
                    </select>
                </div>

                <!-- Job Cards -->
                <div class="row" id="job-listings-container">
                    <?php if (empty($jobs)): ?>
                        <div class="col-12">
                            <div class="alert alert-info" id="no-jobs-message">
                                <i class="bi bi-info-circle"></i> No job listings available at the moment.
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($jobs as $job): ?>
                            <div class="col-md-6">
                                <div class="job-card" 
                                     data-job-type="<?php echo htmlspecialchars($job['job_type']); ?>"
                                     data-work-mode="<?php echo htmlspecialchars($job['work_mode']); ?>"
                                     data-salary="<?php echo htmlspecialchars($job['salary_range']); ?>"
                                     data-job-id="<?php echo htmlspecialchars($job['id']); ?>">
                                    <i class="bi bi-bookmark bookmark-icon"></i>
                                    <div class="d-flex align-items-center mb-3">
                                        <img src="<?php echo htmlspecialchars('../Backend/Core/get_company_logo.php?id=' . $job['company_id']); ?>" 
                                             alt="<?php echo htmlspecialchars($job['company_name']); ?> Logo" 
                                             class="company-logo me-3">
                                        <div>
                                            <h5 class="job-title"><?php echo htmlspecialchars($job['title']); ?></h5>
                                            <p class="company-name"><?php echo htmlspecialchars($job['company_name']); ?></p>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <?php 
                                        $tags = explode(',', $job['tags']);
                                        foreach ($tags as $tag): ?>
                                            <span class="badge bg-primary"><?php echo htmlspecialchars(trim($tag)); ?></span>
                                        <?php endforeach; ?>
                                        <span class="badge <?php echo $job['work_mode'] == 'Remote' ? 'badge-remote' : 'badge-onsite'; ?>">
                                            <i class="bi <?php echo $job['work_mode'] == 'Remote' ? 'bi-laptop' : 'bi-building'; ?>"></i>
                                            <?php echo htmlspecialchars($job['work_mode']); ?>
                                        </span>
                                    </div>
                                    <div class="job-details">
                                        <p class="mb-2"><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($job['location']); ?></p>
                                        <p class="mb-2"><i class="bi bi-calendar3"></i> Posted <?php echo time_ago($job['created_at']); ?></p>
                                        <p class="mb-2"><i class="bi bi-currency-dollar"></i> <?php echo htmlspecialchars($job['salary_range']); ?></p>
                                    </div>
                                    <?php if (in_array($job['id'], $applied_jobs)): ?>
                                        <button class="btn btn-secondary btn-sm mt-3" disabled>
                                            <i class="bi bi-check2-circle"></i> Applied
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-primary apply-btn btn-sm mt-3" data-job-id="<?php echo $job['id']; ?>">
                                            <i class="bi bi-send"></i> Apply Now
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
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