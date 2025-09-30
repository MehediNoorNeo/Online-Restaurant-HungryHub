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
} catch(PDOException $e) {
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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_status') {
        $order_id = $_POST['order_id'] ?? 0;
        $new_status = $_POST['status'] ?? '';
        
        try {
            $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $order_id]);
            $message = 'Order status updated successfully!';
            $message_type = 'success';
        } catch(PDOException $e) {
            $message = 'Error updating order status: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';
// Normalize input: allow searching with or without a leading '#'
$normalized_search = ltrim($search, '#');

// Build query
$where_conditions = [];
$params = [];

if ($status_filter !== 'all') {
    $where_conditions[] = "o.status = ?";
    $params[] = $status_filter;
}

if (!empty($normalized_search)) {
    $where_conditions[] = "(u.name LIKE ? OR u.email LIKE ? OR o.order_id LIKE ?)";
    $params[] = "%$normalized_search%";
    $params[] = "%$normalized_search%";
    $params[] = "%$normalized_search%";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get orders from database
try {
    $query = "SELECT o.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone 
              FROM orders o 
              LEFT JOIN users u ON o.user_id = u.id 
              $where_clause 
              ORDER BY o.created_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $orders = [];
}

// Get order statistics
try {
    $stmt = $pdo->query("SELECT 
        COUNT(*) as total_orders,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
        SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing_orders,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
        SUM(CASE WHEN status = 'completed' THEN total ELSE 0 END) as total_revenue
        FROM orders");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $stats = [
        'total_orders' => 0,
        'pending_orders' => 0,
        'processing_orders' => 0,
        'completed_orders' => 0,
        'cancelled_orders' => 0,
        'total_revenue' => 0
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - HungryHub Admin</title>
    <link rel="stylesheet" href="../cssAdmin/manage-orders.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="header">
        <h1>Manage Orders</h1>
        <div class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="manage-food.php">Food</a>
            <a href="manage-orders.php" class="active">Orders</a>
            <a href="manage-users.php">Users</a>
            <a href="manage-areas.php">Areas</a>
            <a href="manage-coupons.php">Coupons</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['total_orders']); ?></div>
                <div class="stat-label">Total Orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['pending_orders']); ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['processing_orders']); ?></div>
                <div class="stat-label">Processing</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['completed_orders']); ?></div>
                <div class="stat-label">Completed</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><span class="currency-symbol">৳</span><?php echo number_format($stats['total_revenue'], 2); ?></div>
                <div class="stat-label">Total Revenue</div>
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="filters">
            <form method="GET" action="">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="status">Filter by Status:</label>
                        <select name="status" id="status">
                            <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Orders</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="processing" <?php echo $status_filter === 'processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="search">Search:</label>
                        <input type="text" name="search" id="search" placeholder="Order ID, Customer Name, or Email" value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    
                    <div class="filter-group filter-actions">
                        <label>&nbsp;</label>
                        <div class="actions-grid">
                            <button type="submit" class="filter-btn">Filter</button>
                            <a href="manage-orders.php" class="filter-btn clear">Clear Filter</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        
        <div class="orders-table">
            <div class="table-header">
                <h3>Orders List</h3>
            </div>
            
            <?php if (empty($orders)): ?>
                <div class="no-orders">
                    <p>No orders found. Make sure your database tables are set up correctly.</p>
                </div>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>S/N</th>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $index => $order): ?>
                        <tr class="order-row" data-href="order-details.php?order_id=<?php echo urlencode($order['order_id']); ?>">
                            <td><?php echo $index + 1; ?></td>
                            <td><a class="order-link" href="order-details.php?order_id=<?php echo urlencode($order['order_id']); ?>">#<?php echo htmlspecialchars($order['order_id']); ?></a></td>
                            <td><?php echo htmlspecialchars($order['customer_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($order['customer_email'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($order['customer_phone'] ?? 'N/A'); ?></td>
                            <td><span class="list-currency-symbol">৳</span><?php echo number_format($order['total'], 2); ?></td>
                            <td>
                                <span class="status status-<?php echo $order['status']; ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></td>
                            <td>
                                <form method="POST" style="display: inline-block;">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                    <select name="status" class="status-select">
                                        <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                        <option value="completed" <?php echo $order['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                        <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                    <button type="submit" class="update-btn">Update</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
<script>
// Make the entire row clickable except the Actions cell
document.querySelectorAll('.order-row').forEach(function(row){
  row.addEventListener('click', function(e){
    // prevent when clicking inside the form/actions
    if (e.target.closest('form') || e.target.closest('select') || e.target.closest('button') || e.target.closest('a.order-link')) return;
    const href = this.getAttribute('data-href');
    if (href) { window.location.href = href; }
  });
});
</script>
</body>
</html>
