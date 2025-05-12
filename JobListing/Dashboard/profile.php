<?php
session_start();
if (!isset($_SESSION['student_id'])) {
    header('location: ../Frontend/login.html');
    exit();
}

require_once '../Backend/Core/Config/DataManagement/DB_Operations.php';
$db = new SQL_Operations();
$conn = $db->getConnection();

$student_id = $_SESSION['student_id'];
$student_name = $_SESSION['student_name'];

// Get user details including course and department information
$query = "SELECT u.*, c.name as course_name, d.name as department_name 
          FROM users u 
          LEFT JOIN courses c ON u.course_id = c.id 
          LEFT JOIN departments d ON c.department_id = d.id 
          WHERE u.id = ?";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user_data = mysqli_fetch_assoc($result);

// If no user data is found, use session data as fallback
if (!$user_data) {
    $user_data = [
        'srcode' => $_SESSION['srcode'] ?? 'N/A',
        'firstname' => explode(' ', $_SESSION['student_name'])[0] ?? 'N/A',
        'lastname' => explode(' ', $_SESSION['student_name'])[1] ?? '',
        'email' => $_SESSION['email'] ?? 'N/A',
        'course_name' => 'Not set',
        'department_name' => 'Not set',
        'section' => $_SESSION['section'] ?? 'N/A',
        'status' => 'active',
        'created_at' => date('Y-m-d H:i:s')
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../Assets/Styles/admin.css">
    <!-- Add SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .card {
            transition: transform 0.2s ease-in-out;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .profile-info {
            transition: all 0.3s ease;
        }
        .profile-info:hover {
            background-color: #f8f9fa;
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
                    <a href="companies.php" class="nav-link">
                        <i class="bi bi-building-fill"></i> Partner Companies
                    </a>
                    <a href="resume.php" class="nav-link">
                        <i class="bi bi-file-person-fill"></i> My Resume
                    </a>
                    <a href="profile.php" class="nav-link active">
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
                            <i class="bi bi-person-fill me-2"></i>
                            <h4 class="mb-0">My Profile</h4>
                        </div>
                        <div class="text-muted small mt-1">Manage your personal information and settings</div>
                    </div>
                    <div class="profile-section">
                        <i class="bi bi-person-circle profile-icon"></i>
                        <span class="ms-2"><?php echo htmlspecialchars($student_name); ?></span>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-person-vcard me-2 text-primary"></i>
                            <h5 class="mb-0">Profile Information</h5>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="profile-data">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="profile-info p-3 rounded">
                                    <label class="text-muted mb-1">SR Code</label>
                                    <h6 class="mb-0"><?php echo htmlspecialchars($user_data['srcode'] ?? 'N/A'); ?></h6>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="profile-info p-3 rounded">
                                    <label class="text-muted mb-1">Full Name</label>
                                    <h6 class="mb-0"><?php echo htmlspecialchars(($user_data['firstname'] ?? '') . ' ' . ($user_data['lastname'] ?? '')); ?></h6>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="profile-info p-3 rounded">
                                    <label class="text-muted mb-1">Email</label>
                                    <h6 class="mb-0"><?php echo htmlspecialchars($user_data['email'] ?? 'N/A'); ?></h6>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="profile-info p-3 rounded">
                                    <label class="text-muted mb-1">Department</label>
                                    <h6 class="mb-0"><?php echo htmlspecialchars($user_data['department_name'] ?? 'Not set'); ?></h6>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="profile-info p-3 rounded">
                                    <label class="text-muted mb-1">Course</label>
                                    <h6 class="mb-0"><?php echo htmlspecialchars($user_data['course_name'] ?? 'Not set'); ?></h6>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="profile-info p-3 rounded">
                                    <label class="text-muted mb-1">Section</label>
                                    <h6 class="mb-0"><?php echo htmlspecialchars($user_data['section'] ?? 'Not set'); ?></h6>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="profile-info p-3 rounded">
                                    <label class="text-muted mb-1">Account Status</label>
                                    <h6 class="mb-0">
                                        <span class="badge bg-<?php echo ($user_data['status'] ?? 'active') === 'active' ? 'success' : 'warning'; ?>">
                                            <?php echo ucfirst(htmlspecialchars($user_data['status'] ?? 'active')); ?>
                                        </span>
                                    </h6>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="profile-info p-3 rounded">
                                    <label class="text-muted mb-1">Account Created</label>
                                    <h6 class="mb-0"><?php 
                                        $created_at = $user_data['created_at'] ?? null;
                                        echo $created_at ? date('F j, Y', strtotime($created_at)) : 'Not available';
                            ?></h6>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Load scripts in correct order -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>