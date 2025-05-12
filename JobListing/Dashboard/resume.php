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

// Handle Delete Request
if (isset($_POST['delete_resume'])) {
    $delete_query = "DELETE FROM student_resumes WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $delete_query);
    mysqli_stmt_bind_param($stmt, "i", $student_id);
    
    if (mysqli_stmt_execute($stmt)) {
        header('Location: resume.php');
        exit();
    } else {
        $delete_error = "Failed to delete resume";
    }
}

// Handle Download/View Request
if (isset($_GET['action']) && ($_GET['action'] === 'download' || $_GET['action'] === 'view')) {
    $query = "SELECT resume_data, resume_name, resume_type FROM student_resumes WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $student_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    
    if (mysqli_stmt_num_rows($stmt) > 0) {
        mysqli_stmt_bind_result($stmt, $resume_data, $resume_name, $resume_type);
        mysqli_stmt_fetch($stmt);
        
        // Clean any output
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Type: ' . $resume_type);
        header('Cache-Control: private, no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        if ($_GET['action'] === 'download') {
            header('Content-Disposition: attachment; filename="' . basename($resume_name) . '"');
        } else {
            header('Content-Disposition: inline; filename="' . basename($resume_name) . '"');
        }
        
        header('Content-Length: ' . strlen($resume_data));
        echo $resume_data;
        exit();
    }
}

// Verify user exists in the database
$verify_user_query = "SELECT id FROM users WHERE id = ? AND status = 'active'";
$stmt = mysqli_prepare($conn, $verify_user_query);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) === 0) {
    session_destroy();
    header('location: ../Frontend/login.html');
    exit();
}

$create_table_query = "CREATE TABLE IF NOT EXISTS student_resumes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    resume_data LONGBLOB,
    resume_name VARCHAR(255),
    resume_type VARCHAR(100),
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)";

if (!mysqli_query($conn, $create_table_query)) {
    die("Error creating table: " . mysqli_error($conn));
}

if (isset($_POST['upload_resume'])) {
    if (!isset($_FILES["resume_file"])) {
        $upload_error = "Please select a file to upload.";
    } else if ($_FILES["resume_file"]["error"] !== UPLOAD_ERR_OK) {
        // Get upload error message
        switch($_FILES["resume_file"]["error"]) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $upload_error = "The uploaded file exceeds the maximum file size limit of 100MB.";
                break;
            case UPLOAD_ERR_PARTIAL:
                $upload_error = "The file was only partially uploaded. Please try again.";
                break;
            case UPLOAD_ERR_NO_FILE:
                $upload_error = "No file was uploaded. Please select a file.";
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $upload_error = "Missing a temporary folder. Please contact support.";
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $upload_error = "Failed to write file to disk. Please contact support.";
                break;
            case UPLOAD_ERR_EXTENSION:
                $upload_error = "A PHP extension stopped the file upload. Please contact support.";
                break;
        }
    } else {
        // Process the file
        $file_size = $_FILES["resume_file"]["size"];
        $file_name = $_FILES["resume_file"]["name"];
        $file_type = $_FILES["resume_file"]["type"];
        $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if ($file_size > 104857600) { // 100MB in bytes
            $upload_error = "Sorry, your file is too large. Maximum size is 100MB.";
        } else if ($file_extension != "pdf" && $file_extension != "doc" && $file_extension != "docx") {
            $upload_error = "Sorry, only PDF, DOC & DOCX files are allowed.";
        } else {
            // Add error logging
            error_log("Uploading file: " . $file_name . " (" . $file_type . ")");
            error_log("File size: " . $file_size . " bytes");
            
            // Read file contents
            $resume_data = file_get_contents($_FILES["resume_file"]["tmp_name"]);
            if ($resume_data === false) {
                $upload_error = "Failed to read the uploaded file.";
                error_log("Failed to read file: " . $_FILES["resume_file"]["tmp_name"]);
            } else {
                error_log("Successfully read file. Size: " . strlen($resume_data) . " bytes");
                
                $check_query = "SELECT id FROM student_resumes WHERE user_id = ?";
                $stmt = mysqli_prepare($conn, $check_query);
                mysqli_stmt_bind_param($stmt, "i", $student_id);
                $check_result = mysqli_stmt_execute($stmt);
                
                if (!$check_result) {
                    $upload_error = "Database error: " . mysqli_error($conn);
                } else {
                    $result = mysqli_stmt_get_result($stmt);
                    if (mysqli_num_rows($result) > 0) {
                        // Update existing resume
                        $update_query = "UPDATE student_resumes SET resume_data = ?, resume_name = ?, resume_type = ? WHERE user_id = ?";
                        $stmt = mysqli_prepare($conn, $update_query);
                        
                        // Bind parameters using null
                        mysqli_stmt_bind_param($stmt, "bssi", $null, $file_name, $file_type, $student_id);
                        
                        // Now update the null bind with the actual data
                        $null = $resume_data;
                        $stmt->send_long_data(0, $resume_data);
                        
                        if (!mysqli_stmt_execute($stmt)) {
                            $upload_error = "Error updating record: " . mysqli_error($conn);
                            error_log("MySQL Error during update: " . mysqli_error($conn));
                        } else {
                            $upload_success = "Your resume has been uploaded successfully.";
                            error_log("Resume updated successfully for user " . $student_id);
                        }
                    } else {
                        // Insert new resume
                        $insert_query = "INSERT INTO student_resumes (user_id, resume_data, resume_name, resume_type) VALUES (?, ?, ?, ?)";
                        $stmt = mysqli_prepare($conn, $insert_query);
                        
                        // Bind parameters using null for BLOB
                        mysqli_stmt_bind_param($stmt, "ibss", $student_id, $null, $file_name, $file_type);
                        
                        // Now update the null bind with the actual data
                        $null = $resume_data;
                        $stmt->send_long_data(1, $resume_data);
                        
                        if (!mysqli_stmt_execute($stmt)) {
                            $upload_error = "Error inserting record: " . mysqli_error($conn);
                            error_log("MySQL Error during insert: " . mysqli_error($conn));
                        } else {
                            $upload_success = "Your resume has been uploaded successfully.";
                            error_log("Resume inserted successfully for user " . $student_id);
                        }
                    }
                }
            }
        }
    }
}

