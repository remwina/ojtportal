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

// Handle resume deletion
if (isset($_POST['delete_resume'])) {
    $resume_id = $_POST['delete_resume'];
    
    // Delete from database
    $delete_query = "DELETE FROM student_resumes WHERE id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $delete_query);
    mysqli_stmt_bind_param($stmt, "ii", $resume_id, $student_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $upload_success = "Resume has been deleted successfully.";
    } else {
        $upload_error = "Error deleting resume: " . mysqli_error($conn);
    }
}

// Create table if not exists
$create_table_query = "CREATE TABLE IF NOT EXISTS student_resumes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    resume_data LONGBLOB,
    resume_name VARCHAR(255),
    resume_type VARCHAR(100),
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if (!mysqli_query($conn, $create_table_query)) {
    die("Error creating table: " . mysqli_error($conn));
}

// Handle resume upload
if (isset($_POST['upload_resume'])) {
    $file_extension = strtolower(pathinfo($_FILES["resume_file"]["name"], PATHINFO_EXTENSION));
    
    if ($_FILES["resume_file"]["size"] > 5000000) {
        $upload_error = "Sorry, your file is too large. Maximum size is 5MB.";
    }
    else if ($file_extension != "pdf" && $file_extension != "doc" && $file_extension != "docx") {
        $upload_error = "Sorry, only PDF, DOC & DOCX files are allowed.";
    }
    else {
        // Read file content
        $resume_data = file_get_contents($_FILES["resume_file"]["tmp_name"]);
        $resume_type = $_FILES["resume_file"]["type"];
        $resume_name = $_FILES["resume_file"]["name"];

        // Check if user already has a resume
        $check_query = "SELECT * FROM student_resumes WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($stmt, "i", $student_id);
        $check_result = mysqli_stmt_execute($stmt);
        
        if (!$check_result) {
            $upload_error = "Database error: " . mysqli_error($conn);
        } else {
            $result = mysqli_stmt_get_result($stmt);
            if (mysqli_num_rows($result) > 0) {
                $update_query = "UPDATE student_resumes SET resume_data = ?, resume_name = ?, resume_type = ? WHERE user_id = ?";
                $stmt = mysqli_prepare($conn, $update_query);
                mysqli_stmt_bind_param($stmt, "bssi", $resume_data, $resume_name, $resume_type, $student_id);
                
                if (!mysqli_stmt_execute($stmt)) {
                    $upload_error = "Error updating record: " . mysqli_error($conn);
                } else {
                    $upload_success = "Your resume has been updated successfully.";
                }
            } else {
                $insert_query = "INSERT INTO student_resumes (user_id, resume_data, resume_name, resume_type) VALUES (?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $insert_query);
                mysqli_stmt_bind_param($stmt, "ibss", $student_id, $resume_data, $resume_name, $resume_type);
                
                if (!mysqli_stmt_execute($stmt)) {
                    $upload_error = "Error inserting record: " . mysqli_error($conn);
                } else {
                    $upload_success = "Your resume has been uploaded successfully.";
                }
            }
        }
    }
}

