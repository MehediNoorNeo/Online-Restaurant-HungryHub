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

// Get dashboard statistics
try {
    // Total food items
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM food_items");
    $total_food_items = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
    
    // Pending orders
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'");
    $pending_orders = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
    
    // Total customers
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $total_customers = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
    
    // Today's revenue: based only on completed orders
    $stmt = $pdo->query("SELECT SUM(total) as revenue FROM orders WHERE DATE(created_at) = CURDATE() AND status = 'completed'");
    $today_revenue_row = $stmt->fetch(PDO::FETCH_ASSOC);
    $today_revenue = isset($today_revenue_row['revenue']) && $today_revenue_row['revenue'] !== null ? $today_revenue_row['revenue'] : 0;
    
} catch(PDOException $e) {
    // If tables don't exist yet, set default values
    $total_food_items = 0;
    $pending_orders = 0;
    $total_customers = 0;
    $today_revenue = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - HungryHub</title>
    <link rel="stylesheet" href="../cssAdmin/dashboard.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="header">
        <h1>HungryHub Admin Dashboard</h1>
        <div class="user-info">
            <span>Welcome, <?php echo htmlspecialchars($admin_username); ?>!</span>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="dashboard-grid">
            <div class="dashboard-card">
                <div class="card-icon">🍽️</div>
                <h3 class="card-title">Manage Food Items</h3>
                <p class="card-description">Add, edit, or remove food items from the menu. Update prices, descriptions, and availability.</p>
                <a href="manage-food.php" class="card-btn">Manage Food</a>
            </div>
            
            <div class="dashboard-card">
                <div class="card-icon">📋</div>
                <h3 class="card-title">Manage Orders</h3>
                <p class="card-description">View and manage customer orders. Update order status and track delivery progress.</p>
                <a href="manage-orders.php" class="card-btn">Manage Orders</a>
            </div>
            
            <div class="dashboard-card">
                <div class="card-icon">👥</div>
                <h3 class="card-title">Manage Users</h3>
                <p class="card-description">View customer accounts, manage user permissions, and handle customer support.</p>
                <a href="manage-users.php" class="card-btn">Manage Users</a>
            </div>
            
            <div class="dashboard-card">
                <div class="card-icon">📍</div>
                <h3 class="card-title">Manage Areas</h3>
                <p class="card-description">Add, edit, or remove delivery areas. Manage service coverage and delivery zones.</p>
                <a href="manage-areas.php" class="card-btn">Manage Areas</a>
            </div>
            
            <div class="dashboard-card">
                <div class="card-icon">🏷️</div>
                <h3 class="card-title">Manage Coupons</h3>
                <p class="card-description">Create, edit, or delete discount coupons. Manage promotional codes and percentage discounts.</p>
                <a href="manage-coupons.php" class="card-btn">Manage Coupons</a>
            </div>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($total_food_items); ?></div>
                <div class="stat-label">Total Food Items</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($pending_orders); ?></div>
                <div class="stat-label">Pending Orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($total_customers); ?></div>
                <div class="stat-label">Total Customers</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">৳<?php echo number_format($today_revenue, 2); ?></div>
                <div class="stat-label">Today's Revenue</div>
            </div>
        </div>
        
        <div class="recent-orders">
            <h3>Recent Orders</h3>
            <?php
            try {
                $stmt = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 5");
                $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (empty($recent_orders)) {
                    echo "<p>No orders found. Make sure your database tables are set up correctly.</p>";
                } else {
                    foreach ($recent_orders as $order) {
                        $status_class = $order['status'] === 'completed' ? 'status-completed' : 'status-pending';
                        echo "<div class='order-item'>";
                        $order_label = (isset($order['order_id']) && $order['order_id'] !== '')
                            ? "Order ID: " . htmlspecialchars($order['order_id'])
                            : "Order #{$order['id']}";
                        echo "<div>{$order_label} - <span class='currency-symbol'>৳</span>" . number_format($order['total'], 2) . "</div>";
                        echo "<div class='order-status {$status_class}'>" . ucfirst($order['status']) . "</div>";
                        echo "</div>";
                    }
                }
            } catch(PDOException $e) {
                echo "<p>Unable to load recent orders. Please check your database connection.</p>";
            }
            ?>
        </div>
    </div>
</body>
</html>