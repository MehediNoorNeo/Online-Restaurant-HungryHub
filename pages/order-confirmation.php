<?php
session_start();

// Database configuration
$host = 'localhost';
$dbname = 'hungry_hub';
$username = 'root';
$password = '';

// Check if user is logged in
require_once '../auth/auth_functions.php';

if (!isLoggedIn()) {
    header('Location: signin.php');
    exit();
}

// Check if order success data exists
if (!isset($_SESSION['order_success'])) {
    header('Location: index.php');
    exit();
}

$order_success = $_SESSION['order_success'];
$user = getCurrentUser();

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get order details from database using numeric id if available, otherwise fallback
    $orderLookupId = $order_success['id'] ?? null;
    if ($orderLookupId) {
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$orderLookupId]);
    } else {
        // Backward compatibility: some sessions may pass order_id string
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ?");
        $stmt->execute([$order_success['order_id']]);
    }
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        header('Location: index.php');
        exit();
    }
    
    // Decode order items
    $order_items = json_decode($order['order_items'], true);
    
} catch(PDOException $e) {
    error_log("Database connection error: " . $e->getMessage());
    $order = null;
    $order_items = [];
}

// Clear order success data from session
unset($_SESSION['order_success']);
// If process step requested cart clear, ensure cart badge resets
if (!empty($_SESSION['clear_cart_client'])) {
    echo "<script>localStorage.removeItem('hungryHubCart');window.dispatchEvent(new CustomEvent('cartUpdated'));</script>";
    unset($_SESSION['clear_cart_client']);
}

// Page configuration
$page_title = 'Order Confirmation - HungryHub';
$include_checkout_css = true;

include '../components/header.php';
?>

