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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
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
        
        .profile-card {
            background: var(--pure-white);
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            border-bottom: 1px solid #eee;
            padding-bottom: 20px;
        }
        
        .profile-header i {
            font-size: 2.5rem;
            margin-right: 15px;
            color: var(--primary-red);
        }
        
        .profile-data {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 30px;
        }
        
        .profile-field {
            margin-bottom: 0;
        }
        
        .profile-field label {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--dark-gray);
            margin-bottom: 8px;
            display: block;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .profile-field p {
            margin: 0;
            color: var(--dark-gray);
            font-size: 1rem;
            padding: 8px 12px;
            background: var(--light-gray);
            border-radius: 6px;
        }

        .profile-field .badge {
            font-size: 0.875rem;
            padding: 8px 12px;
        }

        .main-content {
            margin-left: 16.666667%;
        }

        @media (max-width: 768px) {
            .profile-data {
                grid-template-columns: 1fr;
                gap: 20px;
            }
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
                <div class="section-header mb-4">
                    <div class="profile-header">
                        <i class="bi bi-person-circle"></i>
                        <h4 class="mb-0">My Profile</h4>
                    </div>
                </div>

                <div class="profile-card">
                    <div class="profile-data">
                        <div class="profile-field">
                            <label>SR Code</label>
                            <p><?php echo htmlspecialchars($user_data['srcode']); ?></p>
                        </div>
                        <div class="profile-field">
                            <label>Full Name</label>
                            <p><?php echo htmlspecialchars($user_data['firstname'] . ' ' . $user_data['lastname']); ?></p>
                        </div>
                        <div class="profile-field">
                            <label>Email</label>
                            <p><?php echo htmlspecialchars($user_data['email']); ?></p>
                        </div>
                        <div class="profile-field">
                            <label>Department</label>
                            <p><?php echo htmlspecialchars($user_data['department_name']); ?></p>
                        </div>
                        <div class="profile-field">
                            <label>Course</label>
                            <p><?php echo htmlspecialchars($user_data['course_name']); ?></p>
                        </div>
                        <div class="profile-field">
                            <label>Section</label>
                            <p><?php echo htmlspecialchars($user_data['section']); ?></p>
                        </div>
                        <div class="profile-field">
                            <label>Account Status</label>
                            <p><span class="badge bg-<?php echo $user_data['status'] === 'active' ? 'success' : 'warning'; ?>">
                                <?php echo ucfirst(htmlspecialchars($user_data['status'])); ?>
                            </span></p>
                        </div>
                        <div class="profile-field">
                            <label>Account Created</label>
                            <p><?php echo date('F j, Y', strtotime($user_data['created_at'])); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>