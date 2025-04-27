<?php
session_start();
if (!isset($_SESSION['student_id'])) {
    header('location: ../Frontend/login.html');
    exit();
}

$student_id = $_SESSION['student_id'];
$student_name = $_SESSION['student_name'];

require_once '../Backend/Core/Config/DataManagement/DB_Operations.php';
$db = new SQL_Operations();
$conn = $db->getConnection();

$create_table_query = "CREATE TABLE IF NOT EXISTS student_resumes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    resume_path VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)";

if (!mysqli_query($conn, $create_table_query)) {
    die("Error creating table: " . mysqli_error($conn));
}

if (isset($_POST['upload_resume'])) {
    $target_dir = "uploads/resumes/";

    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $file_extension = strtolower(pathinfo($_FILES["resume_file"]["name"], PATHINFO_EXTENSION));
    $new_filename = "resume_" . $student_id . "_" . time() . "." . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    if ($_FILES["resume_file"]["size"] > 5000000) {
        $upload_error = "Sorry, your file is too large. Maximum size is 5MB.";
    }
    else if ($file_extension != "pdf" && $file_extension != "doc" && $file_extension != "docx") {
        $upload_error = "Sorry, only PDF, DOC & DOCX files are allowed.";
    }
    else {
        if (move_uploaded_file($_FILES["resume_file"]["tmp_name"], $target_file)) {
            $check_query = "SELECT * FROM student_resumes WHERE user_id = ?";
            $stmt = mysqli_prepare($conn, $check_query);
            mysqli_stmt_bind_param($stmt, "i", $student_id);
            $check_result = mysqli_stmt_execute($stmt);
            
            if (!$check_result) {
                $upload_error = "Database error: " . mysqli_error($conn);
            } else {
                $result = mysqli_stmt_get_result($stmt);
                if (mysqli_num_rows($result) > 0) {
                    $update_query = "UPDATE student_resumes SET resume_path = ? WHERE user_id = ?";
                    $stmt = mysqli_prepare($conn, $update_query);
                    mysqli_stmt_bind_param($stmt, "si", $target_file, $student_id);
                    
                    if (!mysqli_stmt_execute($stmt)) {
                        $upload_error = "Error updating record: " . mysqli_error($conn);
                    } else {
                        $upload_success = "Your resume has been uploaded successfully.";
                    }
                } else {
                    $insert_query = "INSERT INTO student_resumes (user_id, resume_path) VALUES (?, ?)";
                    $stmt = mysqli_prepare($conn, $insert_query);
                    mysqli_stmt_bind_param($stmt, "is", $student_id, $target_file);
                    
                    if (!mysqli_stmt_execute($stmt)) {
                        $upload_error = "Error inserting record: " . mysqli_error($conn);
                    } else {
                        $upload_success = "Your resume has been uploaded successfully.";
                    }
                }
            }
        } else {
            $upload_error = "Sorry, there was an error uploading your file.";
        }
    }
}

$resume_query = "SELECT * FROM student_resumes WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $resume_query);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$resume_result = mysqli_stmt_get_result($stmt);
$resume_data = null;

if ($resume_result) {
    $resume_data = mysqli_fetch_assoc($resume_result);
} else {
    error_log("Database error: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Resume</title>
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
        
        .resume-card {
            background: var(--pure-white);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .resume-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
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
        
        .upload-area {
            border: 2px dashed #ddd;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            margin-bottom: 20px;
            background-color: #f9f9f9;
            cursor: pointer;
        }
        
        .upload-area:hover {
            border-color: var(--primary-red);
        }
        
        .upload-icon {
            font-size: 3rem;
            color: #aaa;
            margin-bottom: 15px;
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
                    <a href="companies.php" class="nav-link">
                        <i class="bi bi-building-fill"></i> Partner Companies
                    </a>
                    <a href="resume.php" class="nav-link active">
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
                    <i class="bi bi-file-person-fill"></i>
                    <h4 class="mb-0">My Resume</h4>
                </div>

                <?php if (isset($upload_success)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $upload_success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <?php if (isset($upload_error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $upload_error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <!-- Resume Upload Card -->
                <div class="resume-card">
                    <h5 class="mb-3">Upload Your Resume</h5>
                    <p class="text-muted mb-3">Upload your resume in PDF, DOC, or DOCX format (max 5MB).</p>
                    
                    <form action="resume.php" method="post" enctype="multipart/form-data">
                        <div class="upload-area" id="uploadArea">
                            <i class="bi bi-cloud-upload upload-icon"></i>
                            <h5>Drag & Drop your resume here</h5>
                            <p>or</p>
                            <input type="file" name="resume_file" id="resumeFile" class="d-none" accept=".pdf,.doc,.docx">
                            <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('resumeFile').click()">
                                Browse Files
                            </button>
                            <p class="mt-2" id="selectedFile">No file selected</p>
                        </div>
                        
                        <button type="submit" name="upload_resume" class="btn btn-primary">
                            <i class="bi bi-upload me-2"></i> Upload Resume
                        </button>
                    </form>
                </div>

                <!-- Current Resume Card -->
                <?php if ($resume_data): ?>
                <div class="resume-card">
                    <h5 class="mb-3">Your Current Resume</h5>
                    <div class="d-flex align-items-center mb-3">
                        <i class="bi bi-file-earmark-text me-2" style="font-size: 2rem; color: var(--primary-red);"></i>
                        <div>
                            <h6 class="mb-0"><?php echo basename($resume_data['resume_path']); ?></h6>
                            <small class="text-muted">Uploaded on: <?php echo date('F j, Y', strtotime($resume_data['uploaded_at'])); ?></small>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="<?php echo $resume_data['resume_path']; ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                            <i class="bi bi-eye me-1"></i> View
                        </a>
                        <a href="<?php echo $resume_data['resume_path']; ?>" class="btn btn-sm btn-outline-success" download>
                            <i class="bi bi-download me-1"></i> Download
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // File selection display
        document.getElementById('resumeFile').addEventListener('change', function() {
            const fileName = this.files[0] ? this.files[0].name : 'No file selected';
            document.getElementById('selectedFile').textContent = fileName;
        });
        
        // Drag and drop functionality
        const uploadArea = document.getElementById('uploadArea');
        
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        ['dragenter', 'dragover'].forEach(eventName => {
            uploadArea.addEventListener(eventName, highlight, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, unhighlight, false);
        });
        
        function highlight() {
            uploadArea.classList.add('border-primary');
        }
        
        function unhighlight() {
            uploadArea.classList.remove('border-primary');
        }
        
        uploadArea.addEventListener('drop', handleDrop, false);
        
        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            
            document.getElementById('resumeFile').files = files;
            document.getElementById('selectedFile').textContent = files[0].name;
        }
    </script>
</body>
</html>