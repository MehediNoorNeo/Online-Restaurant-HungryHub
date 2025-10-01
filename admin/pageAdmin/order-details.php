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

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$orderPublicId = $_GET['order_id'] ?? '';
if ($orderPublicId === '') {
    header('Location: manage-orders.php');
    exit();
}

// Load order
$order = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ?");
    $stmt->execute([$orderPublicId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $order = null;
}

if (!$order) {
    echo '<p style="padding:2rem;font-family:Inter,Segoe UI,Arial;">Order not found. <a href="manage-orders.php">Back to Orders</a></p>';
    exit();
}

// Parse items
$items = [];
if (!empty($order['order_items'])) {
    $decoded = json_decode($order['order_items'], true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $items = $decoded;
    }
}

// Build a map of food name -> image path for thumbnails
$food_image_map = [];
try {
    $stmt = $pdo->query("SELECT name, image FROM food_items");
    $foods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($foods as $f) {
        $food_image_map[strtolower($f['name'])] = $f['image'];
    }
} catch (PDOException $e) {
    $food_image_map = [];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - <?php echo htmlspecialchars($orderPublicId); ?></title>
    <link rel="stylesheet" href="../cssAdmin/manage-orders.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../cssAdmin/order-details.css?v=<?php echo time(); ?>">

</head>

<body>
    <div class="header">
        <h1>Order Details</h1>
        <div class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="manage-orders.php" class="active">Orders</a>
            <a href="manage-users.php">Users</a>
            <a href="manage-areas.php">Areas</a>
            <a href="manage-coupons.php">Coupons</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="details-container">
        <a class="back-link" href="manage-orders.php">← Back to Orders</a>
        <div class="details-card" style="margin-top:1rem;">
            <div class="details-header">
                <div>
                    <h2 style="margin:0;">Order #<?php echo htmlspecialchars($order['order_id']); ?></h2>
                    <div class="muted">Placed on <?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></div>
                </div>
                <div>
                    <span class="status status-<?php echo htmlspecialchars($order['status']); ?>"><?php echo ucfirst($order['status']); ?></span>
                </div>
            </div>

            <div class="grid" style="display:grid; grid-template-columns: 1fr 1fr; gap:2rem;">
                <div>
                    <h3 style="margin:0 0 .5rem 0;">Customer</h3>
                    <div><?php echo htmlspecialchars($order['customer_name'] ?? 'N/A'); ?></div>
                    <div class="muted"><?php echo htmlspecialchars($order['customer_email'] ?? ''); ?> · <?php echo htmlspecialchars($order['customer_phone'] ?? ''); ?></div>
                    <div class="muted" style="margin-top:.5rem;">
                        <?php
                        $addrParts = array_filter([
                            $order['delivery_address1'] ?? null,
                            $order['delivery_address2'] ?? null,
                            $order['delivery_road'] ? ('Road: ' . $order['delivery_road']) : null,
                            $order['delivery_street'] ? ('Street: ' . $order['delivery_street']) : null,
                            $order['delivery_area'] ? ('Area: ' . $order['delivery_area']) : null,
                        ]);
                        echo htmlspecialchars(implode(', ', $addrParts));
                        ?>
                    </div>
                    <div class="items-list">
                        <h3 style="margin:1rem 0 .5rem 0;">Items</h3>
                        <?php if (empty($items)): ?>
                            <div class="muted">No items found for this order.</div>
                        <?php else: ?>
                            <ul style="list-style:none; padding:0; margin:0;">
                                <?php foreach ($items as $it): ?>
                                    <li style="display:flex;justify-content:space-between;align-items:center;padding:.5rem 0;border-bottom:1px solid #f0f2f5;">
                                        <div style="display:flex; align-items:center; gap:.75rem;">
                                            <?php
                                            $lookupName = strtolower(trim($it['item_name'] ?? ''));
                                            $imgRel = $food_image_map[$lookupName] ?? '';
                                            $imgSrc = '';
                                            if (!empty($imgRel)) {
                                                if (strpos($imgRel, 'http') === 0 || strpos($imgRel, 'data:') === 0) {
                                                    $imgSrc = $imgRel;
                                                } else {
                                                    $webPath = '../../' . ltrim($imgRel, '/');
                                                    $fsPath = realpath(__DIR__ . '/../../' . ltrim($imgRel, '/'));
                                                    if ($fsPath && file_exists($fsPath)) {
                                                        $imgSrc = $webPath;
                                                    }
                                                }
                                            }
                                            ?>
                                            <?php if (!empty($imgSrc)): ?>
                                                <img src="<?php echo htmlspecialchars($imgSrc); ?>" alt="<?php echo htmlspecialchars($it['item_name'] ?? ''); ?>" style="width:48px;height:48px;object-fit:cover;border-radius:8px;">
                                            <?php endif; ?>
                                            <div>
                                                <div style="font-weight:600;">&nbsp;<?php echo htmlspecialchars($it['item_name'] ?? ''); ?></div>
                                                <div class="muted small"><?php echo htmlspecialchars($it['category'] ?? ''); ?></div>
                                            </div>
                                        </div>
                                        <div style="text-align:right;min-width:110px;">
                                            <div>x<?php echo (int)($it['quantity'] ?? 0); ?></div>
                                            <div class="muted">৳<?php echo number_format((float)($it['total_price'] ?? 0), 2); ?></div>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
                <div>
                    <h3 style="margin:0 0 .5rem 0;">Payment</h3>
                    <div class="muted text-capitalize">Method: <strong><?php echo htmlspecialchars($order['payment_method'] ?? ''); ?></strong></div>
                    <div class="muted text-capitalize">Status: <strong><?php echo htmlspecialchars($order['payment_status'] ?? ''); ?></strong></div>
                    <div style="margin-top:.75rem;">
                        <div class="d-flex justify-content-between"><span class="muted">Subtotal: </span><span>৳<?php echo number_format((float)($order['subtotal'] ?? 0), 2); ?></span></div>
                        <div class="d-flex justify-content-between"><span class="muted">Tax: </span><span>৳<?php echo number_format((float)($order['tax_amount'] ?? 0), 2); ?></span></div>
                        <div class="d-flex justify-content-between"><span class="muted">Delivery: </span><span>৳<?php echo number_format((float)($order['delivery_fee'] ?? 0), 2); ?></span></div>
                        <?php if (!empty($order['coupon_code'])): ?>
                            <div class="d-flex justify-content-between"><span class="muted">Coupon (<?php echo htmlspecialchars($order['coupon_code']); ?>): </span><span>-৳<?php echo number_format((float)($order['coupon_discount'] ?? 0), 2); ?></span></div>
                        <?php endif; ?>
                        <hr>
                        <div class="d-flex justify-content-between" style="font-weight:700;">
                            <span>Total: </span><span>৳<?php echo number_format((float)$order['total'], 2); ?></span>
                        </div>
                    </div>
                </div>
            </div>


        </div>
    </div>
</body>

</html>