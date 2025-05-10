<?php
session_start();
if (!isset($_SESSION['student_id'])) {
    header('location: ../Frontend/login.html');
    exit();
}

require_once '../Backend/Core/Config/DataManagement/DB_Operations.php';
$db = new SQL_Operations();
$conn = $db->getConnection();

// Fetch user's applications
$stmt = $conn->prepare("SELECT ja.*, jl.title, jl.description, jl.requirements,
                              c.name as company_name, c.id as company_id,
                              ja.created_at as application_date, ja.status,
                              ja.cover_letter, ja.resume_path, jl.job_type,
                              jl.location
                       FROM job_applications ja 
                       JOIN job_listings jl ON ja.job_id = jl.id 
                       JOIN companies c ON jl.company_id = c.id 
                       WHERE ja.user_id = ?
                       ORDER BY ja.created_at DESC");
$stmt->bind_param('i', $_SESSION['student_id']);
$stmt->execute();
$result = $stmt->get_result();
$applications = [];
$pending_applications = [];
$interview_applications = [];
$accepted_applications = [];

while ($row = $result->fetch_assoc()) {
    $applications[] = $row;
    switch($row['status']) {
        case 'pending':
        case 'reviewing':
            $pending_applications[] = $row;
            break;
        case 'interview':
            $interview_applications[] = $row;
            break;
        case 'accepted':
            $accepted_applications[] = $row;
            break;
    }
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
                        <?php if (empty($applications)): ?>
                            <div class="empty-state">
                                <i class="bi bi-file-earmark-text"></i>
                                <h4>No Applications Yet</h4>
                                <p>You haven't applied to any positions yet.</p>
                                <a href="job_listings.php" class="btn btn-primary">
                                    <i class="bi bi-briefcase"></i> Browse Job Listings
                                </a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($applications as $app): ?>
                                <div class="application-card">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo htmlspecialchars('../Backend/Core/get_company_logo.php?id=' . $app['company_id']); ?>" 
                                                 alt="<?php echo htmlspecialchars($app['company_name']); ?> Logo" 
                                                 class="company-logo me-3">
                                            <div>
                                                <h5 class="mb-1"><?php echo htmlspecialchars($app['title']); ?></h5>
                                                <p class="mb-0 text-muted"><?php echo htmlspecialchars($app['company_name']); ?></p>
                                            </div>
                                        </div>
                                        <span class="status-badge status-<?php echo strtolower($app['status']); ?>">
                                            <?php 
                                            $icon = '';
                                            switch($app['status']) {
                                                case 'pending': $icon = 'hourglass'; break;
                                                case 'reviewing': $icon = 'eye'; break;
                                                case 'interview': $icon = 'calendar-check'; break;
                                                case 'accepted': $icon = 'check-circle'; break;
                                                case 'rejected': $icon = 'x-circle'; break;
                                            }
                                            ?>
                                            <i class="bi bi-<?php echo $icon; ?>"></i>
                                            <?php echo ucfirst($app['status']); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="application-details">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p class="mb-2">
                                                    <i class="bi bi-calendar3"></i> Applied on: 
                                                    <strong><?php echo date('M j, Y', strtotime($app['application_date'])); ?></strong>
                                                </p>
                                                <p class="mb-2">
                                                    <i class="bi bi-geo-alt"></i> Location: 
                                                    <strong><?php echo htmlspecialchars($app['location']); ?></strong>
                                                </p>
                                            </div>
                                            <div class="col-md-6">
                                                <p class="mb-2">
                                                    <i class="bi bi-briefcase"></i> Job Type: 
                                                    <strong><?php echo htmlspecialchars($app['job_type']); ?></strong>
                                                </p>
                                                <p class="mb-2">
                                                    <i class="bi bi-geo-alt"></i> Work Location: 
                                                    <strong><?php echo htmlspecialchars($app['location']); ?></strong>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="application-timeline">
                                        <h6 class="mb-3">Application Timeline</h6>
                                        <div class="timeline-item">
                                            <div>
                                                <div class="timeline-date">
                                                    <?php echo date('M j, Y', strtotime($app['application_date'])); ?>
                                                </div>
                                                <div class="timeline-content timeline-completed">
                                                    Application submitted
                                                </div>
                                            </div>
                                        </div>
                                        <?php if ($app['status'] != 'pending'): ?>
                                            <div class="timeline-item">
                                                <div>
                                                    <div class="timeline-date">
                                                        <?php echo date('M j, Y', strtotime($app['updated_at'])); ?>
                                                    </div>
                                                    <div class="timeline-content timeline-<?php echo $app['status']; ?>">
                                                        <?php 
                                                        switch($app['status']) {
                                                            case 'reviewing': echo 'Application under review'; break;
                                                            case 'interview': echo 'Interview scheduled'; break;
                                                            case 'accepted': echo 'Application accepted'; break;
                                                            case 'rejected': echo 'Application not selected'; break;
                                                        }
                                                        ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="application-actions">
                                        <button class="btn btn-outline-primary view-details" 
                                                data-application='<?php echo json_encode($app); ?>'>
                                            <i class="bi bi-eye"></i> View Details
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Pending Tab -->
                    <div class="tab-pane fade" id="pending" role="tabpanel" aria-labelledby="pending-tab">
                        <?php if (empty($pending_applications)): ?>
                            <div class="empty-state">
                                <i class="bi bi-hourglass"></i>
                                <h4>No Pending Applications</h4>
                                <p>You don't have any applications under review at the moment.</p>
                                <a href="job_listings.php" class="btn btn-primary">
                                    <i class="bi bi-briefcase"></i> Browse Job Listings
                                </a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($pending_applications as $app): ?>
                                <div class="application-card">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo htmlspecialchars('../Backend/Core/get_company_logo.php?id=' . $app['company_id']); ?>" 
                                                 alt="<?php echo htmlspecialchars($app['company_name']); ?> Logo" 
                                                 class="company-logo me-3">
                                            <div>
                                                <h5 class="mb-1"><?php echo htmlspecialchars($app['title']); ?></h5>
                                                <p class="mb-0 text-muted"><?php echo htmlspecialchars($app['company_name']); ?></p>
                                            </div>
                                        </div>
                                        <span class="status-badge status-<?php echo strtolower($app['status']); ?>">
                                            <?php 
                                            $icon = '';
                                            switch($app['status']) {
                                                case 'pending': $icon = 'hourglass'; break;
                                                case 'reviewing': $icon = 'eye'; break;
                                            }
                                            ?>
                                            <i class="bi bi-<?php echo $icon; ?>"></i>
                                            <?php echo ucfirst($app['status']); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="application-details">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p class="mb-2">
                                                    <i class="bi bi-calendar3"></i> Applied on: 
                                                    <strong><?php echo date('M j, Y', strtotime($app['application_date'])); ?></strong>
                                                </p>
                                                <p class="mb-2">
                                                    <i class="bi bi-geo-alt"></i> Location: 
                                                    <strong><?php echo htmlspecialchars($app['location']); ?></strong>
                                                </p>
                                            </div>
                                            <div class="col-md-6">
                                                <p class="mb-2">
                                                    <i class="bi bi-briefcase"></i> Job Type: 
                                                    <strong><?php echo htmlspecialchars($app['job_type']); ?></strong>
                                                </p>
                                                <p class="mb-2">
                                                    <i class="bi bi-geo-alt"></i> Work Location: 
                                                    <strong><?php echo htmlspecialchars($app['location']); ?></strong>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="application-timeline">
                                        <h6 class="mb-3">Application Timeline</h6>
                                        <div class="timeline-item">
                                            <div>
                                                <div class="timeline-date">
                                                    <?php echo date('M j, Y', strtotime($app['application_date'])); ?>
                                                </div>
                                                <div class="timeline-content timeline-completed">
                                                    Application submitted
                                                </div>
                                            </div>
                                        </div>
                                        <?php if ($app['status'] == 'reviewing'): ?>
                                            <div class="timeline-item">
                                                <div>
                                                    <div class="timeline-date">
                                                        <?php echo date('M j, Y', strtotime($app['updated_at'])); ?>
                                                    </div>
                                                    <div class="timeline-content timeline-<?php echo $app['status']; ?>">
                                                        Application under review
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="application-actions">
                                        <button class="btn btn-outline-primary view-details" 
                                                data-application='<?php echo json_encode($app); ?>'>
                                            <i class="bi bi-eye"></i> View Details
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Interview Tab -->
                    <div class="tab-pane fade" id="interview" role="tabpanel" aria-labelledby="interview-tab">
                        <?php if (empty($interview_applications)): ?>
                            <div class="empty-state">
                                <i class="bi bi-calendar-check"></i>
                                <h4>No Scheduled Interviews</h4>
                                <p>You don't have any scheduled interviews at the moment.</p>
                                <a href="job_listings.php" class="btn btn-primary">
                                    <i class="bi bi-briefcase"></i> Browse Job Listings
                                </a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($interview_applications as $app): ?>
                                <div class="application-card">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo htmlspecialchars('../Backend/Core/get_company_logo.php?id=' . $app['company_id']); ?>" 
                                                 alt="<?php echo htmlspecialchars($app['company_name']); ?> Logo" 
                                                 class="company-logo me-3">
                                            <div>
                                                <h5 class="mb-1"><?php echo htmlspecialchars($app['title']); ?></h5>
                                                <p class="mb-0 text-muted"><?php echo htmlspecialchars($app['company_name']); ?></p>
                                            </div>
                                        </div>
                                        <span class="status-badge status-<?php echo strtolower($app['status']); ?>">
                                            <i class="bi bi-calendar-check"></i>
                                            Interview Scheduled
                                        </span>
                                    </div>
                                    
                                    <div class="application-details">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p class="mb-2">
                                                    <i class="bi bi-calendar3"></i> Applied on: 
                                                    <strong><?php echo date('M j, Y', strtotime($app['application_date'])); ?></strong>
                                                </p>
                                                <p class="mb-2">
                                                    <i class="bi bi-geo-alt"></i> Location: 
                                                    <strong><?php echo htmlspecialchars($app['location']); ?></strong>
                                                </p>
                                            </div>
                                            <div class="col-md-6">
                                                <p class="mb-2">
                                                    <i class="bi bi-briefcase"></i> Job Type: 
                                                    <strong><?php echo htmlspecialchars($app['job_type']); ?></strong>
                                                </p>
                                                <p class="mb-2">
                                                    <i class="bi bi-geo-alt"></i> Work Location: 
                                                    <strong><?php echo htmlspecialchars($app['location']); ?></strong>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="application-timeline">
                                        <h6 class="mb-3">Application Timeline</h6>
                                        <div class="timeline-item">
                                            <div>
                                                <div class="timeline-date">
                                                    <?php echo date('M j, Y', strtotime($app['application_date'])); ?>
                                                </div>
                                                <div class="timeline-content timeline-completed">
                                                    Application submitted
                                                </div>
                                            </div>
                                        </div>
                                        <div class="timeline-item">
                                            <div>
                                                <div class="timeline-date">
                                                    <?php echo date('M j, Y', strtotime($app['updated_at'])); ?>
                                                </div>
                                                <div class="timeline-content timeline-<?php echo $app['status']; ?>">
                                                    Interview scheduled
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="application-actions">
                                        <button class="btn btn-outline-primary view-details" 
                                                data-application='<?php echo json_encode($app); ?>'>
                                            <i class="bi bi-eye"></i> View Details
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Accepted Tab -->
                    <div class="tab-pane fade" id="accepted" role="tabpanel" aria-labelledby="accepted-tab">
                        <?php if (empty($accepted_applications)): ?>
                            <div class="empty-state">
                                <i class="bi bi-check-circle"></i>
                                <h4>No Accepted Applications Yet</h4>
                                <p>When you get accepted for a position, it will appear here.</p>
                                <a href="job_listings.php" class="btn btn-primary">
                                    <i class="bi bi-briefcase"></i> Browse Job Listings
                                </a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($accepted_applications as $app): ?>
                                <div class="application-card">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo htmlspecialchars('../Backend/Core/get_company_logo.php?id=' . $app['company_id']); ?>" 
                                                 alt="<?php echo htmlspecialchars($app['company_name']); ?> Logo" 
                                                 class="company-logo me-3">
                                            <div>
                                                <h5 class="mb-1"><?php echo htmlspecialchars($app['title']); ?></h5>
                                                <p class="mb-0 text-muted"><?php echo htmlspecialchars($app['company_name']); ?></p>
                                            </div>
                                        </div>
                                        <span class="status-badge status-<?php echo strtolower($app['status']); ?>">
                                            <i class="bi bi-check-circle"></i>
                                            Application Accepted
                                        </span>
                                    </div>
                                    
                                    <div class="application-details">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p class="mb-2">
                                                    <i class="bi bi-calendar3"></i> Applied on: 
                                                    <strong><?php echo date('M j, Y', strtotime($app['application_date'])); ?></strong>
                                                </p>
                                                <p class="mb-2">
                                                    <i class="bi bi-geo-alt"></i> Location: 
                                                    <strong><?php echo htmlspecialchars($app['location']); ?></strong>
                                                </p>
                                            </div>
                                            <div class="col-md-6">
                                                <p class="mb-2">
                                                    <i class="bi bi-briefcase"></i> Job Type: 
                                                    <strong><?php echo htmlspecialchars($app['job_type']); ?></strong>
                                                </p>
                                                <p class="mb-2">
                                                    <i class="bi bi-geo-alt"></i> Work Location: 
                                                    <strong><?php echo htmlspecialchars($app['location']); ?></strong>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="application-timeline">
                                        <h6 class="mb-3">Application Timeline</h6>
                                        <div class="timeline-item">
                                            <div>
                                                <div class="timeline-date">
                                                    <?php echo date('M j, Y', strtotime($app['application_date'])); ?>
                                                </div>
                                                <div class="timeline-content timeline-completed">
                                                    Application submitted
                                                </div>
                                            </div>
                                        </div>
                                        <div class="timeline-item">
                                            <div>
                                                <div class="timeline-date">
                                                    <?php echo date('M j, Y', strtotime($app['updated_at'])); ?>
                                                </div>
                                                <div class="timeline-content timeline-<?php echo $app['status']; ?>">
                                                    Application accepted
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="application-actions">
                                        <button class="btn btn-outline-primary view-details" 
                                                data-application='<?php echo json_encode($app); ?>'>
                                            <i class="bi bi-eye"></i> View Details
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/Scripts/csrf.js"></script>
    <script src="../Assets/Scripts/user.js"></script>
</body>
</html>