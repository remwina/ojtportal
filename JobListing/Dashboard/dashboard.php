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
$stmt = $conn->prepare("SELECT ja.*, jl.title, c.name as company_name, c.logo_path
                       FROM job_applications ja 
                       JOIN job_listings jl ON ja.job_id = jl.id 
                       JOIN companies c ON jl.company_id = c.id 
                       WHERE ja.user_id = ?
                       ORDER BY ja.created_at DESC LIMIT 5");
$stmt->bind_param('i', $_SESSION['student_id']);
$stmt->execute();
$recent_applications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get featured opportunities
$featured_jobs = $conn->query("SELECT jl.*, c.name as company_name, c.logo_path
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
            color: var(--dark-gray);
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
            border-color: var(--primary-red);
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
                            <div class="stats-number text-primary">25</div>
                            <div>Available Positions</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card bg-success bg-opacity-10">
                            <i class="bi bi-file-earmark-text-fill stats-icon"></i>
                            <div class="stats-number text-success">5</div>
                            <div>Active Applications</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card bg-info bg-opacity-10">
                            <i class="bi bi-building-fill stats-icon"></i>
                            <div class="stats-number text-info">12</div>
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
                        <div class="col-md-6">
                            <div class="job-card">
                                <div class="d-flex align-items-center mb-3">
                                    <img src="https://logo.clearbit.com/accenture.com" alt="Accenture Logo" class="company-logo me-3">
                                    <div>
                                        <h5 class="mb-1">Web Developer Intern</h5>
                                        <p class="mb-0 text-muted">Accenture Philippines</p>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <span class="badge bg-primary">Web Development</span>
                                    <span class="badge bg-secondary">Remote</span>
                                    <span class="badge bg-info">PHP</span>
                                </div>
                                <p class="small mb-3">Looking for passionate web developer interns to join our dynamic team...</p>
                                <button class="btn btn-outline-primary btn-sm">Apply Now</button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="job-card">
                                <div class="d-flex align-items-center mb-3">
                                    <img src="https://logo.clearbit.com/ibm.com" alt="IBM Logo" class="company-logo me-3">
                                    <div>
                                        <h5 class="mb-1">Software QA Intern</h5>
                                        <p class="mb-0 text-muted">IBM Philippines</p>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <span class="badge bg-primary">Quality Assurance</span>
                                    <span class="badge bg-secondary">Hybrid</span>
                                    <span class="badge bg-info">Testing</span>
                                </div>
                                <p class="small mb-3">Join our QA team and gain hands-on experience in software testing...</p>
                                <button class="btn btn-outline-primary btn-sm">Apply Now</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Applications -->
                <div class="mt-4">
                    <div class="section-header">
                        <i class="bi bi-clock-history"></i>
                        <h4 class="mb-0">Recent Applications</h4>
                    </div>
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
                                <tr>
                                    <td>Google Philippines</td>
                                    <td>Frontend Developer Intern</td>
                                    <td>Jan 4, 2024</td>
                                    <td><span class="badge bg-warning">Pending</span></td>
                                    <td><button class="btn btn-sm btn-outline-primary">View Details</button></td>
                                </tr>
                                <tr>
                                    <td>Microsoft Philippines</td>
                                    <td>Software Engineer Intern</td>
                                    <td>Jan 2, 2024</td>
                                    <td><span class="badge bg-success">Interview Scheduled</span></td>
                                    <td><button class="btn btn-sm btn-outline-primary">View Details</button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js"></script>
</body>
</html>