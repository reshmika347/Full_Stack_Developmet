<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

// Check if user is admin (only admins can access user management)
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] == 'admin';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    die("Database connection failed");
}

// Handle add user form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_user'])) {
    if (!$is_admin) {
        $error = 'Only administrators can add users';
    } else {
        $username = trim($_POST['username'] ?? '');
        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'staff';
        
        if (empty($username) || empty($full_name) || empty($email) || empty($password)) {
            $error = 'All fields are required';
        } else {
            // Check if username or email exists
            $check = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $check->execute([$username, $email]);
            
            if ($check->rowCount() > 0) {
                $error = 'Username or email already exists';
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $insert = $db->prepare("INSERT INTO users (username, full_name, email, password, role) VALUES (?, ?, ?, ?, ?)");
                
                if ($insert->execute([$username, $full_name, $email, $hashed, $role])) {
                    $success = 'User added successfully';
                } else {
                    $error = 'Error adding user';
                }
            }
        }
    }
}

// Handle status toggle
if (isset($_GET['toggle_status']) && $is_admin) {
    $id = $_GET['toggle_status'];
    if ($id != $_SESSION['user_id']) { // Don't allow toggling own status
        $stmt = $db->prepare("UPDATE users SET status = NOT status WHERE id = ?");
        if ($stmt->execute([$id])) {
            $success = 'User status updated';
        }
    }
    header('Location: index.php');
    exit();
}

// Handle delete user
if (isset($_GET['delete']) && $is_admin) {
    $id = $_GET['delete'];
    if ($id != $_SESSION['user_id']) { // Don't allow deleting own account
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        if ($stmt->execute([$id])) {
            $success = 'User deleted successfully';
        }
    }
    header('Location: index.php');
    exit();
}

// Get all users
$query = "SELECT id, username, email, full_name, role, status, last_login, created_at FROM users ORDER BY id DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'User Management';
include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">User Management</h1>
        <?php if ($is_admin): ?>
        <button class="btn btn-primary" data-toggle="modal" data-target="#addUserModal">
            <i class="fas fa-plus"></i> Add User
        </button>
        <?php endif; ?>
    </div>

    <?php if (isset($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $success; ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $error; ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>

    <?php if (!$is_admin): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> 
            You are viewing this page as a non-admin user. Some features are restricted.
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="usersTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            <th>Created</th>
                            <?php if ($is_admin): ?>
                            <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="<?php echo $is_admin ? 9 : 8; ?>" class="text-center">
                                    No users found in database.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo $user['role'] == 'admin' ? 'danger' : 
                                            ($user['role'] == 'manager' ? 'warning' : 'info'); 
                                    ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $user['status'] ? 'success' : 'secondary'; ?>">
                                        <?php echo $user['status'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td><?php echo $user['last_login'] ? date('d-m-Y H:i', strtotime($user['last_login'])) : 'Never'; ?></td>
                                <td><?php echo date('d-m-Y', strtotime($user['created_at'])); ?></td>
                                <?php if ($is_admin): ?>
                                <td>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <a href="?toggle_status=<?php echo $user['id']; ?>" 
                                           class="btn btn-sm btn-<?php echo $user['status'] ? 'secondary' : 'success'; ?>"
                                           title="<?php echo $user['status'] ? 'Deactivate' : 'Activate'; ?>">
                                            <i class="fas fa-<?php echo $user['status'] ? 'ban' : 'check'; ?>"></i>
                                        </a>
                                        
                                        <a href="?delete=<?php echo $user['id']; ?>" 
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Are you sure you want to delete this user?')"
                                           title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">(You)</span>
                                    <?php endif; ?>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<?php if ($is_admin): ?>
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header">
                    <h5 class="modal-title">Add New User</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Username <span class="text-danger">*</span></label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" required>
                        <small class="text-muted">Minimum 6 characters</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Role</label>
                        <select name="role" class="form-control">
                            <option value="staff">Staff</option>
                            <option value="manager">Manager</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_user" class="btn btn-primary">Add User</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
$(document).ready(function() {
    $('#usersTable').DataTable({
        pageLength: 25,
        order: [[0, 'desc']]
    });
});
</script>

<?php include '../../includes/footer.php'; ?>