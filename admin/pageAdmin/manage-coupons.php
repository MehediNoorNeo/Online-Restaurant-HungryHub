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

// Handle form submissions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            switch ($_POST['action']) {
                case 'create':
                    $coupon_code = strtoupper(trim($_POST['coupon_code']));
                    $percentage = floatval($_POST['percentage']);
                    
                    // Validate input
                    if (empty($coupon_code) || $percentage <= 0 || $percentage > 100) {
                        throw new Exception('Invalid coupon data');
                    }
                    
                    $stmt = $pdo->prepare("INSERT INTO coupons (coupon_code, percentage) VALUES (?, ?)");
                    $stmt->execute([$coupon_code, $percentage]);
                    $message = 'Coupon created successfully!';
                    $message_type = 'success';
                    break;
                    
                case 'update':
                    $id = intval($_POST['id']);
                    $coupon_code = strtoupper(trim($_POST['coupon_code']));
                    $percentage = floatval($_POST['percentage']);
                    
                    // Validate input
                    if (empty($coupon_code) || $percentage <= 0 || $percentage > 100) {
                        throw new Exception('Invalid coupon data');
                    }
                    
                    $stmt = $pdo->prepare("UPDATE coupons SET coupon_code = ?, percentage = ? WHERE id = ?");
                    $stmt->execute([$coupon_code, $percentage, $id]);
                    $message = 'Coupon updated successfully!';
                    $message_type = 'success';
                    break;
                    
                case 'delete':
                    $id = intval($_POST['id']);
                    $stmt = $pdo->prepare("DELETE FROM coupons WHERE id = ?");
                    $stmt->execute([$id]);
                    $message = 'Coupon deleted successfully!';
                    $message_type = 'success';
                    break;
            }
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Fetch all coupons
try {
    $stmt = $pdo->query("SELECT id, coupon_code, percentage, created_date FROM coupons ORDER BY id DESC");
    $coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $coupons = [];
    $message = 'Error fetching coupons: ' . $e->getMessage();
    $message_type = 'error';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Coupons - HungryHub Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../cssAdmin/manage-coupons.css">
</head>
<body>
    <div class="header">
        <h1>Manage Coupons</h1>
        <div class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="manage-food.php">Food</a>
            <a href="manage-orders.php">Orders</a>
            <a href="manage-users.php">Users</a>
            <a href="manage-areas.php">Areas</a>
            <a href="manage-coupons.php" class="active">Coupons</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="page-header">
            <h2>Coupon Management</h2>
            <div class="header-buttons">
                <a href="#" class="add-food-btn" onclick="openCouponModal('add')">+ Add New Coupon</a>
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <!-- Coupons Table -->
        <div class="table-container">
            <table class="food-table">
                <thead>
                    <tr>
                        <th>Serial No.</th>
                        <th>Coupon Code</th>
                        <th>Percentage</th>
                        <th>Created Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($coupons)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                <i class="fas fa-tag fa-2x mb-2"></i>
                                <p>No coupons found. Create your first coupon!</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($coupons as $index => $coupon): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td>
                                    <span class="coupon-code"><?php echo htmlspecialchars($coupon['coupon_code']); ?></span>
                                </td>
                                <td>
                                    <span class="percentage-badge"><?php echo $coupon['percentage']; ?>%</span>
                                </td>
                                <td>
                                    <?php 
                                    if (isset($coupon['created_date']) && !empty($coupon['created_date'])) {
                                        echo date('M j, Y', strtotime($coupon['created_date']));
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary me-1" 
                                            onclick="editCoupon(<?php echo htmlspecialchars(json_encode($coupon)); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" 
                                            onclick="deleteCoupon(<?php echo $coupon['id']; ?>, '<?php echo htmlspecialchars($coupon['coupon_code']); ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Coupon Modal -->
    <div class="modal fade" id="couponModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add New Coupon</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="couponForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" id="formAction" value="create">
                        <input type="hidden" name="id" id="couponId">
                        
                        <div class="mb-3">
                            <label for="coupon_code" class="form-label">Coupon Code *</label>
                            <input type="text" class="form-control" id="coupon_code" name="coupon_code" 
                                   placeholder="e.g., WELCOME10" required maxlength="20">
                            <div class="form-text">Enter a unique coupon code (will be converted to uppercase)</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="percentage" class="form-label">Discount Percentage *</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="percentage" name="percentage" 
                                       min="1" max="100" step="0.01" placeholder="10" required>
                                <span class="input-group-text">%</span>
                            </div>
                            <div class="form-text">Enter discount percentage (1-100%)</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="fas fa-save me-1"></i>Save Coupon
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the coupon <strong id="deleteCouponCode"></strong>?</p>
                    <p class="text-danger">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" id="deleteCouponId">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash me-1"></i>Delete Coupon
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../scriptAdmin/manage-coupons.js?v=<?php echo time(); ?>"></script>
</body>
</html>
