<?php
session_start();
if (!isset($_SESSION['student_id'])) {
    header('location: ../Frontend/login.html');
    exit();
}

// Helper function for JSON encoding
function safeJsonEncode($data) {
    return htmlspecialchars(
        json_encode($data, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP),
        ENT_QUOTES,
        'UTF-8'
    );
}

require_once '../Backend/Core/Config/DataManagement/DB_Operations.php';
$db = new SQL_Operations();
$conn = $db->getConnection();

// Fetch user's applications
$stmt = $conn->prepare("SELECT ja.*, jl.title, jl.description, jl.requirements,
                              c.name as company_name, c.id as company_id,
                              ja.created_at as application_date, ja.status,
                              jl.job_type, c.address as location,
                              (SELECT id FROM student_resumes WHERE user_id = ja.user_id LIMIT 1) as resume_id
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
                <div class="card mb-4">
                    <div class="card-body p-0">
                        <ul class="nav nav-tabs" id="applicationTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button" role="tab" aria-controls="all" aria-selected="true">
                                    <i class="bi bi-list-check"></i> All Applications
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button" role="tab" aria-controls="pending" aria-selected="false">
                                    <i class="bi bi-hourglass"></i> Pending
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="interview-tab" data-bs-toggle="tab" data-bs-target="#interview" type="button" role="tab" aria-controls="interview" aria-selected="false">
                                    <i class="bi bi-calendar-check"></i> Interviews
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="accepted-tab" data-bs-toggle="tab" data-bs-target="#accepted" type="button" role="tab" aria-controls="accepted" aria-selected="false">
                                    <i class="bi bi-check-circle"></i> Accepted
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
                
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
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>Company</th>
                                            <th>Position</th>
                                            <th>Applied On</th>
                                            <th>Status</th>
                                            <th>Timeline</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($applications as $app): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <img src="<?php echo htmlspecialchars('../Backend/Core/get_company_logo.php?id=' . $app['company_id']); ?>" 
                                                             alt="<?php echo htmlspecialchars($app['company_name']); ?> Logo" 
                                                             class="company-logo-sm me-2">
                                                        <div>
                                                            <?php echo htmlspecialchars($app['company_name']); ?>
                                                            <div class="text-muted small">
                                                                <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($app['location']); ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div>
                                                        <?php echo htmlspecialchars($app['title']); ?>
                                                        <div class="text-muted small">
                                                            <i class="bi bi-briefcase"></i> <?php echo htmlspecialchars($app['job_type']); ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php echo date('M j, Y', strtotime($app['application_date'])); ?>
                                                </td>
                                                <td>
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
                                                </td>
                                                <td>
                                                    <div class="timeline-compact">
                                                        <div class="timeline-dot timeline-completed" title="Application submitted on <?php echo date('M j, Y', strtotime($app['application_date'])); ?>"></div>
                                                        <?php if ($app['status'] != 'pending'): ?>
                                                            <div class="timeline-dot timeline-<?php echo $app['status']; ?>" 
                                                                 title="<?php 
                                                                    switch($app['status']) {
                                                                        case 'reviewing': echo 'Under review since '; break;
                                                                        case 'interview': echo 'Interview scheduled on '; break;
                                                                        case 'accepted': echo 'Accepted on '; break;
                                                                        case 'rejected': echo 'Not selected on '; break;
                                                                    }
                                                                    echo date('M j, Y', strtotime($app['updated_at']));
                                                                 ?>">
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary view-details" 
                                                            data-application='<?php echo safeJsonEncode($app); ?>'>
                                                        <i class="bi bi-eye"></i> View
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
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
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>Company</th>
                                            <th>Position</th>
                                            <th>Applied On</th>
                                            <th>Status</th>
                                            <th>Timeline</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pending_applications as $app): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <img src="<?php echo htmlspecialchars('../Backend/Core/get_company_logo.php?id=' . $app['company_id']); ?>" 
                                                             alt="<?php echo htmlspecialchars($app['company_name']); ?> Logo" 
                                                             class="company-logo-sm me-2">
                                                        <div>
                                                            <?php echo htmlspecialchars($app['company_name']); ?>
                                                            <div class="text-muted small">
                                                                <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($app['location']); ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div>
                                                        <?php echo htmlspecialchars($app['title']); ?>
                                                        <div class="text-muted small">
                                                            <i class="bi bi-briefcase"></i> <?php echo htmlspecialchars($app['job_type']); ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php echo date('M j, Y', strtotime($app['application_date'])); ?>
                                                </td>
                                                <td>
                                                    <span class="status-badge status-<?php echo strtolower($app['status']); ?>">
                                                        <i class="bi bi-<?php echo $app['status'] == 'pending' ? 'hourglass' : 'eye'; ?>"></i>
                                                        <?php echo ucfirst($app['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="timeline-compact">
                                                        <div class="timeline-dot timeline-completed" title="Application submitted on <?php echo date('M j, Y', strtotime($app['application_date'])); ?>"></div>
                                                        <?php if ($app['status'] == 'reviewing'): ?>
                                                            <div class="timeline-dot timeline-reviewing" title="Under review since <?php echo date('M j, Y', strtotime($app['updated_at'])); ?>"></div>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary view-details" 
                                                            data-application='<?php echo safeJsonEncode($app); ?>'>
                                                        <i class="bi bi-eye"></i> View
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
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
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>Company</th>
                                            <th>Position</th>
                                            <th>Applied On</th>
                                            <th>Status</th>
                                            <th>Timeline</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($interview_applications as $app): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <img src="<?php echo htmlspecialchars('../Backend/Core/get_company_logo.php?id=' . $app['company_id']); ?>" 
                                                             alt="<?php echo htmlspecialchars($app['company_name']); ?> Logo" 
                                                             class="company-logo-sm me-2">
                                                        <div>
                                                            <?php echo htmlspecialchars($app['company_name']); ?>
                                                            <div class="text-muted small">
                                                                <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($app['location']); ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div>
                                                        <?php echo htmlspecialchars($app['title']); ?>
                                                        <div class="text-muted small">
                                                            <i class="bi bi-briefcase"></i> <?php echo htmlspecialchars($app['job_type']); ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php echo date('M j, Y', strtotime($app['application_date'])); ?>
                                                </td>
                                                <td>
                                                    <span class="status-badge status-interview">
                                                        <i class="bi bi-calendar-check"></i>
                                                        Interview Scheduled
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="timeline-compact">
                                                        <div class="timeline-dot timeline-completed" title="Application submitted on <?php echo date('M j, Y', strtotime($app['application_date'])); ?>"></div>
                                                        <div class="timeline-dot timeline-interview" title="Interview scheduled on <?php echo date('M j, Y', strtotime($app['updated_at'])); ?>"></div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary view-details" 
                                                            data-application='<?php echo safeJsonEncode($app); ?>'>
                                                        <i class="bi bi-eye"></i> View
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
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
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>Company</th>
                                            <th>Position</th>
                                            <th>Applied On</th>
                                            <th>Status</th>
                                            <th>Timeline</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($accepted_applications as $app): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <img src="<?php echo htmlspecialchars('../Backend/Core/get_company_logo.php?id=' . $app['company_id']); ?>" 
                                                             alt="<?php echo htmlspecialchars($app['company_name']); ?> Logo" 
                                                             class="company-logo-sm me-2">
                                                        <div>
                                                            <?php echo htmlspecialchars($app['company_name']); ?>
                                                            <div class="text-muted small">
                                                                <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($app['location']); ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div>
                                                        <?php echo htmlspecialchars($app['title']); ?>
                                                        <div class="text-muted small">
                                                            <i class="bi bi-briefcase"></i> <?php echo htmlspecialchars($app['job_type']); ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php echo date('M j, Y', strtotime($app['application_date'])); ?>
                                                </td>
                                                <td>
                                                    <span class="status-badge status-accepted">
                                                        <i class="bi bi-check-circle"></i>
                                                        Application Accepted
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="timeline-compact">
                                                        <div class="timeline-dot timeline-completed" title="Application submitted on <?php echo date('M j, Y', strtotime($app['application_date'])); ?>"></div>
                                                        <div class="timeline-dot timeline-accepted" title="Application accepted on <?php echo date('M j, Y', strtotime($app['updated_at'])); ?>"></div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary view-details" 
                                                            data-application='<?php echo safeJsonEncode($app); ?>'>
                                                        <i class="bi bi-eye"></i> View
                                                    </button>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/Scripts/csrf.js"></script>
    <script src="../Assets/Scripts/user.js"></script>
</body>
</html>