// Get current resume
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">    <title>My Resume</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../Assets/Styles/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</head>
<body>
    <div class="container-fluid">
        <div class="row">            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <div class="logo-container">
                    <img src="BatStateU-NEU-Logo.png" alt="BatStateU-NEU Logo" class="logo">                    <div class="logo-text">BatState-U NEU</div>
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
            </div>            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 p-4 main-content">
                <!-- Section Header with Profile -->
                <div class="section-header d-flex justify-content-between align-items-center mb-4">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-file-person-fill me-2"></i>
                        <h4 class="mb-0">My Resume</h4>
                    </div>
                    <div class="profile-section">
                        <i class="bi bi-person-circle profile-icon"></i>
                        <span class="ms-2"><?php echo htmlspecialchars($student_name); ?></span>
                    </div>
                </div>

                <?php if (isset($upload_success)): ?>
                <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i><?php echo $upload_success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <?php if (isset($upload_error)): ?>
                <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                    <i class="bi bi-exclamation-circle-fill me-2"></i><?php echo $upload_error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>                <!-- Current Resume Card -->
                <?php if ($resume_data): ?>
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-file-earmark-text me-2 text-primary"></i>
                            <h5 class="mb-0">Your Current Resume</h5>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="file-preview d-flex align-items-center p-3 bg-light rounded">
                            <i class="bi bi-file-earmark-pdf fs-2 text-primary me-3"></i>
                            <div>
                                <h6 class="mb-1"><?php echo htmlspecialchars($resume_data['resume_name']); ?></h6>
                                <small class="text-muted">
                                    <i class="bi bi-calendar-event me-1"></i>
                                    Uploaded on: <?php echo date('F j, Y', strtotime($resume_data['uploaded_at'])); ?>
                                </small>
                            </div>
                        </div>
                        <div class="d-flex gap-2 mt-4">
                            <a href="../Backend/Core/get_resume.php?id=<?php echo $resume_data['id']; ?>" class="btn btn-primary px-4" target="_blank">
                                <i class="bi bi-eye me-2"></i>View Resume
                            </a>
                            <a href="../Backend/Core/get_resume.php?id=<?php echo $resume_data['id']; ?>&download=true" class="btn btn-outline-primary px-4" download>
                                <i class="bi bi-download me-2"></i>Download Resume
                            </a>
                            <button type="button" class="btn btn-outline-danger px-4" data-bs-toggle="modal" data-bs-target="#deleteResumeModal">
                                <i class="bi bi-trash me-2"></i>Delete Resume
                            </button>
                        </div>
                    </div>
                </div>
                <?php endif; ?>                <!-- Resume Upload Card -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-cloud-upload me-2 text-primary"></i>
                            <h5 class="mb-0"><?php echo $resume_data ? 'Update Resume' : 'Upload Resume'; ?></h5>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Upload your resume in PDF, DOC, or DOCX format (max 5MB).</p>
                        
                        <form action="resume.php" method="post" enctype="multipart/form-data">
                            <div class="upload-area border rounded p-5 text-center" id="uploadArea">
                                <i class="bi bi-cloud-upload fs-1 text-primary mb-3 d-block"></i>
                                <h5 class="mb-3">Drag & Drop your resume here</h5>
                                <p class="text-muted mb-3">or</p>
                                <input type="file" name="resume_file" id="resumeFile" class="d-none" accept=".pdf,.doc,.docx">
                                <button type="button" class="btn btn-outline-primary px-4" onclick="document.getElementById('resumeFile').click()">
                                    <i class="bi bi-folder2-open me-2"></i>Browse Files
                                </button>
                                <p class="mt-3 text-muted" id="selectedFile">No file selected</p>
                            </div>
                            
                            <div class="mt-3">
                                <button type="submit" name="upload_resume" class="btn btn-primary px-4">
                                    <i class="bi bi-upload me-2"></i><?php echo $resume_data ? 'Update Resume' : 'Upload Resume'; ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>                <!-- Delete Resume Modal -->
                <?php if ($resume_data): ?>
                <div class="modal fade" id="deleteResumeModal" tabindex="-1" aria-labelledby="deleteResumeModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0 shadow">
                            <div class="modal-header border-bottom">
                                <h5 class="modal-title" id="deleteResumeModalLabel">
                                    <i class="bi bi-exclamation-triangle-fill text-danger me-2"></i>
                                    Delete Resume
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p class="mb-0">Are you sure you want to delete your resume? This action cannot be undone.</p>
                            </div>
                            <div class="modal-footer border-top">
                                <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Cancel</button>
                                <form action="resume.php" method="post" class="d-inline">
                                    <input type="hidden" name="delete_resume" value="<?php echo $resume_data['id']; ?>">
                                    <button type="submit" class="btn btn-outline-danger">
                                        <i class="bi bi-trash me-2"></i>Delete Resume
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>    <!-- Load scripts in correct order -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // File selection display
        document.getElementById('resumeFile').addEventListener('change', function() {
            const fileName = this.files[0] ? this.files[0].name : 'No file selected';
            document.getElementById('selectedFile').textContent = fileName;
            
            // Validate file size and type
            if (this.files[0]) {
                if (this.files[0].size > 5000000) {
                    Swal.fire({
                        icon: 'error',
                        title: 'File Too Large',
                        text: 'Maximum file size allowed is 5MB.',
                        confirmButtonColor: '#0d6efd'
                    });
                    this.value = '';
                    document.getElementById('selectedFile').textContent = 'No file selected';
                }
                
                const fileType = this.files[0].name.split('.').pop().toLowerCase();
                if (!['pdf', 'doc', 'docx'].includes(fileType)) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Invalid File Type',
                        text: 'Only PDF, DOC, and DOCX files are allowed.',
                        confirmButtonColor: '#0d6efd'
                    });
                    this.value = '';
                    document.getElementById('selectedFile').textContent = 'No file selected';
                }
            }
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
            uploadArea.classList.add('drag-over');
        }
        
        function unhighlight() {
            uploadArea.classList.remove('drag-over');
        }
        
        uploadArea.addEventListener('drop', handleDrop, false);
        
        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            
            document.getElementById('resumeFile').files = files;
            document.getElementById('selectedFile').textContent = files[0].name;
            
            // Validate file on drop
            if (files[0]) {
                if (files[0].size > 5000000) {
                    Swal.fire({
                        icon: 'error',
                        title: 'File Too Large',
                        text: 'Maximum file size allowed is 5MB.',
                        confirmButtonColor: '#0d6efd'
                    });
                    document.getElementById('resumeFile').value = '';
                    document.getElementById('selectedFile').textContent = 'No file selected';
                    return;
                }
                
                const fileType = files[0].name.split('.').pop().toLowerCase();
                if (!['pdf', 'doc', 'docx'].includes(fileType)) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Invalid File Type',
                        text: 'Only PDF, DOC, and DOCX files are allowed.',
                        confirmButtonColor: '#0d6efd'
                    });
                    document.getElementById('resumeFile').value = '';
                    document.getElementById('selectedFile').textContent = 'No file selected';
                }
            }
        }
    </script>
</body>
</html>
