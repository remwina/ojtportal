<?php
session_start();
if (!isset($_SESSION['student_id'])) {
    header('location: login.php');
    exit();
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
        
        .company-card {
            background: var(--pure-white);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .company-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .company-logo {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
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
        
        .btn-primary {
            background-color: var(--primary-red);
            border-color: var(--primary-red);
        }
        
        .btn-primary:hover {
            background-color: var(--soft-red);
            border-color: var(--soft-red);
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
                <!-- Section Header -->
                <div class="section-header">
                    <i class="bi bi-building-fill"></i>
                    <h4 class="mb-0">Partner Companies</h4>
                </div>

                <!-- Company Cards -->
                <div class="row">
                    <div class="col-md-4">
                        <div class="company-card">
                            <div class="d-flex align-items-center mb-3">
                                <img src="https://logo.clearbit.com/accenture.com" alt="Accenture Logo" class="company-logo me-3">
                                <div>
                                    <h5 class="mb-1">Accenture Philippines</h5>
                                    <p class="mb-0 text-muted">Industry: Consulting</p>
                                </div>
                            </div>
                            <p class="small mb-3">Accenture is a global professional services company with leading capabilities in digital, cloud, and security.</p>
                            <a href="https://www.accenture.com/ph-en" class="btn btn-primary btn-sm" target="_blank">Visit Website</a>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="company-card">
                            <div class="d-flex align-items-center mb-3">
                                <img src="https://logo.clearbit.com/ibm.com" alt="IBM Logo" class="company-logo me-3">
                                <div>
                                    <h5 class="mb-1">IBM Philippines</h5>
                                    <p class="mb-0 text-muted">Industry: Technology</p>
                                </div>
                            </div>
                            <p class="small mb-3">IBM is a leading cloud platform and cognitive solutions company.</p>
                            <a href="https://www.ibm.com/ph-en" class="btn btn-primary btn-sm" target="_blank">Visit Website</a>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="company-card">
                            <div class="d-flex align-items-center mb-3">
                                <img src="https://logo.clearbit.com/google.com" alt="Google Logo" class="company-logo me-3">
                                <div>
                                    <h5 class="mb-1">Google Philippines</h5>
                                    <p class="mb-0 text-muted">Industry: Technology</p>
                                </div>
                            </div>
                            <p class="small mb-3">Google is a multinational corporation that specializes in Internet-related services and products.</p>
                            <a href="https://www.google.com.ph" class="btn btn-primary btn-sm" target="_blank">Visit Website</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 