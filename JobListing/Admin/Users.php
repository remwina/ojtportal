<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('location: ../Frontend/login.html');
    exit();
}

require_once 'Auth.php';
require_once __DIR__ . '/../Backend/Core/Security/TokenHandler.php';

$auth = new Auth();

if (!$auth->check()) {
    header('Location: Login.php');
    exit();
}

require_once __DIR__ . '/../Backend/Core/Config/DataManagement/DB_Operations.php';

class UsersManager {
    private $dbOps;
    private $conn;

    public function __construct() {
        $this->dbOps = new SQL_Operations();
        $this->conn = $this->dbOps->getConnection();
    }

    public function getAllUsers() {
        $result = $this->conn->query("SELECT u.*, c.name as course_name, d.name as department_name 
                                    FROM users u 
                                    LEFT JOIN courses c ON u.course_id = c.id 
                                    LEFT JOIN departments d ON c.department_id = d.id 
                                    ORDER BY u.created_at DESC");
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function __destruct() {
        // Connection will be closed by SQL_Operations
    }
}

$manager = new UsersManager();
$users = $manager->getAllUsers();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="<?php echo TokenHandler::generateToken(); ?>" />
    <title>Admin - Users</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" />
    <link rel="stylesheet" href="../Assets/Styles/admin.css" />
    <!-- Add SweetAlert2 -->
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
                    <a href="Users.php" class="nav-link active">
                        <i class="bi bi-people-fill"></i> Users
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
                    <div class="d-flex align-items-center">
                        <i class="bi bi-people-fill me-2"></i>
                        <h4 class="mb-0">Users Management</h4>
                    </div>
                    <div class="profile-section">
                        <i class="bi bi-person-circle profile-icon"></i>
                        <span class="ms-2">Admin</span>
                    </div>
                </div>

                <!-- Users Table -->
                <div class="table-responsive">
                    <table id="usersTable" class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>SR Code</th>
                                <th>Email</th>
                                <th>Course</th>
                                <th>Department</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['srcode']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['course_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['department_name']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $user['status'] === 'active' ? 'success' : 'warning'; ?>">
                                            <?php echo ucfirst($user['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-info btn-sm view-btn" data-id="<?php echo $user['id']; ?>">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button class="btn <?php echo $user['status'] === 'active' ? 'btn-warning' : 'btn-success'; ?> btn-sm status-btn" 
                                                data-id="<?php echo $user['id']; ?>" 
                                                data-action="<?php echo $user['status'] === 'active' ? 'deactivate' : 'activate'; ?>">
                                            <i class="bi <?php echo $user['status'] === 'active' ? 'bi-x-circle' : 'bi-check-circle'; ?>"></i>
                                        </button>
                                        <?php if ($user['usertype'] !== 'admin'): ?>
                                        <button class="btn btn-secondary btn-sm force-reset-btn" 
                                                data-id="<?php echo $user['id']; ?>"
                                                data-bs-toggle="tooltip" 
                                                title="Force Password Reset">
                                            <i class="bi bi-key"></i>
                                        </button>
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

    <!-- View User Modal -->
    <div class="modal fade" id="viewUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content"> 
                <div class="modal-header">
                    <h5 class="modal-title">User Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- User details will be loaded here -->
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
    <script src="../Assets/Scripts/csrf.js"></script>
    <script src="../Assets/Scripts/admin.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', async function() {
            // Destroy existing DataTable instance if it exists
            if ($.fn.DataTable.isDataTable('#usersTable')) {
                $('#usersTable').DataTable().destroy();
            }
            
            // Initialize fresh DataTable instance
            $('#usersTable').DataTable({
                pageLength: 10,
                language: {
                    search: "Filter records:"
                }
            });

            // Initialize CSRF token management
            await CSRFManager.init();
        });
    </script>
</body>
</html>
