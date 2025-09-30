<?php
// Page configuration
$page_title = 'Orders History - HungryHub';

// Database configuration
$host = 'localhost';
$dbname = 'hungry_hub';
$username = 'root';
$password = '';

require_once '../auth/auth_functions.php';
if (!isLoggedIn()) {
    header('Location: signin.php');
    exit();
}
$user = getCurrentUser();

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Connection failed: ' . $e->getMessage());
}

// Load completed and cancelled orders only (history)
$historyOrders = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? AND status IN ('completed','cancelled') ORDER BY created_at DESC");
    $stmt->execute([$user['id']]);
    $historyOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $historyOrders = [];
}

$include_checkout_css = true;
include '../components/header.php';
?>

<main class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="m-0">Orders History</h2>
            <div class="text-muted">Completed and cancelled orders</div>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="orders.php"><i class="fas fa-clock-rotate-left me-2"></i>Recent Orders</a>
            <a class="btn btn-outline-secondary" href="index.php"><i class="fas fa-arrow-left me-2"></i>Home</a>
        </div>
    </div>

    <?php if (empty($historyOrders)): ?>
        <div class="alert alert-secondary">No previous orders found.</div>
    <?php else: ?>
        <div class="table-responsive shadow-sm rounded">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Order #</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($historyOrders as $o): ?>
                    <tr>
                        <td class="fw-semibold"><?php echo htmlspecialchars($o['order_id']); ?></td>
                        <td><?php echo date('M j, Y g:i A', strtotime($o['created_at'])); ?></td>
                        <td class="text-capitalize"><?php echo htmlspecialchars($o['status']); ?></td>
                        <td class="text-uppercase small text-muted"><?php echo htmlspecialchars($o['payment_method']); ?> · <?php echo htmlspecialchars($o['payment_status']); ?></td>
                        <td class="text-end">৳<?php echo number_format((float)$o['total']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>


