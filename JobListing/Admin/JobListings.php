<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('location: ../Frontend/login.html');
    exit();
}

require_once 'Auth.php';

$auth = new Auth();
if (!$auth->check()) {
    header('Location: Login.php');
    exit();
}

require_once __DIR__ . '/../Backend/Core/Config/DataManagement/DB_Operations.php';

class JobListingsManager {
    private $dbOps;
    private $conn;

    public function __construct() {
        $this->dbOps = new SQL_Operations();
        $this->conn = $this->dbOps->getConnection();
    }

    public function getAllJobListings() {
        $query = "SELECT jl.*, c.name as company_name 
                 FROM job_listings jl 
                 LEFT JOIN companies c ON jl.company_id = c.id 
                 ORDER BY jl.created_at DESC";
        $result = $this->conn->query($query);
        if (!$result) {
            throw new Exception("Error fetching job listings: " . $this->conn->error);
        }
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getJobById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM job_listings WHERE id = ?");
        if (!$stmt) {
            throw new Exception("Error preparing statement: " . $this->conn->error);
        }
        $stmt->bind_param('i', $id);
        if (!$stmt->execute()) {
            throw new Exception("Error fetching job: " . $stmt->error);
        }
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }    public function addJobListing($data) {
        $stmt = $this->conn->prepare("INSERT INTO job_listings (title, company_id, description, work_mode, job_type, slots, salary_range, requirements, responsibilities, qualifications, benefits, status, expires_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            throw new Exception("Error preparing statement: " . $this->conn->error);
        }
        $stmt->bind_param("sisssisssssss", 
            $data['title'],
            $data['company_id'],
            $data['description'],
            $data['work_mode'],
            $data['job_type'],
            $data['slots'],
            $data['salary_range'],
            $data['requirements'],
            $data['responsibilities'],
            $data['qualifications'],
            $data['benefits'],
            $data['status'],
            $data['expires_at']
        );
        if (!$stmt->execute()) {
            throw new Exception("Error adding job listing: " . $stmt->error);
        }
        return $stmt->insert_id;
    }    public function updateJobListing($data) {
        $stmt = $this->conn->prepare("UPDATE job_listings SET 
            title = ?,
            company_id = ?,
            description = ?,
            work_mode = ?,
            job_type = ?,
            slots = ?,
            salary_range = ?,
            requirements = ?,
            responsibilities = ?,
            qualifications = ?,
            benefits = ?,
            status = ?,
            expires_at = ?
            WHERE id = ?");
        
        if (!$stmt) {
            throw new Exception("Error preparing statement: " . $this->conn->error);
        }
        
        $stmt->bind_param("sisssisssssssi",            $data['title'],
            $data['company_id'],
            $data['description'],
            $data['work_mode'],
            $data['job_type'],
            $data['slots'],
            $data['salary_range'],
            $data['requirements'],
            $data['responsibilities'],
            $data['qualifications'],
            $data['benefits'],
            $data['status'],
            $data['expires_at'],
            $data['id']
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Error updating job listing: " . $stmt->error);
        }
        return true;
    }

    public function deleteJobListing($id) {
        // First check if job has any applications
        $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM applications WHERE job_id = ?");
        if (!$stmt) {
            throw new Exception("Error preparing statement: " . $this->conn->error);
        }
        $stmt->bind_param('i', $id);
        if (!$stmt->execute()) {
            throw new Exception("Error checking applications: " . $stmt->error);
        }
        $result = $stmt->get_result();
        $count = $result->fetch_assoc()['count'];
        
        if ($count > 0) {
            throw new Exception("Cannot delete job listing that has applications");
        }

        // If no applications, proceed with deletion
        $stmt = $this->conn->prepare("DELETE FROM job_listings WHERE id = ?");
        if (!$stmt) {
            throw new Exception("Error preparing delete statement: " . $this->conn->error);
        }
        $stmt->bind_param('i', $id);
        if (!$stmt->execute()) {
            throw new Exception("Error deleting job listing: " . $stmt->error);
        }
        return true;
    }

    public function getAllCompanies() {
        $result = $this->conn->query("SELECT id, name FROM companies WHERE status = 'active' ORDER BY name ASC");
        if (!$result) {
            throw new Exception("Error fetching companies: " . $this->conn->error);
        }
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function __destruct() {
        // Connection will be closed by SQL_Operations
    }
}

$manager = new JobListingsManager();
$jobListings = $manager->getAllJobListings();
$companies = $manager->getAllCompanies();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin - Job Listings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" />
    <link rel="stylesheet" href="../Assets/Styles/admin.css" />
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <div class="logo-container">
                    <img src="../Dashboard/BatStateU-NEU-Logo.png" alt="BatStateU-NEU Logo" class="logo">
                    <div class="logo-text">BatState-U NEU</div>
                    <div class="logo-subtext">Admin Portal</div>
                </div>
                <div class="d-flex flex-column">
                    <a href="Dashboard.php" class="nav-link">
                        <i class="bi bi-house-door-fill"></i> Dashboard
                    </a>
                    <a href="JobListings.php" class="nav-link active">
                        <i class="bi bi-briefcase-fill"></i> Job Listings
                    </a>
                    <a href="Applications.php" class="nav-link">
                        <i class="bi bi-file-earmark-text-fill"></i> Applications
                    </a>
                    <a href="Companies.php" class="nav-link">
                        <i class="bi bi-building-fill"></i> Companies
                    </a>
                    <a href="Users.php" class="nav-link">
                        <i class="bi bi-people-fill"></i> Users
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
                            <input type="text" class="form-control" placeholder="Search job listings...">
                            <button class="search-button">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="profile-section">
                        <i class="bi bi-person-circle profile-icon"></i>
                        <span class="ms-2">Admin</span>
                    </div>
                </div>

                <!-- Section Header -->
                <div class="section-header">
                    <i class="bi bi-briefcase-fill"></i>
                    <h4 class="mb-0">Job Listings Management</h4>
                </div>

                <!-- Add New Job Button -->
                <div class="mb-4">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addJobModal">
                        <i class="bi bi-plus-circle"></i> Add New Job Listing
                    </button>
                </div>

                <!-- Job Listings Table -->
                <div class="table-responsive">
                    <table class="table table-hover" id="jobListingsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Company</th>
                <th>Work Mode</th>
                <th>Type</th>
                <th>Posted</th>
                <th>Status</th>
                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($jobListings as $job): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($job['id']); ?></td>
                                <td><?php echo htmlspecialchars($job['title']); ?></td>
                                <td><?php echo htmlspecialchars($job['company_name']); ?></td>
                <td><?php echo htmlspecialchars($job['work_mode']); ?></td>
                <td><?php echo htmlspecialchars(ucfirst($job['job_type'])); ?></td>
                <td><?php echo date('M d, Y', strtotime($job['created_at'])); ?></td>
                <td>                    <span class="badge bg-<?php echo $job['status'] === 'open' ? 'success' : ($job['status'] === 'draft' ? 'warning' : 'danger'); ?>">
                                        <?php echo ucfirst($job['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary edit-btn" data-id="<?php echo $job['id']; ?>" data-bs-toggle="modal" data-bs-target="#editJobModal">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger delete-btn" data-id="<?php echo $job['id']; ?>">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>    <!-- Add Job Modal -->
    <div class="modal fade" id="addJobModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Job Listing</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addJobForm">
                        <div class="row mb-3">
                            <div class="col-md-8">
                                <label class="form-label">Job Title</label>
                                <input type="text" class="form-control" name="title" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Company</label>
                                <select class="form-select" name="company_id" required>
                                    <option value="">Select Company</option>
                                    <?php foreach ($companies as $company): ?>
                                    <option value="<?php echo $company['id']; ?>">
                                        <?php echo htmlspecialchars($company['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3" required></textarea>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Work Mode</label>
                                <select class="form-select" name="work_mode" required>
                                    <option value="">Select Work Mode</option>
                                    <option value="onsite">On-site</option>
                                    <option value="hybrid">Hybrid</option>
                                    <option value="remote">Remote</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Job Type</label>
                                <select class="form-select" name="job_type" required>
                                    <option value="">Select Type</option>
                                    <option value="full-time">Full Time</option>
                                    <option value="part-time">Part Time</option>
                                    <option value="internship">Internship</option>
                                </select>
                            </div>                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Number of Slots</label>
                                <input type="number" class="form-control" name="slots" min="1" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Salary Range</label>
                                <input type="text" class="form-control" name="salary_range" placeholder="e.g. ₱20,000 - ₱30,000">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Requirements</label>
                            <textarea class="form-control" name="requirements" rows="3"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Responsibilities</label>
                            <textarea class="form-control" name="responsibilities" rows="3"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Qualifications</label>
                            <textarea class="form-control" name="qualifications" rows="3"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Benefits</label>
                            <textarea class="form-control" name="benefits" rows="3"></textarea>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Expiry Date</label>
                                <input type="date" class="form-control" name="expires_at">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status" required>
                                    <option value="open">Open</option>
                                    <option value="closed">Closed</option>
                                    <option value="draft">Draft</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveJobBtn">Save Job</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Job Modal -->
    <div class="modal fade" id="editJobModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Job Listing</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editJobForm">
                        <input type="hidden" name="id">
                        <div class="row mb-3">
                            <div class="col-md-8">
                                <label class="form-label">Job Title</label>
                                <input type="text" class="form-control" name="title" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Company</label>
                                <select class="form-select" name="company_id" required>
                                    <option value="">Select Company</option>
                                    <?php foreach ($companies as $company): ?>
                                    <option value="<?php echo $company['id']; ?>">
                                        <?php echo htmlspecialchars($company['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">                                <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3" required></textarea>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Work Mode</label>
                                <select class="form-select" name="work_mode" required>
                                    <option value="">Select Work Mode</option>
                                    <option value="onsite">On-site</option>
                                    <option value="hybrid">Hybrid</option>
                                    <option value="remote">Remote</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Job Type</label>
                                <select class="form-select" name="job_type" required>
                                    <option value="">Select Type</option>
                                    <option value="full-time">Full Time</option>
                                    <option value="part-time">Part Time</option>
                                    <option value="internship">Internship</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Number of Slots</label>
                                <input type="number" class="form-control" name="slots" min="1" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Salary Range</label>
                                <input type="text" class="form-control" name="salary_range" placeholder="e.g. ₱20,000 - ₱30,000">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Requirements</label>
                            <textarea class="form-control" name="requirements" rows="3"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Responsibilities</label>
                            <textarea class="form-control" name="responsibilities" rows="3"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Qualifications</label>
                            <textarea class="form-control" name="qualifications" rows="3"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Benefits</label>
                            <textarea class="form-control" name="benefits" rows="3"></textarea>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Expiry Date</label>
                                <input type="date" class="form-control" name="expires_at">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status" required>
                                    <option value="open">Open</option>
                                    <option value="closed">Closed</option>
                                    <option value="draft">Draft</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="updateJobBtn">Update Job</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Password Change Modal -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Change Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="changePasswordForm">
                        <div class="mb-3">
                            <label class="form-label">Current Password</label>
                            <input type="password" class="form-control" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" class="form-control" name="new_password" id="newPassword" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirmPassword" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="changePasswordBtn">Change Password</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../Assets/Scripts/csrf.js"></script>
    <script src="../Assets/Scripts/admin.js"></script>
</body>
</html>