// Fetch existing resume data with proper BLOB handling
$resume_query = "SELECT * FROM student_resumes WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $resume_query);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$resume_data = null;

if ($result && mysqli_num_rows($result) > 0) {
    $resume_data = mysqli_fetch_assoc($result);
    // Don't fetch BLOB data here, only when downloading/viewing
    unset($resume_data['resume_data']);
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
    <link rel="stylesheet" href="../Assets/Styles/user.css">
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
                    <p class="text-muted mb-3">Upload your resume in PDF, DOC, or DOCX format (max 100MB).</p>
                    
                    <form action="resume.php" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="MAX_FILE_SIZE" value="104857600" />
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
                        <i class="bi bi-file-earmark-text me-2" class="document-icon"></i>
                        <div>
                            <h6 class="mb-0"><?php echo htmlspecialchars($resume_data['resume_name']); ?></h6>
                            <small class="text-muted">Uploaded on: <?php echo date('F j, Y', strtotime($resume_data['uploaded_at'])); ?></small>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="resume.php?action=view" class="btn btn-sm btn-outline-primary" target="_blank">
                            <i class="bi bi-eye me-1"></i> View
                        </a>
                        <a href="resume.php?action=download" class="btn btn-sm btn-outline-success">
                            <i class="bi bi-download me-1"></i> Download
                        </a>
                        <form method="post" style="display: inline;">
                            <button type="submit" name="delete_resume" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete your resume?')">
                                <i class="bi bi-trash me-1"></i> Delete
                            </button>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Add SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // File selection display
        document.getElementById('resumeFile').addEventListener('change', function() {
            const fileName = this.files[0] ? this.files[0].name : 'No file selected';
            document.getElementById('selectedFile').textContent = fileName;
        });

        // Delete resume functionality
        function deleteResume() {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('delete_resume.php', {
                        method: 'POST'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire(
                                'Deleted!',
                                'Your resume has been deleted.',
                                'success'
                            ).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire(
                                'Error!',
                                data.message,
                                'error'
                            );
                        }
                    })
                    .catch(error => {
                        Swal.fire(
                            'Error!',
                            'Something went wrong.',
                            'error'
                        );
                    });
                }
            });
        }
        
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