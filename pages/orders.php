<?php
// Page configuration
$page_title = 'My Orders - HungryHub';

// Database configuration
$host = 'localhost';
$dbname = 'hungry_hub';
$username = 'root';
$password = '';

// Auth
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

// Fetch all orders for this user (most recent first)
$orders = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user['id']]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $orders = [];
}

// Split by status: active on this page, completed/cancelled in history
$activeOrders = [];
$historyOrders = [];
foreach ($orders as $o) {
    $s = strtolower($o['status'] ?? '');
    if ($s === 'pending' || $s === 'processing') {
        $activeOrders[] = $o;
    } elseif ($s === 'completed' || $s === 'cancelled') {
        $historyOrders[] = $o;
    }
}

// Compute status counts for summary and filters (active only)
$statusCounts = [
    'all' => count($activeOrders),
    'pending' => 0,
    'processing' => 0,
];
foreach ($activeOrders as $o) {
    $s = strtolower($o['status']);
    if (isset($statusCounts[$s])) { $statusCounts[$s]++; }
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

// Flags for styles used on this page
$include_checkout_css = true; // reuse some layout spacing/styles

include '../components/header.php';
?>

<main class="container py-4">
    <style>
        .orders-hero { background: #f8fafc; border: 1px solid #eef2f7; border-radius: 12px; }
        .status-pill { text-transform: capitalize; }
        .status-pill.pending { background:#fff3cd; color:#7a5b00; }
        .status-pill.processing { background:#e7f1ff; color:#0b5ed7; }
        .status-pill.completed { background:#e6f6ec; color:#0b7c3e; }
        .status-pill.cancelled { background:#fdecec; color:#a12525; }
        .order-card .card-header { background:#ffffff; }
        .order-items-thumb { width:48px; height:48px; object-fit:cover; border-radius:8px; }
        .filter-pills .nav-link { border-radius: 999px; }
        .summary-stat { min-width: 110px; }
    </style>

    <div class="orders-hero p-3 p-md-4 mb-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="display-6 text-success mb-0"><i class="fas fa-shopping-bag"></i></div>
            <div>
                <h2 class="m-0">My Orders</h2>
                <div class="text-muted">Track your food orders and their status</div>
            </div>
        </div>
        <div class="d-flex gap-3">
            <div class="summary-stat text-center">
                <div class="small text-muted">All</div>
                <div class="fs-5 fw-semibold"><?php echo $statusCounts['all']; ?></div>
            </div>
            <div class="summary-stat text-center">
                <div class="small text-muted">Pending</div>
                <div class="fs-5 fw-semibold"><?php echo $statusCounts['pending']; ?></div>
            </div>
            <div class="summary-stat text-center">
                <div class="small text-muted">Processing</div>
                <div class="fs-5 fw-semibold"><?php echo $statusCounts['processing']; ?></div>
            </div>
            
        </div>
        <a class="btn btn-outline-secondary ms-auto" href="index.php"><i class="fas fa-arrow-left me-2"></i>Back to Home</a>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="m-0">Active Orders</h5>
        <a class="btn btn-sm btn-outline-secondary" href="orders-history.php"><i class="fas fa-clock-rotate-left me-1"></i>Orders History</a>
    </div>

    <div>
            <?php if (!empty($activeOrders)): ?>
                <ul class="nav nav-pills filter-pills mb-4" id="ordersFilter">
                    <li class="nav-item"><a class="nav-link active" data-status="all" href="#">All (<?php echo $statusCounts['all']; ?>)</a></li>
                    <li class="nav-item"><a class="nav-link" data-status="pending" href="#">Pending (<?php echo $statusCounts['pending']; ?>)</a></li>
                    <li class="nav-item"><a class="nav-link" data-status="processing" href="#">Processing (<?php echo $statusCounts['processing']; ?>)</a></li>
                </ul>
            <?php else: ?>
                <div class="alert alert-info">You have no active orders.</div>
            <?php endif; ?>
    </div>

    <?php if (empty($activeOrders)): ?>
        <div class="alert alert-info">You don't have any orders yet.</div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($activeOrders as $order): ?>
                <?php
                    $items = [];
                    if (!empty($order['order_items'])) {
                        $decoded = json_decode($order['order_items'], true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                            $items = $decoded;
                        }
                    }
                    $status = $order['status'];
                    $statusClass = 'secondary';
                    if ($status === 'pending') { $statusClass = 'warning'; }
                    if ($status === 'processing') { $statusClass = 'primary'; }
                    if ($status === 'completed') { $statusClass = 'success'; }
                ?>
                <div class="col-12 order-card" data-status="<?php echo htmlspecialchars(strtolower($status)); ?>">
                    <div class="card shadow-sm">
                        <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
                            <div class="fw-semibold">Order #<?php echo htmlspecialchars($order['order_id']); ?></div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge status-pill <?php echo htmlspecialchars(strtolower($status)); ?>"><?php echo ucfirst($status); ?></span>
                                <small class="text-muted"><?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></small>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="small text-muted">Payment: <span class="fw-semibold text-dark text-uppercase"><?php echo htmlspecialchars($order['payment_method']); ?></span> • <span class="text-capitalize"><?php echo htmlspecialchars($order['payment_status']); ?></span></div>
                                <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#orderItems-<?php echo $order['id']; ?>">
                                    <i class="fas fa-list me-1"></i> View items
                                </button>
                            </div>
                            <div class="row">
                                <div class="col-12 col-lg-6 mb-3 mb-lg-0">
                                    <div class="collapse show" id="orderItems-<?php echo $order['id']; ?>">
                                        <div class="mb-2 fw-semibold">Items</div>
                                        <?php if (empty($items)): ?>
                                            <div class="text-muted">No items found.</div>
                                        <?php else: ?>
                                        <ul class="list-group list-group-flush">
                                            <?php foreach ($items as $it): ?>
                                                <?php
                                                    $lookupName = strtolower($it['item_name'] ?? '');
                                                    $imgRel = $food_image_map[$lookupName] ?? '';
                                                    $imgSrc = '';
                                                    if (!empty($imgRel)) {
                                                        $imgSrc = (strpos($imgRel, 'http') === 0) ? $imgRel : '../' . $imgRel;
                                                    }
                                                ?>
                                                <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                                    <div class="d-flex align-items-center gap-3">
                                                        <?php if (!empty($imgSrc)): ?>
                                                        <img class="order-items-thumb" src="<?php echo htmlspecialchars($imgSrc); ?>" alt="<?php echo htmlspecialchars($it['item_name']); ?>">
                                                        <?php endif; ?>
                                                        <div>
                                                            <div class="fw-semibold"><?php echo htmlspecialchars($it['item_name']); ?></div>
                                                            <small class="text-muted"><?php echo htmlspecialchars($it['category'] ?? ''); ?></small>
                                                        </div>
                                                    </div>
                                                    <div class="text-end">
                                                        <div>x<?php echo (int)($it['quantity'] ?? 0); ?></div>
                                                        <small class="text-muted">৳<?php echo number_format((float)($it['total_price'] ?? 0)); ?></small>
                                                    </div>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-12 col-lg-6">
                                    <div class="mb-2 fw-semibold">Delivery</div>
                                    <div class="mb-3">
                                        <div><?php echo htmlspecialchars($order['customer_name']); ?></div>
                                        <div class="text-muted small"><?php echo htmlspecialchars($order['customer_phone']); ?> · <?php echo htmlspecialchars($order['customer_email']); ?></div>
                                        <div class="text-muted small">
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
                                    </div>

                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Subtotal</span>
                                        <span>৳<?php echo number_format((float)$order['subtotal']); ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Tax</span>
                                        <span>৳<?php echo number_format((float)$order['tax_amount']); ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Delivery</span>
                                        <span>৳<?php echo number_format((float)$order['delivery_fee']); ?></span>
                                    </div>
                                    <?php if (!empty($order['coupon_code'])): ?>
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Coupon (<?php echo htmlspecialchars($order['coupon_code']); ?>)</span>
                                        <span>-৳<?php echo number_format((float)$order['coupon_discount']); ?></span>
                                    </div>
                                    <?php endif; ?>
                                    <hr class="my-2" />
                                    <div class="d-flex justify-content-between fs-5 fw-semibold">
                                        <span>Total</span>
                                        <span>৳<?php echo number_format((float)$order['total']); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<script>
  (function(){
    const pills = document.querySelectorAll('#ordersFilter .nav-link');
    const cards = document.querySelectorAll('.order-card');
    pills.forEach(p => {
      p.addEventListener('click', function(e){
        e.preventDefault();
        pills.forEach(x => x.classList.remove('active'));
        this.classList.add('active');
        const status = this.getAttribute('data-status');
        cards.forEach(c => {
          const s = c.getAttribute('data-status');
          c.style.display = (status === 'all' || s === status) ? '' : 'none';
        });
      });
    });
  })();
</script>

<?php include '../components/footer.php'; ?>


