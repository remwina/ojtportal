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

class CompaniesManager {
    private $dbOps;
    private $conn;

    public function __construct() {
        $this->dbOps = new SQL_Operations();
        $this->conn = $this->dbOps->getConnection();
    }

    public function getAllCompanies() {
        $result = $this->conn->query("SELECT * FROM companies ORDER BY name ASC");
            if (!$result) {
            throw new Exception("Error fetching companies: " . $this->conn->error);
        }
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getCompanyById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM companies WHERE id = ?");
        if (!$stmt) {
            throw new Exception("Error preparing statement: " . $this->conn->error);
        }
        $stmt->bind_param('i', $id);
        if (!$stmt->execute()) {
            throw new Exception("Error fetching company: " . $stmt->error);
        }
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function addCompany($data) {
        $stmt = $this->conn->prepare("CALL sp_admin_add_company(?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            throw new Exception("Error preparing statement: " . $this->conn->error);
        }
        $stmt->bind_param("ssssssss", 
            $data['name'],
            $data['address'],
            $data['contact_person'],
            $data['contact_email'],
            $data['contact_phone'],
            $data['website'],
            $data['description'],
            $data['logo_path']
        );
        if (!$stmt->execute()) {
            throw new Exception("Error adding company: " . $stmt->error);
        }
        $result = $stmt->get_result();
        return $result->fetch_assoc()['company_id'];
    }

    public function updateCompany($data) {
        $stmt = $this->conn->prepare("CALL sp_admin_update_company(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            throw new Exception("Error preparing statement: " . $this->conn->error);
        }
        $stmt->bind_param("issssssssss",
            $data['id'],
            $data['name'],
            $data['address'],
            $data['contact_person'],
            $data['contact_email'],
            $data['contact_phone'],
            $data['website'],
            $data['description'],
            $data['logo_data'],
            $data['logo_type'],
            $data['status']
        );
        if (!$stmt->execute()) {
            throw new Exception("Error updating company: " . $stmt->error);
        }
        return true;
    }

    public function deleteCompany($id) {
        // First check if company has any job listings
        $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM job_listings WHERE company_id = ?");
        if (!$stmt) {
            throw new Exception("Error preparing statement: " . $this->conn->error);
        }
        $stmt->bind_param('i', $id);
        if (!$stmt->execute()) {
            throw new Exception("Error checking job listings: " . $stmt->error);
        }
        $result = $stmt->get_result();
        $count = $result->fetch_assoc()['count'];
        
        if ($count > 0) {
            throw new Exception("Cannot delete company that has job listings");
        }

        // If no job listings, proceed with deletion
        $stmt = $this->conn->prepare("DELETE FROM companies WHERE id = ?");
        if (!$stmt) {
            throw new Exception("Error preparing delete statement: " . $this->conn->error);
        }
        $stmt->bind_param('i', $id);
        if (!$stmt->execute()) {
            throw new Exception("Error deleting company: " . $stmt->error);
        }
        return true;
    }

    public function __destruct() {
        // Connection will be closed by SQL_Operations
    }
}

$manager = new CompaniesManager();
$companies = $manager->getAllCompanies();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin - Companies</title>
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
                    <a href="JobListings.php" class="nav-link">
                        <i class="bi bi-briefcase-fill"></i> Job Listings
                    </a>
                    <a href="Applications.php" class="nav-link">
                        <i class="bi bi-file-earmark-text-fill"></i> Applications
                    </a>
                    <a href="Companies.php" class="nav-link active">
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
                            <input type="text" class="form-control" placeholder="Search companies...">
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
                    <i class="bi bi-building-fill"></i>
                    <h4 class="mb-0">Partner Companies Management</h4>
                </div>

                <!-- Add New Company Button -->
                <div class="mb-4">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCompanyModal">
                        <i class="bi bi-plus-circle"></i> Add New Company
                    </button>
                </div>

                <!-- Companies Table -->
                <div class="table-responsive">
                    <table id="companiesTable" class="table table-hover">
                        <thead>
                            <tr>
                                <th>Company Name</th>
                                <th>Contact Person</th>
                                <th>Contact Info</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($companies as $company): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo htmlspecialchars('../Backend/Core/get_company_logo.php?id=' . $company['id']); ?>" 
                                                 alt="Company Logo"
                                                 class="company-logo me-2"
                                                 style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;">
                                            <div>
                                                <?php echo htmlspecialchars($company['name']); ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($company['contact_person'] ?? 'N/A'); ?></td>
                                    <td>
                                        <div><?php echo htmlspecialchars($company['contact_email'] ?? 'N/A'); ?></div>
                                        <div><?php echo htmlspecialchars($company['contact_phone'] ?? 'N/A'); ?></div>
                                    </td>
                                    <td><?php echo htmlspecialchars($company['address']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $company['status'] === 'active' ? 'success' : 'danger'; ?>">
                                            <?php echo ucfirst($company['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary me-2 edit-btn" 
                                                data-id="<?php echo $company['id']; ?>"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editCompanyModal">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger delete-btn"
                                                data-id="<?php echo $company['id']; ?>">
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
    </div>

    <!-- Add Company Modal -->
    <div class="modal fade" id="addCompanyModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Company</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addCompanyForm">
                        <div class="mb-3">
                            <label class="form-label">Company Name</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Contact Person</label>
                                <input type="text" class="form-control" name="contact_person">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Contact Phone</label>
                                <input type="tel" class="form-control" name="contact_phone">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Contact Email</label>
                                <input type="email" class="form-control" name="contact_email">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Website</label>
                                <input type="url" class="form-control" name="website" placeholder="https://">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" name="address" rows="2" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Company Description</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Company Logo</label>
                            <input type="file" class="form-control" name="logo" accept="image/*">
                            <small class="form-text text-muted">Recommended size: 200x200 pixels, max 2MB</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveCompanyBtn">Save Company</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Company Modal -->
    <div class="modal fade" id="editCompanyModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Company</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editCompanyForm">
                        <input type="hidden" name="id" value="">
                        <div class="mb-3">
                            <label class="form-label">Company Name</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Contact Person</label>
                                <input type="text" class="form-control" name="contact_person">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Contact Phone</label>
                                <input type="tel" class="form-control" name="contact_phone">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Contact Email</label>
                                <input type="email" class="form-control" name="contact_email">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Website</label>
                                <input type="url" class="form-control" name="website" placeholder="https://">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" name="address" rows="2" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Company Description</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Company Logo</label>
                            <input type="file" class="form-control" name="logo" accept="image/*">
                            <input type="hidden" name="current_logo">
                            <small class="form-text text-muted">Leave empty to keep current logo</small>
                            <div id="currentLogoPreview" class="mt-2"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="updateCompanyBtn">Update Company</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Load scripts in correct order -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="../Assets/Scripts/csrf.js"></script>
    <script src="../Assets/Scripts/admin.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', async function() {
            // Initialize CSRF token management
            try {
                // Initialize CSRF first
                await CSRFManager.init();
                
                // Update all forms with the token
                CSRFManager.updateAllForms();

                // Initialize company select options
                const companySelect = document.querySelector('select[name="company_id"]');
                if (companySelect) {
                    const companiesData = JSON.parse('<?php echo addslashes(json_encode($companies)); ?>');
                    companiesData.forEach(company => {
                        const option = document.createElement('option');
                        option.value = company.id;
                        option.textContent = company.name;
                        companySelect.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Failed to initialize:', error);
                Swal.fire({
                    title: 'Error',
                    text: 'Failed to initialize the page. Please refresh and try again.',
                    icon: 'error'
                });
            }
        });
    </script>
</body>
</html>
