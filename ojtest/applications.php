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
    <title>My Applications</title>
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
        
        .application-card {
            background: var(--pure-white);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .application-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .company-logo {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .status-badge {
            padding: 8px 12px;
            border-radius: 20px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .status-pending {
            background-color: #FFC107;
            color: #212529;
        }
        
        .status-reviewing {
            background-color: #17A2B8;
            color: white;
        }
        
        .status-interview {
            background-color: #6610F2;
            color: white;
        }
        
        .status-accepted {
            background-color: #28A745;
            color: white;
        }
        
        .status-rejected {
            background-color: #DC3545;
            color: white;
        }
        
        .application-details {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #dee2e6;
        }
        
        .application-details i {
            color: var(--primary-red);
            margin-right: 5px;
        }
        
        .filter-tabs {
            margin-bottom: 20px;
        }
        
        .filter-tabs .nav-link {
            color: var(--dark-gray);
            border: none;
            padding: 10px 20px;
            border-radius: 20px;
            margin-right: 10px;
            transition: all 0.3s;
        }
        
        .filter-tabs .nav-link:hover {
            background-color: rgba(200, 16, 46, 0.1);
            color: var(--primary-red);
        }
        
        .filter-tabs .nav-link.active {
            background-color: var(--primary-red);
            color: white;
        }
        
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            background: var(--pure-white);
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #dee2e6;
            margin-bottom: 20px;
        }
        
        .empty-state h4 {
            color: var(--dark-gray);
            margin-bottom: 10px;
        }
        
        .empty-state p {
            color: #6c757d;
            margin-bottom: 20px;
        }
        
        .btn-primary {
            background-color: var(--primary-red);
            border-color: var(--primary-red);
        }
        
        .btn-primary:hover {
            background-color: var(--soft-red);
            border-color: var(--soft-red);
        }
        
        .btn-outline-primary {
            color: var(--primary-red);
            border-color: var(--primary-red);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary-red);
            border-color: var(--primary-red);
            color: var(--pure-white);
        }
        
        .application-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .application-actions .btn {
            padding: 5px 15px;
            font-size: 0.9rem;
        }
        
        .application-timeline {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #dee2e6;
        }
        
        .timeline-item {
            display: flex;
            margin-bottom: 10px;
            position: relative;
            padding-left: 25px;
        }
        
        .timeline-item:before {
            content: '';
            position: absolute;
            left: 0;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: var(--primary-red);
        }
        
        .timeline-item:after {
            content: '';
            position: absolute;
            left: 5px;
            top: 17px;
            width: 2px;
            height: calc(100% - 12px);
            background-color: #dee2e6;
        }
        
        .timeline-item:last-child:after {
            display: none;
        }
        
        .timeline-date {
            font-size: 0.8rem;
            color: #6c757d;
            margin-bottom: 2px;
        }
        
        .timeline-content {
            font-size: 0.9rem;
        }
        
        .timeline-completed {
            color: #28a745;
        }
        
        .timeline-pending {
            color: #ffc107;
        }
        
        .timeline-rejected {
            color: #dc3545;
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
                    <a href="applications.php" class="nav-link active">
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
                            <input type="text" class="form-control" placeholder="Search your applications...">
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
                    <i class="bi bi-file-earmark-text-fill"></i>
                    <h4 class="mb-0">My Applications</h4>
                </div>
                
                <!-- Filter Tabs -->
                <ul class="nav filter-tabs" id="applicationTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button" role="tab" aria-controls="all" aria-selected="true">All Applications</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button" role="tab" aria-controls="pending" aria-selected="false">Pending</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="interview-tab" data-bs-toggle="tab" data-bs-target="#interview" type="button" role="tab" aria-controls="interview" aria-selected="false">Interviews</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="accepted-tab" data-bs-toggle="tab" data-bs-target="#accepted" type="button" role="tab" aria-controls="accepted" aria-selected="false">Accepted</button>
                    </li>
                </ul>
                
                <!-- Tab Content -->
                <div class="tab-content" id="applicationTabsContent">
                    <!-- All Applications Tab -->
                    <div class="tab-pane fade show active" id="all" role="tabpanel" aria-labelledby="all-tab">
                        <!-- Application Card 1 -->
                        <div class="application-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="d-flex align-items-center">
                                    <img src="https://logo.clearbit.com/accenture.com" alt="Accenture Logo" class="company-logo me-3">
                                    <div>
                                        <h5 class="mb-1">Web Developer Intern</h5>
                                        <p class="mb-0 text-muted">Accenture Philippines</p>
                                    </div>
                                </div>
                                <span class="status-badge status-reviewing">
                                    <i class="bi bi-eye"></i> Under Review
                                </span>
                            </div>
                            
                            <div class="application-details">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p class="mb-2"><i class="bi bi-calendar3"></i> Applied on: <strong>Jan 15, 2024</strong></p>
                                        <p class="mb-2"><i class="bi bi-geo-alt"></i> Location: <strong>Manila, Philippines</strong></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-2"><i class="bi bi-briefcase"></i> Job Type: <strong>Internship</strong></p>
                                        <p class="mb-2"><i class="bi bi-laptop"></i> Work Mode: <strong>Remote</strong></p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="application-timeline">
                                <h6 class="mb-3">Application Timeline</h6>
                                <div class="timeline-item">
                                    <div>
                                        <div class="timeline-date">Jan 15, 2024</div>
                                        <div class="timeline-content timeline-completed">Application submitted</div>
                                    </div>
                                </div>
                                <div class="timeline-item">
                                    <div>
                                        <div class="timeline-date">Jan 16, 2024</div>
                                        <div class="timeline-content timeline-completed">Application received</div>
                                    </div>
                                </div>
                                <div class="timeline-item">
                                    <div>
                                        <div class="timeline-date">Jan 17, 2024</div>
                                        <div class="timeline-content timeline-pending">Under review</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="application-actions">
                                <button class="btn btn-outline-primary">
                                    <i class="bi bi-eye"></i> View Details
                                </button>
                                <button class="btn btn-outline-primary">
                                    <i class="bi bi-file-earmark-text"></i> View Application
                                </button>
                            </div>
                        </div>
                        
                        <!-- Application Card 2 -->
                        <div class="application-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="d-flex align-items-center">
                                    <img src="https://logo.clearbit.com/ibm.com" alt="IBM Logo" class="company-logo me-3">
                                    <div>
                                        <h5 class="mb-1">Software QA Intern</h5>
                                        <p class="mb-0 text-muted">IBM Philippines</p>
                                    </div>
                                </div>
                                <span class="status-badge status-interview">
                                    <i class="bi bi-calendar-check"></i> Interview Scheduled
                                </span>
                            </div>
                            
                            <div class="application-details">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p class="mb-2"><i class="bi bi-calendar3"></i> Applied on: <strong>Jan 10, 2024</strong></p>
                                        <p class="mb-2"><i class="bi bi-geo-alt"></i> Location: <strong>Makati, Philippines</strong></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-2"><i class="bi bi-briefcase"></i> Job Type: <strong>Internship</strong></p>
                                        <p class="mb-2"><i class="bi bi-laptop"></i> Work Mode: <strong>Hybrid</strong></p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="application-timeline">
                                <h6 class="mb-3">Application Timeline</h6>
                                <div class="timeline-item">
                                    <div>
                                        <div class="timeline-date">Jan 10, 2024</div>
                                        <div class="timeline-content timeline-completed">Application submitted</div>
                                    </div>
                                </div>
                                <div class="timeline-item">
                                    <div>
                                        <div class="timeline-date">Jan 12, 2024</div>
                                        <div class="timeline-content timeline-completed">Application received</div>
                                    </div>
                                </div>
                                <div class="timeline-item">
                                    <div>
                                        <div class="timeline-date">Jan 13, 2024</div>
                                        <div class="timeline-content timeline-completed">Application reviewed</div>
                                    </div>
                                </div>
                                <div class="timeline-item">
                                    <div>
                                        <div class="timeline-date">Jan 14, 2024</div>
                                        <div class="timeline-content timeline-completed">Interview scheduled for Jan 20, 2024</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="application-actions">
                                <button class="btn btn-outline-primary">
                                    <i class="bi bi-eye"></i> View Details
                                </button>
                                <button class="btn btn-outline-primary">
                                    <i class="bi bi-file-earmark-text"></i> View Application
                                </button>
                                <button class="btn btn-primary">
                                    <i class="bi bi-calendar-check"></i> View Interview Details
                                </button>
                            </div>
                        </div>
                        
                        <!-- Application Card 3 -->
                        <div class="application-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="d-flex align-items-center">
                                    <img src="https://logo.clearbit.com/google.com" alt="Google Logo" class="company-logo me-3">
                                    <div>
                                        <h5 class="mb-1">Frontend Developer</h5>
                                        <p class="mb-0 text-muted">Google Philippines</p>
                                    </div>
                                </div>
                                <span class="status-badge status-rejected">
                                    <i class="bi bi-x-circle"></i> Not Selected
                                </span>
                            </div>
                            
                            <div class="application-details">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p class="mb-2"><i class="bi bi-calendar3"></i> Applied on: <strong>Jan 5, 2024</strong></p>
                                        <p class="mb-2"><i class="bi bi-geo-alt"></i> Location: <strong>Taguig, Philippines</strong></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-2"><i class="bi bi-briefcase"></i> Job Type: <strong>Full Time</strong></p>
                                        <p class="mb-2"><i class="bi bi-laptop"></i> Work Mode: <strong>On-site</strong></p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="application-timeline">
                                <h6 class="mb-3">Application Timeline</h6>
                                <div class="timeline-item">
                                    <div>
                                        <div class="timeline-date">Jan 5, 2024</div>
                                        <div class="timeline-content timeline-completed">Application submitted</div>
                                    </div>
                                </div>
                                <div class="timeline-item">
                                    <div>
                                        <div class="timeline-date">Jan 6, 2024</div>
                                        <div class="timeline-content timeline-completed">Application received</div>
                                    </div>
                                </div>
                                <div class="timeline-item">
                                    <div>
                                        <div class="timeline-date">Jan 8, 2024</div>
                                        <div class="timeline-content timeline-completed">Application reviewed</div>
                                    </div>
                                </div>
                                <div class="timeline-item">
                                    <div>
                                        <div class="timeline-date">Jan 10, 2024</div>
                                        <div class="timeline-content timeline-rejected">Application not selected</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="application-actions">
                                <button class="btn btn-outline-primary">
                                    <i class="bi bi-eye"></i> View Details
                                </button>
                                <button class="btn btn-outline-primary">
                                    <i class="bi bi-file-earmark-text"></i> View Application
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pending Tab -->
                    <div class="tab-pane fade" id="pending" role="tabpanel" aria-labelledby="pending-tab">
                        <!-- Application Card 1 (Under Review) -->
                        <div class="application-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="d-flex align-items-center">
                                    <img src="https://logo.clearbit.com/accenture.com" alt="Accenture Logo" class="company-logo me-3">
                                    <div>
                                        <h5 class="mb-1">Web Developer Intern</h5>
                                        <p class="mb-0 text-muted">Accenture Philippines</p>
                                    </div>
                                </div>
                                <span class="status-badge status-reviewing">
                                    <i class="bi bi-eye"></i> Under Review
                                </span>
                            </div>
                            
                            <div class="application-details">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p class="mb-2"><i class="bi bi-calendar3"></i> Applied on: <strong>Jan 15, 2024</strong></p>
                                        <p class="mb-2"><i class="bi bi-geo-alt"></i> Location: <strong>Manila, Philippines</strong></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-2"><i class="bi bi-briefcase"></i> Job Type: <strong>Internship</strong></p>
                                        <p class="mb-2"><i class="bi bi-laptop"></i> Work Mode: <strong>Remote</strong></p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="application-timeline">
                                <h6 class="mb-3">Application Timeline</h6>
                                <div class="timeline-item">
                                    <div>
                                        <div class="timeline-date">Jan 15, 2024</div>
                                        <div class="timeline-content timeline-completed">Application submitted</div>
                                    </div>
                                </div>
                                <div class="timeline-item">
                                    <div>
                                        <div class="timeline-date">Jan 16, 2024</div>
                                        <div class="timeline-content timeline-completed">Application received</div>
                                    </div>
                                </div>
                                <div class="timeline-item">
                                    <div>
                                        <div class="timeline-date">Jan 17, 2024</div>
                                        <div class="timeline-content timeline-pending">Under review</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="application-actions">
                                <button class="btn btn-outline-primary">
                                    <i class="bi bi-eye"></i> View Details
                                </button>
                                <button class="btn btn-outline-primary">
                                    <i class="bi bi-file-earmark-text"></i> View Application
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Interview Tab -->
                    <div class="tab-pane fade" id="interview" role="tabpanel" aria-labelledby="interview-tab">
                        <!-- Application Card 2 (Interview Scheduled) -->
                        <div class="application-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="d-flex align-items-center">
                                    <img src="https://logo.clearbit.com/ibm.com" alt="IBM Logo" class="company-logo me-3">
                                    <div>
                                        <h5 class="mb-1">Software QA Intern</h5>
                                        <p class="mb-0 text-muted">IBM Philippines</p>
                                    </div>
                                </div>
                                <span class="status-badge status-interview">
                                    <i class="bi bi-calendar-check"></i> Interview Scheduled
                                </span>
                            </div>
                            
                            <div class="application-details">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p class="mb-2"><i class="bi bi-calendar3"></i> Applied on: <strong>Jan 10, 2024</strong></p>
                                        <p class="mb-2"><i class="bi bi-geo-alt"></i> Location: <strong>Makati, Philippines</strong></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-2"><i class="bi bi-briefcase"></i> Job Type: <strong>Internship</strong></p>
                                        <p class="mb-2"><i class="bi bi-laptop"></i> Work Mode: <strong>Hybrid</strong></p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="application-timeline">
                                <h6 class="mb-3">Application Timeline</h6>
                                <div class="timeline-item">
                                    <div>
                                        <div class="timeline-date">Jan 10, 2024</div>
                                        <div class="timeline-content timeline-completed">Application submitted</div>
                                    </div>
                                </div>
                                <div class="timeline-item">
                                    <div>
                                        <div class="timeline-date">Jan 12, 2024</div>
                                        <div class="timeline-content timeline-completed">Application received</div>
                                    </div>
                                </div>
                                <div class="timeline-item">
                                    <div>
                                        <div class="timeline-date">Jan 13, 2024</div>
                                        <div class="timeline-content timeline-completed">Application reviewed</div>
                                    </div>
                                </div>
                                <div class="timeline-item">
                                    <div>
                                        <div class="timeline-date">Jan 14, 2024</div>
                                        <div class="timeline-content timeline-completed">Interview scheduled for Jan 20, 2024</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="application-actions">
                                <button class="btn btn-outline-primary">
                                    <i class="bi bi-eye"></i> View Details
                                </button>
                                <button class="btn btn-outline-primary">
                                    <i class="bi bi-file-earmark-text"></i> View Application
                                </button>
                                <button class="btn btn-primary">
                                    <i class="bi bi-calendar-check"></i> View Interview Details
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Accepted Tab -->
                    <div class="tab-pane fade" id="accepted" role="tabpanel" aria-labelledby="accepted-tab">
                        <!-- Empty State for Accepted Applications -->
                        <div class="empty-state">
                            <i class="bi bi-check-circle"></i>
                            <h4>No Accepted Applications Yet</h4>
                            <p>When you get accepted for a position, it will appear here.</p>
                            <a href="job_listings.php" class="btn btn-primary">
                                <i class="bi bi-briefcase"></i> Browse Job Listings
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Search functionality
            const searchButton = document.querySelector('.search-button');
            const searchInput = document.querySelector('.search-bar input');
            
            searchButton.addEventListener('click', function() {
                performSearch();
            });
            
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    performSearch();
                }
            });
            
            function performSearch() {
                const searchTerm = searchInput.value.trim();
                if (searchTerm) {
                    // filter the applications based on the search term
                    const toast = document.createElement('div');
                    toast.className = 'position-fixed bottom-0 end-0 p-3';
                    toast.style.zIndex = '5';
                    toast.innerHTML = `
                        <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
                            <div class="toast-header">
                                <i class="bi bi-search me-2"></i>
                                <strong class="me-auto">Search</strong>
                                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                            </div>
                            <div class="toast-body">
                                Searching for: "${searchTerm}"
                            </div>
                        </div>
                    `;
                    document.body.appendChild(toast);
                    
                    setTimeout(() => {
                        toast.remove();
                    }, 3000);
                }
            }
            
            // View Details Button Functionality
            const viewDetailsButtons = document.querySelectorAll('.application-actions .btn-outline-primary');
            viewDetailsButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const jobTitle = this.closest('.application-card').querySelector('h5').textContent;
                    const companyName = this.closest('.application-card').querySelector('p.text-muted').textContent;
                    
                    // Show details modal
                    const modal = document.createElement('div');
                    modal.className = 'modal fade';
                    modal.id = 'detailsModal';
                    modal.setAttribute('tabindex', '-1');
                    modal.setAttribute('aria-labelledby', 'detailsModalLabel');
                    modal.setAttribute('aria-hidden', 'true');
                    modal.innerHTML = `
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="detailsModalLabel">Application Details</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="d-flex align-items-center mb-4">
                                        <img src="${this.closest('.application-card').querySelector('.company-logo').src}" alt="${companyName} Logo" class="company-logo me-3">
                                        <div>
                                            <h5 class="mb-1">${jobTitle}</h5>
                                            <p class="mb-0 text-muted">${companyName}</p>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <h6 class="mb-3">Application Information</h6>
                                            <p><strong>Application Date:</strong> ${this.closest('.application-card').querySelector('.application-details p:nth-child(1) strong').textContent}</p>
                                            <p><strong>Location:</strong> ${this.closest('.application-card').querySelector('.application-details p:nth-child(2) strong').textContent}</p>
                                            <p><strong>Job Type:</strong> ${this.closest('.application-card').querySelector('.application-details p:nth-child(3) strong').textContent}</p>
                                            <p><strong>Work Mode:</strong> ${this.closest('.application-card').querySelector('.application-details p:nth-child(4) strong').textContent}</p>
                                        </div>
                                        <div class="col-md-6">
                                            <h6 class="mb-3">Application Status</h6>
                                            <p><strong>Current Status:</strong> ${this.closest('.application-card').querySelector('.status-badge').textContent.trim()}</p>
                                            <p><strong>Last Updated:</strong> ${this.closest('.application-card').querySelector('.timeline-item:last-child .timeline-date').textContent}</p>
                                        </div>
                                    </div>
                                    
                                    <h6 class="mb-3">Application Timeline</h6>
                                    <div class="application-timeline">
                                        ${this.closest('.application-card').querySelector('.application-timeline').innerHTML}
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    `;
                    document.body.appendChild(modal);
                    
                    // Initialize and show the modal
                    const modalInstance = new bootstrap.Modal(modal);
                    modalInstance.show();
                    
                    // Remove modal from DOM after it's hidden
                    modal.addEventListener('hidden.bs.modal', function() {
                        modal.remove();
                    });
                });
            });
            
            // View Application Button Functionality
            const viewApplicationButtons = document.querySelectorAll('.application-actions .btn-outline-primary:nth-child(2)');
            viewApplicationButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const jobTitle = this.closest('.application-card').querySelector('h5').textContent;
                    const companyName = this.closest('.application-card').querySelector('p.text-muted').textContent;
                    
                    // Show application modal
                    const modal = document.createElement('div');
                    modal.className = 'modal fade';
                    modal.id = 'applicationModal';
                    modal.setAttribute('tabindex', '-1');
                    modal.setAttribute('aria-labelledby', 'applicationModalLabel');
                    modal.setAttribute('aria-hidden', 'true');
                    modal.innerHTML = `
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="applicationModalLabel">Your Application</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="d-flex align-items-center mb-4">
                                        <img src="${this.closest('.application-card').querySelector('.company-logo').src}" alt="${companyName} Logo" class="company-logo me-3">
                                        <div>
                                            <h5 class="mb-1">${jobTitle}</h5>
                                            <p class="mb-0 text-muted">${companyName}</p>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <h6 class="mb-3">Cover Letter</h6>
                                        <div class="p-3 bg-light rounded">
                                            <p>Dear Hiring Manager,</p>
                                            <p>I am writing to express my strong interest in the ${jobTitle} position at ${companyName}. As a student at BatStateU-NEU, I have developed a solid foundation in web development and am eager to apply my skills in a professional environment.</p>
                                            <p>During my academic career, I have completed several projects that demonstrate my ability to work with modern web technologies. I am particularly drawn to ${companyName}'s innovative approach to technology and believe my skills and enthusiasm would make me a valuable addition to your team.</p>
                                            <p>I am excited about the opportunity to contribute to your organization and would welcome the chance to discuss how my skills and experience align with your needs.</p>
                                            <p>Thank you for considering my application.</p>
                                            <p>Sincerely,<br>${document.querySelector('.logo-subtext').textContent}</p>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <h6 class="mb-3">Resume</h6>
                                        <div class="d-flex align-items-center p-3 bg-light rounded">
                                            <i class="bi bi-file-earmark-pdf fs-4 me-3 text-danger"></i>
                                            <div>
                                                <p class="mb-1">resume.pdf</p>
                                                <p class="mb-0 small text-muted">Uploaded on Jan 15, 2024</p>
                                            </div>
                                            <button class="btn btn-sm btn-outline-primary ms-auto">
                                                <i class="bi bi-download"></i> Download
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    `;
                    document.body.appendChild(modal);
                    
                    // Initialize and show the modal
                    const modalInstance = new bootstrap.Modal(modal);
                    modalInstance.show();
                    
                    // Remove modal from DOM after it's hidden
                    modal.addEventListener('hidden.bs.modal', function() {
                        modal.remove();
                    });
                });
            });
            
            // View Interview Details Button Functionality
            const viewInterviewButtons = document.querySelectorAll('.application-actions .btn-primary');
            viewInterviewButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const jobTitle = this.closest('.application-card').querySelector('h5').textContent;
                    const companyName = this.closest('.application-card').querySelector('p.text-muted').textContent;
                    
                    // Show interview details modal
                    const modal = document.createElement('div');
                    modal.className = 'modal fade';
                    modal.id = 'interviewModal';
                    modal.setAttribute('tabindex', '-1');
                    modal.setAttribute('aria-labelledby', 'interviewModalLabel');
                    modal.setAttribute('aria-hidden', 'true');
                    modal.innerHTML = `
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="interviewModalLabel">Interview Details</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="d-flex align-items-center mb-4">
                                        <img src="${this.closest('.application-card').querySelector('.company-logo').src}" alt="${companyName} Logo" class="company-logo me-3">
                                        <div>
                                            <h5 class="mb-1">${jobTitle}</h5>
                                            <p class="mb-0 text-muted">${companyName}</p>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <h6 class="mb-3">Interview Information</h6>
                                        <p><strong>Date:</strong> January 20, 2024</p>
                                        <p><strong>Time:</strong> 10:00 AM - 11:00 AM</p>
                                        <p><strong>Type:</strong> Virtual (Zoom)</p>
                                        <p><strong>Interviewer:</strong> John Smith, HR Manager</p>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <h6 class="mb-3">Interview Link</h6>
                                        <div class="input-group">
                                            <input type="text" class="form-control" value="https://zoom.us/j/123456789" readonly>
                                            <button class="btn btn-outline-primary" type="button">Copy</button>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <h6 class="mb-3">Preparation Tips</h6>
                                        <ul class="list-group">
                                            <li class="list-group-item">Research the company and its products/services</li>
                                            <li class="list-group-item">Review your resume and be prepared to discuss your experience</li>
                                            <li class="list-group-item">Prepare questions about the role and company</li>
                                            <li class="list-group-item">Test your camera and microphone before the interview</li>
                                            <li class="list-group-item">Find a quiet, well-lit space for the interview</li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <button type="button" class="btn btn-primary">
                                        <i class="bi bi-calendar-plus"></i> Add to Calendar
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                    document.body.appendChild(modal);
                    
                    // Initialize and show the modal
                    const modalInstance = new bootstrap.Modal(modal);
                    modalInstance.show();
                    
                    // Remove modal from DOM after it's hidden
                    modal.addEventListener('hidden.bs.modal', function() {
                        modal.remove();
                    });
                });
            });
        });
    </script>
</body>
</html> 