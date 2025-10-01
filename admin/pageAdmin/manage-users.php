<?php
session_start();

// Database configuration
$host = 'localhost';
$dbname = 'hungry_hub';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$admin_username = $_SESSION['admin_username'] ?? 'Admin';
$message = '';
$message_type = '';

// Flash message (from previous redirect)
if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    $message_type = $_SESSION['flash_type'] ?? 'info';
    unset($_SESSION['flash_message'], $_SESSION['flash_type']);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_user') {
        $user_id = $_POST['user_id'] ?? 0;
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $address = $_POST['address'] ?? '';
        $status = $_POST['status'] ?? 'active';

        // Basic validation
        if (empty($name) || empty($email)) {
            $_SESSION['flash_message'] = 'Name and email are required';
            $_SESSION['flash_type'] = 'error';
            header('Location: manage-users.php');
            exit();
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash_message'] = 'Please enter a valid email address';
            $_SESSION['flash_type'] = 'error';
            header('Location: manage-users.php');
            exit();
        }
        if (!empty($phone) && !preg_match('/^(\+?88)?01[3-9]\d{8}$/', $phone)) {
            $_SESSION['flash_message'] = 'Please enter a valid Bangladesh phone number (e.g., 01XXXXXXXXX or +8801XXXXXXXXX)';
            $_SESSION['flash_type'] = 'error';
            header('Location: manage-users.php');
            exit();
        }

        try {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ?, address = ?, status = ? WHERE id = ?");
            $stmt->execute([$name, $email, $phone, $address, $status, $user_id]);
            $_SESSION['flash_message'] = 'User updated successfully!';
            $_SESSION['flash_type'] = 'success';
            header('Location: manage-users.php');
            exit();
        } catch (PDOException $e) {
            $_SESSION['flash_message'] = 'Error updating user: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'error';
            header('Location: manage-users.php');
            exit();
        }
    } elseif ($action === 'delete_user') {
        $user_id = $_POST['user_id'] ?? 0;

        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $_SESSION['flash_message'] = 'User deleted successfully!';
            $_SESSION['flash_type'] = 'success';
            header('Location: manage-users.php');
            exit();
        } catch (PDOException $e) {
            $_SESSION['flash_message'] = 'Error deleting user: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'error';
            header('Location: manage-users.php');
            exit();
        }
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$where_conditions = [];
$params = [];

if ($status_filter !== 'all') {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
}

if (!empty($search)) {
    $where_conditions[] = "(name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get users from database with computed orders count and total_spent from orders table
try {
    $query = "SELECT 
                u.*,
                (
                  SELECT COUNT(*) 
                  FROM orders o_all 
                  WHERE o_all.user_id = u.id AND o_all.status = 'completed'
                ) AS orders,
                (
                  SELECT COALESCE(SUM(o_completed.total), 0)
                  FROM orders o_completed
                  WHERE o_completed.user_id = u.id AND o_completed.status = 'completed'
                ) AS total_spent
              FROM users u 
              $where_clause 
              ORDER BY u.created_at DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $users = [];
}

// Get user statistics
try {
    $stmt = $pdo->query("SELECT 
        COUNT(*) as total_users,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_users,
        SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_users,
        SUM(CASE WHEN status = 'banned' THEN 1 ELSE 0 END) as banned_users
        FROM users");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $stats = [
        'total_users' => 0,
        'active_users' => 0,
        'inactive_users' => 0,
        'banned_users' => 0
    ];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - HungryHub Admin</title>
    <link rel="stylesheet" href="../cssAdmin/manage-users.css?v=<?php echo time(); ?>">
</head>

<body>
    <div class="header">
        <h1>Manage Users</h1>
        <div class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="manage-food.php">Food</a>
            <a href="manage-orders.php">Orders</a>
            <a href="manage-users.php" class="active">Users</a>
            <a href="manage-areas.php">Areas</a>
            <a href="manage-coupons.php">Coupons</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <div id="toasts" class="toasts-container"></div>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['total_users']); ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['active_users']); ?></div>
                <div class="stat-label">Active Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['inactive_users']); ?></div>
                <div class="stat-label">Inactive Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['banned_users']); ?></div>
                <div class="stat-label">Banned Users</div>
            </div>
        </div>

        <?php /* Toasts will show via JS; keeping old block removed to avoid duplicates */ ?>

        <div class="filters">
            <form method="GET" action="">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="status">Filter by Status:</label>
                        <select name="status" id="status">
                            <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Users</option>
                            <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            <option value="banned" <?php echo $status_filter === 'banned' ? 'selected' : ''; ?>>Banned</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="search">Search:</label>
                        <input type="text" name="search" id="search" placeholder="Name, Email, or Phone" value="<?php echo htmlspecialchars($search); ?>">
                    </div>

                    <div class="filter-group filter-actions">
                        <label>&nbsp;</label>
                        <div class="actions-grid">
                            <button type="submit" class="filter-btn">Filter</button>
                            <a href="manage-users.php" class="filter-btn clear">Clear Filter</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="users-table">
            <div class="table-header">
                <h3>Users List</h3>
            </div>

            <?php if (empty($users)): ?>
                <div class="no-users">
                    <p>No users found. Make sure your database tables are set up correctly.</p>
                </div>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Orders</th>
                            <th>Total Spent</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>#<?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="status status-<?php echo $user['status']; ?>">
                                        <?php echo ucfirst($user['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo number_format($user['orders'] ?? 0); ?></td>
                                <td><span class="currency-symbol">৳</span><?php echo number_format($user['total_spent'] ?? 0); ?></td>
                                <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <a href="#" class="btn btn-edit" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($user)); ?>)">Edit</a>
                                    <a href="#" class="btn btn-delete" onclick="openDeleteModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['name'], ENT_QUOTES); ?>')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <h3>Edit User</h3>
            <form id="editForm" method="POST">
                <input type="hidden" name="action" value="update_user">
                <input type="hidden" name="user_id" id="editUserId">

                <div class="form-content">
                    <div class="form-group">
                        <label for="editName">Name:</label>
                        <input type="text" id="editName" name="name" required>
                    </div>

                    <div class="form-group">
                        <label for="editEmail">Email:</label>
                        <input type="email" id="editEmail" name="email" required>
                    </div>

                    <div class="form-group">
                        <label for="editPhone">Phone:</label>
                        <input type="tel" id="editPhone" name="phone" inputmode="tel" pattern="^(\+?88)?01[3-9]\d{8}$" title="Bangladesh mobile format: 01XXXXXXXXX or +8801XXXXXXXXX" placeholder="e.g., 017XXXXXXXX or +88017XXXXXXXX">
                    </div>

                    <div class="form-group">
                        <label for="editAddress">Address:</label>
                        <textarea id="editAddress" name="address" rows="3"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="editStatus">Status:</label>
                        <select id="editStatus" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="banned">Banned</option>
                        </select>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update User</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content" style="max-width: 480px;">
            <h3>Confirm Deletion</h3>
            <p id="deleteModalText" style="color:#555;margin:0 0 1rem 0;">Are you sure you want to delete this user?</p>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                <button type="button" class="btn btn-delete" onclick="confirmDelete()">Delete</button>
            </div>
        </div>
    </div>

    <script>
        window.__flashMessage = <?php echo json_encode(['type' => $message_type ?: '', 'text' => $message ?: '']); ?>;
    </script>
    <script src="../scriptAdmin/manage-users.js?v=<?php echo time(); ?>"></script>
</body>

</html>