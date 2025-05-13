<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

if (!isset($_SESSION['is_super_admin']) || !$_SESSION['is_super_admin']) {
    header('Location: Dashboard.php');
    exit();
}

require_once __DIR__ . '/../Backend/Core/Security/TokenHandler.php';
require_once __DIR__ . '/../Backend/Core/Config/DataManagement/DB_Operations.php';

class AdminsManager {
    private $dbOps;
    private $conn;

    public function __construct() {
        $this->dbOps = SQL_Operations::getInstance();
        $this->conn = $this->dbOps->getConnection();
    }

    public function getAllAdmins() {
        return $this->conn->query("SELECT * FROM administrators ORDER BY is_super_admin DESC, created_at DESC")
            ->fetch_all(MYSQLI_ASSOC);
    }

    public function createAdmin($adminData) {
        if (!$this->canCreateAdmin($_SESSION['admin_id'])) {
            throw new Exception("Only super administrators can create other admin accounts");
        }

        $this->conn->begin_transaction();
        try {
            if ($this->emailExists($adminData['email'])) {                throw new Exception("This email is already registered in the system as either an administrator or a user");
            }
            if ($this->srcodeExists($adminData['srcode'])) {
                throw new Exception("This SR code is already registered in the system as either an administrator or a user");
            }

            $stmt = $this->conn->prepare("INSERT INTO administrators (srcode, name, email, password, is_super_admin, status) VALUES (?, ?, ?, ?, ?, 'active')");
            
            $hashedPassword = password_hash($adminData['password'], PASSWORD_DEFAULT);
            $stmt->bind_param("ssssi", 
                $adminData['srcode'],
                $adminData['name'],
                $adminData['email'],
                $hashedPassword,
                $adminData['is_super_admin']
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to create admin account: " . $stmt->error);
            }

            $this->conn->commit();
            return [
                'success' => true,
                'message' => 'Admin account created successfully'
            ];
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    public function toggleSuperAdmin($adminId) {
        if (!$this->canCreateAdmin($_SESSION['admin_id'])) {
            throw new Exception("Only super administrators can modify admin privileges");
        }

        $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM administrators WHERE is_super_admin = 1 AND id != ?");
        $stmt->bind_param("i", $adminId);
        $stmt->execute();
        $superAdminCount = $stmt->get_result()->fetch_assoc()['count'];

        $stmt = $this->conn->prepare("SELECT is_super_admin FROM administrators WHERE id = ?");
        $stmt->bind_param("i", $adminId);
        $stmt->execute();
        $currentStatus = $stmt->get_result()->fetch_assoc()['is_super_admin'];

        if ($currentStatus == 1 && $superAdminCount == 0) {
            throw new Exception("Cannot demote the last super administrator");
        }

        $stmt = $this->conn->prepare("UPDATE administrators SET is_super_admin = NOT is_super_admin WHERE id = ?");
        $stmt->bind_param("i", $adminId);
        return $stmt->execute();
    }

    public function canCreateAdmin($adminId) {
        $stmt = $this->conn->prepare("SELECT is_super_admin FROM administrators WHERE id = ? AND status = 'active'");
        $stmt->bind_param("i", $adminId);
        $stmt->execute();
        $result = $stmt->get_result();
        $admin = $result->fetch_assoc();
        return $admin && $admin['is_super_admin'] == 1;
    }

    public function isLastActiveAdmin($userId) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as count 
                                    FROM administrators 
                                    WHERE status = 'active' 
                                    AND id != ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_assoc()['count'];
        return $count === 0;
    }    private function emailExists($email) {
        // Check administrators table
        $stmt = $this->conn->prepare("SELECT 1 FROM administrators WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            return true;
        }
        
        // Also check users table
        $stmt = $this->conn->prepare("SELECT 1 FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }

    private function srcodeExists($srcode) {
        // Check administrators table
        $stmt = $this->conn->prepare("SELECT 1 FROM administrators WHERE srcode = ?");
        $stmt->bind_param("s", $srcode);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            return true;
        }
        
        // Also check users table
        $stmt = $this->conn->prepare("SELECT 1 FROM users WHERE srcode = ?");
        $stmt->bind_param("s", $srcode);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }
}

$manager = new AdminsManager();
$admins = $manager->getAllAdmins();

$adminName = isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : 'Admin';
$isSuperAdmin = isset($_SESSION['is_super_admin']) && $_SESSION['is_super_admin'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin - Administrators</title>
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
                    <a href="Companies.php" class="nav-link">
                        <i class="bi bi-building-fill"></i> Companies
                    </a>
                    <a href="Users.php" class="nav-link">
                        <i class="bi bi-people-fill"></i> Users
                    </a>
                    <a href="Admins.php" class="nav-link active">
                        <i class="bi bi-shield-fill"></i> Administrators
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
                        <i class="bi bi-shield-fill me-2"></i>
                        <h4 class="mb-0">Administrators Management</h4>
                    </div>
                    <?php if ($manager->canCreateAdmin($_SESSION['admin_id'])): ?>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAdminModal">
                        <i class="bi bi-plus-circle"></i> Add Administrator
                    </button>
                    <?php endif; ?>
                </div>

                <!-- Admins Table -->
                <div class="table-responsive">
                    <table id="adminsTable" class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>SR Code</th>
                                <th>Email</th>
                                <th>Created</th>
                                <th>Super Admin</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($admins as $admin): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($admin['name']); ?></td>
                                    <td><?php echo htmlspecialchars($admin['srcode']); ?></td>
                                    <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($admin['created_at'])); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $admin['is_super_admin'] ? 'success' : 'secondary'; ?>">
                                            <?php echo $admin['is_super_admin'] ? 'Yes' : 'No'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $admin['status'] === 'active' ? 'success' : 'warning'; ?>">
                                            <?php echo ucfirst($admin['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-info btn-sm view-admin-btn" data-id="<?php echo $admin['id']; ?>">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <?php if ($isSuperAdmin): ?>
                                        <button class="btn <?php echo $admin['status'] === 'active' ? 'btn-warning' : 'btn-success'; ?> btn-sm status-btn" 
                                                data-id="<?php echo $admin['id']; ?>"
                                                data-action="<?php echo $admin['status'] === 'active' ? 'deactivate' : 'activate'; ?>">
                                            <i class="bi <?php echo $admin['status'] === 'active' ? 'bi-x-circle' : 'bi-check-circle'; ?>"></i>
                                        </button>
                                        <?php if ($admin['id'] != $_SESSION['admin_id']): ?>
                                        <button class="btn <?php echo $admin['is_super_admin'] ? 'btn-danger' : 'btn-success'; ?> btn-sm toggle-super-btn" 
                                                data-id="<?php echo $admin['id']; ?>"
                                                data-is-super="<?php echo $admin['is_super_admin']; ?>"
                                                data-bs-toggle="tooltip" 
                                                title="<?php echo $admin['is_super_admin'] ? 'Remove Super Admin' : 'Make Super Admin'; ?>">
                                            <i class="bi bi-shield<?php echo $admin['is_super_admin'] ? '-minus' : '-plus'; ?>"></i>
                                        </button>
                                        <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Admin Modal -->
    <div class="modal fade" id="addAdminModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Administrator</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addAdminForm">
                        <input type="hidden" name="action" value="addAdmin">
                        <input type="hidden" name="csrf_token" id="csrf_token">
                        
                        <div class="mb-3">
                            <label for="srcode" class="form-label">SR Code</label>
                            <input type="text" class="form-control" id="srcode" name="srcode" required>
                        </div>
                          <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_super_admin" name="is_super_admin" value="1">
                                <label class="form-check-label" for="is_super_admin">
                                    Make Super Administrator
                                </label>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveAdminBtn">Create Administrator</button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Admin Modal -->
    <div class="modal fade" id="viewAdminModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Administrator Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Admin details will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Load scripts in correct order -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="../Assets/Scripts/csrf.js"></script>    <script src="../Assets/Scripts/admin.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', async function() {
            if ($.fn.DataTable.isDataTable('#adminsTable')) {
                $('#adminsTable').DataTable().destroy();
            }
            
            $('#adminsTable').DataTable({
                pageLength: 10,
                language: {
                    search: "Filter records:"
                }
            });

            // Initialize tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            await CSRFManager.init();
        });
    </script>
</body>
</html>
