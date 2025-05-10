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

$adminName = isset($_SESSION['student_name']) ? $_SESSION['student_name'] : 'Admin';

require_once __DIR__ . '/../Backend/Core/Config/DataManagement/DB_Operations.php';

class CompaniesManager {
    private $dbOps;
    private $conn;

    public function __construct() {
        $this->dbOps = new SQL_Operations();
        $this->conn = $this->dbOps->getConnection();
    }

    public function getAllCompanies() {
        $query = "SELECT c.*, 
                 (SELECT COUNT(*) FROM job_listings WHERE company_id = c.id AND status = 'open') as open_positions 
                 FROM companies c ORDER BY c.name ASC";
        $result = $this->conn->query($query);
        if (!$result) {
            throw new Exception("Error fetching companies: " . $this->conn->error);
        }
        return $result->fetch_all(MYSQLI_ASSOC);
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
                <!-- Section Header -->
                <div class="section-header d-flex justify-content-between align-items-center mb-4">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-building-fill me-2"></i>
                        <h4 class="mb-0">Partner Companies Management</h4>
                    </div>
                    <div class="profile-section">
                        <i class="bi bi-person-circle profile-icon"></i>
                        <span class="profile-name"><?php echo htmlspecialchars($adminName); ?></span>
                    </div>
                </div>

                <!-- Companies Table -->
                <div class="table-responsive">
                    <table id="companiesTable" class="table table-hover">
                        <thead>
                            <tr>
                                <th>Company</th>
                                <th>Contact Info</th>
                                <th>Open Positions</th>
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
                                                <strong><?php echo htmlspecialchars($company['name']); ?></strong>
                                                <div class="small text-muted"><?php echo htmlspecialchars($company['address']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div><i class="bi bi-person"></i> <?php echo htmlspecialchars($company['contact_person'] ?? 'N/A'); ?></div>
                                        <div><i class="bi bi-envelope"></i> <?php echo htmlspecialchars($company['contact_email'] ?? 'N/A'); ?></div>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">
                                            <?php echo $company['open_positions'] . ' position' . ($company['open_positions'] != 1 ? 's' : ''); ?>
                                        </span>
                                    </td>
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
                     <!-- Add New Company Button -->
                <div class="add-button-container">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCompanyModal">
                        <i class="bi bi-plus-circle"></i> Add New Company
                    </button>
                </div>
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
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Contact Person</label>
                                <input type="text" class="form-control" name="contact_person">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Contact Phone</label>
                                <input type="tel" class="form-control" name="contact_phone">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Contact Email</label>
                                <input type="email" class="form-control" name="contact_email">
                            </div>
                            <div class="col-md-6">
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
                        <input type="hidden" name="id">
                        <div class="mb-3">
                            <label class="form-label">Company Name</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Contact Person</label>
                                <input type="text" class="form-control" name="contact_person">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Contact Phone</label>
                                <input type="tel" class="form-control" name="contact_phone">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Contact Email</label>
                                <input type="email" class="form-control" name="contact_email">
                            </div>
                            <div class="col-md-6">
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="../Assets/Scripts/csrf.js"></script>
    <script src="../Assets/Scripts/admin.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', async function() {
            // Initialize DataTable
            if ($.fn.DataTable.isDataTable('#companiesTable')) {
                $('#companiesTable').DataTable().destroy();
            }

            $('#companiesTable').DataTable({
                pageLength: 10,
                language: {
                    search: "Search companies:",
                    lengthMenu: "_MENU_ companies per page",
                    info: "Showing _START_ to _END_ of _TOTAL_ companies",
                    infoEmpty: "No companies found",
                    emptyTable: "No companies available"
                },
                dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                     "<'row'<'col-sm-12'tr>>" +
                     "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>"
            });

            // Initialize CSRF token management
            await CSRFManager.init();
        });
    </script>
</body>
</html>