<main class="order-confirmation-page">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- Success Header -->
                <div class="confirmation-header text-center mb-5">
                    <div class="success-icon mb-4">
                        <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                    </div>
                    <h1 class="confirmation-title text-success mb-3">Order Confirmed!</h1>
                    <p class="confirmation-subtitle text-muted">
                        Thank you for your order. We'll start preparing your delicious meal right away.
                    </p>
                </div>

                <?php if ($order): ?>
                <!-- Order Details Card -->
                <div class="order-details-card">
                    <div class="card-header">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-receipt me-2"></i>Order Details
                        </h3>
                    </div>
                    <div class="card-body">
                        <!-- Order Info -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="info-item">
                                    <label>Order ID:</label>
                                    <span class="order-number"><?php echo htmlspecialchars($order['order_id']); ?></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-item">
                                    <label>Order Date:</label>
                                    <span><?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-item">
                                    <label>Payment Method:</label>
                                    <span class="payment-method">
                                        <?php if ($order['payment_method'] === 'card'): ?>
                                            <i class="fas fa-credit-card me-1"></i>Card Payment
                                        <?php else: ?>
                                            <i class="fas fa-money-bill-wave me-1"></i>Cash on Delivery
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-item">
                                    <label>Status:</label>
                                    <span class="order-status status-<?php echo $order['status']; ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-item">
                                    <label>Payment Status:</label>
                                    <span class="order-status status-<?php echo strtolower($order['payment_status']); ?>">
                                        <?php echo ucfirst($order['payment_status']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Delivery Address -->
                        <div class="delivery-info mb-4">
                            <h5 class="section-title">
                                <i class="fas fa-map-marker-alt me-2"></i>Delivery Address
                            </h5>
                            <div class="address-details">
                                <p class="mb-1">
                                    <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong>
                                </p>
                                <p class="mb-1"><?php echo htmlspecialchars($order['customer_phone']); ?></p>
                                <p class="mb-1"><?php echo htmlspecialchars($order['customer_email']); ?></p>
                                <p class="mb-0">
                                    <?php 
                                    $address_parts = array_filter([
                                        $order['delivery_address1'],
                                        $order['delivery_address2'],
                                        $order['delivery_street'],
                                        $order['delivery_road'],
                                        $order['delivery_area']
                                    ]);
                                    echo htmlspecialchars(implode(', ', $address_parts));
                                    ?>
                                </p>
                            </div>
                        </div>

                        <!-- Order Items -->
                        <div class="order-items mb-4">
                            <h5 class="section-title">
                                <i class="fas fa-utensils me-2"></i>Order Items
                            </h5>
                            <div class="items-list">
                                <?php foreach ($order_items as $item): ?>
                                <div class="order-item">
                                    <div class="item-info">
                                        <div class="item-name"><?php echo htmlspecialchars($item['item_name']); ?></div>
                                        <div class="item-category"><?php echo htmlspecialchars($item['category']); ?></div>
                                    </div>
                                    <div class="item-quantity">x<?php echo $item['quantity']; ?></div>
                                    <div class="item-price">৳<?php echo number_format($item['total_price'], 2); ?></div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Order Summary -->
                        <div class="order-summary">
                            <h5 class="section-title">
                                <i class="fas fa-calculator me-2"></i>Order Summary
                            </h5>
                            <div class="summary-details">
                                <div class="summary-row">
                                    <span>Subtotal:</span>
                                    <span>৳<?php echo number_format($order['subtotal'], 2); ?></span>
                                </div>
                                <div class="summary-row">
                                    <span>Tax (5%):</span>
                                    <span>৳<?php echo number_format($order['tax_amount'], 2); ?></span>
                                </div>
                                <div class="summary-row">
                                    <span>Delivery Fee:</span>
                                    <span>৳<?php echo number_format($order['delivery_fee'], 2); ?></span>
                                </div>
                                <?php if ($order['coupon_discount'] > 0): ?>
                                <div class="summary-row text-success">
                                    <span>Coupon Discount (<?php echo htmlspecialchars($order['coupon_code']); ?>):</span>
                                    <span>-৳<?php echo number_format($order['coupon_discount'], 2); ?></span>
                                </div>
                                <?php endif; ?>
                                <hr>
                                <div class="summary-row total-row">
                                    <span><strong>Total:</strong></span>
                                    <span><strong>৳<?php echo number_format($order['discount_total'] ?? $order['total'], 2); ?></strong></span>
                                </div>
                            </div>
                        </div>

                        <?php if ($order['coupon_discount'] > 0): ?>
                        <!-- Coupon Information -->
                        <div class="coupon-info mt-4">
                            <h5 class="section-title">
                                <i class="fas fa-tag me-2"></i>Coupon Applied
                            </h5>
                            <div class="coupon-details">
                                <div class="coupon-card">
                                    <div class="coupon-info-item">
                                        <label>Coupon Code:</label>
                                        <span class="coupon-code"><?php echo htmlspecialchars($order['coupon_code']); ?></span>
                                    </div>
                                    <div class="coupon-info-item">
                                        <label>Discount Amount:</label>
                                        <span class="discount-amount text-success">-৳<?php echo number_format($order['coupon_discount'], 2); ?></span>
                                    </div>
                                    <div class="coupon-info-item">
                                        <label>You Saved:</label>
                                        <span class="savings-amount text-success">৳<?php echo number_format($order['coupon_discount'], 2); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($order['order_notes'])): ?>
                        <!-- Order Notes -->
                        <div class="order-notes mt-4">
                            <h5 class="section-title">
                                <i class="fas fa-sticky-note me-2"></i>Special Instructions
                            </h5>
                            <p class="notes-text"><?php echo htmlspecialchars($order['order_notes']); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="confirmation-actions text-center mt-4">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <a href="index.php" class="btn btn-outline-primary w-100">
                                <i class="fas fa-home me-2"></i>Continue Shopping
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="profile.php" class="btn btn-primary w-100">
                                <i class="fas fa-user me-2"></i>View Profile
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Estimated Delivery -->
                <div class="delivery-estimate mt-4">
                    <div class="alert alert-info text-center">
                        <i class="fas fa-clock me-2"></i>
                        <strong>Estimated Delivery Time:</strong> 30-45 minutes
                    </div>
                </div>

                <?php else: ?>
                <!-- Error State -->
                <div class="error-state text-center">
                    <div class="error-icon mb-4">
                        <i class="fas fa-exclamation-triangle text-warning" style="font-size: 4rem;"></i>
                    </div>
                    <h2 class="error-title mb-3">Order Not Found</h2>
                    <p class="error-message text-muted mb-4">
                        We couldn't find your order details. Please contact support if this issue persists.
                    </p>
                    <a href="index.php" class="btn btn-primary">
                        <i class="fas fa-home me-2"></i>Go Home
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<style>
.order-confirmation-page {
    min-height: 80vh;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.confirmation-header {
    background: white;
    padding: 3rem 2rem;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.order-details-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    overflow: hidden;
}

.card-header {
    background: linear-gradient(135deg, #ffc107 0%, #ff8f00 100%);
    color: white;
    padding: 1.5rem 2rem;
    border: none;
}

.card-body {
    padding: 2rem;
}

.info-item {
    margin-bottom: 1rem;
}

.info-item label {
    font-weight: 600;
    color: #6c757d;
    display: block;
    margin-bottom: 0.25rem;
}

.order-number {
    font-family: 'Courier New', monospace;
    font-weight: bold;
    color: #ffc107;
    font-size: 1.1rem;
}

.payment-method {
    color: #28a745;
    font-weight: 500;
}

.order-status {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 500;
}

.status-pending {
    background-color: #fff3cd;
    color: #856404;
}

.status-processing {
    background-color: #cce5ff;
    color: #004085;
}

.status-completed {
    background-color: #d4edda;
    color: #155724;
}

/* Payment status badges */
.status-paid {
    background-color: #d4edda;
    color: #155724;
}
.status-unpaid, .status-due, .status-failed {
    background-color: #fff3cd;
    color: #856404;
}

.section-title {
    color: #495057;
    font-weight: 600;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #e9ecef;
}

.address-details {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 8px;
}

.order-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 0;
    border-bottom: 1px solid #e9ecef;
}

.order-item:last-child {
    border-bottom: none;
}

.item-info {
    flex: 1;
}

.item-name {
    font-weight: 600;
    color: #495057;
}

.item-category {
    font-size: 0.875rem;
    color: #6c757d;
}

.item-quantity {
    font-weight: 500;
    color: #495057;
    margin: 0 1rem;
}

.item-price {
    font-weight: 600;
    color: #28a745;
}

.summary-details {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
}

.total-row {
    font-size: 1.1rem;
    margin-top: 0.5rem;
}

.notes-text {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 8px;
    font-style: italic;
    color: #6c757d;
}

.confirmation-actions .btn {
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 500;
}

.delivery-estimate .alert {
    border: none;
    border-radius: 8px;
    font-weight: 500;
}

.error-state {
    background: white;
    padding: 3rem 2rem;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

/* Coupon Information Styles */
.coupon-info {
    background: white;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    overflow: hidden;
}

.coupon-details {
    padding: 1.5rem;
}

.coupon-card {
    background: linear-gradient(135deg, #e8f5e8 0%, #f0f8f0 100%);
    border: 2px dashed #28a745;
    border-radius: 12px;
    padding: 1.5rem;
    text-align: center;
}

.coupon-info-item {
    margin-bottom: 1rem;
}

.coupon-info-item:last-child {
    margin-bottom: 0;
}

.coupon-info-item label {
    font-weight: 600;
    color: #495057;
    display: block;
    margin-bottom: 0.25rem;
    font-size: 0.9rem;
}

.coupon-code {
    font-family: 'Courier New', monospace;
    font-weight: bold;
    color: #28a745;
    font-size: 1.2rem;
    background: white;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    display: inline-block;
    border: 1px solid #28a745;
}

.discount-amount, .savings-amount {
    font-weight: bold;
    font-size: 1.1rem;
}
</style>

<script>
// Clear cart after successful order
document.addEventListener('DOMContentLoaded', function() {
    // Clear cart from localStorage
    localStorage.removeItem('hungryHubCart');
    
    // Dispatch event to notify other components
    window.dispatchEvent(new CustomEvent('cartUpdated'));
});
</script>

<?php include '../components/footer.php'; ?>