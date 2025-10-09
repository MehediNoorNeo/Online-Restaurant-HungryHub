<?php
// Page configuration
$page_title = 'Checkout - HungryHub';
$include_checkout_css = true;
$include_checkout_js = true;
$include_card_payment_css = true;

// Database configuration
$host = 'localhost';
$dbname = 'hungry_hub';
$username = 'root';
$password = '';

// Get user data if logged in
$user = null;
$userProfile = null;
$areas = [];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if user is logged in
    require_once '../auth/auth_functions.php';
    
    if (isLoggedIn()) {
        $user = getCurrentUser();
        $userProfile = getUserProfile($user['id']);
    } else {
        // Redirect to signin page if user is not logged in
        $_SESSION['checkout_redirect'] = true;
        $_SESSION['checkout_message'] = 'Please sign in to proceed with checkout.';
        header('Location: signin.php');
        exit();
    }
    
    // Fetch areas from database
    $stmt = $pdo->query("SELECT id, area_name FROM areas ORDER BY area_name ASC");
    $areas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    // Handle database connection error
    $user = null;
    $userProfile = null;
    $areas = [];
}

include '../components/header.php';
?>

<main class="checkout-page">
    <!-- Mobile Navigation Header -->
    <div class="mobile-nav-header d-md-none py-2 bg-white border-bottom">
        <div class="container">
            <div class="d-flex align-items-center justify-content-between">
                <a href="javascript:history.back()" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i>Back
                </a>
                <h5 class="mb-0 text-center flex-grow-1">Checkout</h5>
                <div style="width: 60px;"></div> <!-- Spacer for centering -->
            </div>
        </div>
    </div>

    <!-- Progress Indicator -->
    <section class="progress-section py-3 py-md-4 bg-light">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-lg-8">
                    <div class="progress-indicator d-flex align-items-center justify-content-between">
                        <div class="step active">
                            <div class="step-circle">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <span class="step-label">Cart</span>
                        </div>
                        <div class="step-line d-none d-md-block"></div>
                        <div class="step" id="info-step">
                            <div class="step-circle">
                                <i class="fas fa-info-circle"></i>
                            </div>
                            <span class="step-label">Information</span>
                        </div>
                        <div class="step-line d-none d-md-block"></div>
                        <div class="step">
                            <div class="step-circle">
                                <i class="fas fa-credit-card"></i>
                            </div>
                            <span class="step-label">Payment</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Checkout Form -->
    <section class="checkout-form-section py-3 py-md-5">
        <div class="container">
            <div class="row">
                <!-- Left Column - Checkout Form -->
                <div class="col-12 col-lg-7 order-2 order-lg-1">
                    <form id="checkoutForm" class="checkout-form" method="POST" action="process-checkout.php">
                        <!-- Hidden field to store cart data -->
                        <input type="hidden" id="cart_data" name="cart_data" value="">
                        <!-- Error Messages -->
                        <?php if (isset($_SESSION['checkout_errors'])): ?>
                            <div class="alert alert-danger mb-4">
                                <h6><i class="fas fa-exclamation-triangle me-2"></i>Please fix the following errors:</h6>
                                <ul class="mb-0">
                                    <?php foreach ($_SESSION['checkout_errors'] as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php unset($_SESSION['checkout_errors']); ?>
                        <?php endif; ?>

                        <!-- Contact Information -->
                        <div class="checkout-section mb-5">
                            <h3 class="section-title mb-4">
                                <i class="fas fa-user me-2"></i>Contact Information
                            </h3>
                            <div class="row">
                                <div class="col-12 col-md-6 mb-3">
                                    <label for="full_name" class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" 
                                           value="<?php echo $userProfile ? htmlspecialchars($userProfile['name']) : ''; ?>" 
                                           required>
                                </div>
                                <div class="col-12 col-md-6 mb-3">
                                    <label for="email" class="form-label">Email Address *</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo $userProfile ? htmlspecialchars($userProfile['email']) : ''; ?>" 
                                           required>
                                </div>
                                <div class="col-12 col-md-6 mb-3">
                                    <label for="phone" class="form-label">Phone Number *</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           value="<?php echo $userProfile ? htmlspecialchars($userProfile['phone']) : ''; ?>" 
                                           placeholder="01XXXXXXXXX" required>
                                    <div class="form-text">Enter your Bangladesh phone number</div>
                                </div>
                            </div>
                        </div>

                        <!-- Delivery Address -->
                        <div class="checkout-section mb-5">
                            <h3 class="section-title mb-4">
                                <i class="fas fa-map-marker-alt me-2"></i>Delivery Address
                            </h3>
                            <div class="row">
                                <div class="col-12 col-md-4 mb-3">
                                    <label for="area" class="form-label">Area *</label>
                                    <select class="form-select" id="area" name="area" required>
                                        <option value="">Select Area</option>
                                        <?php if (!empty($areas)): ?>
                                            <?php foreach ($areas as $area): ?>
                                                <option value="<?php echo htmlspecialchars($area['area_name']); ?>">
                                                    <?php echo htmlspecialchars($area['area_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <!-- Fallback options if database is not available -->
                                            <option value="Bashundhara">Bashundhara</option>
                                            <option value="Gulshan">Gulshan</option>
                                            <option value="Banani">Banani</option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <div class="col-12 col-md-4 mb-3">
                                    <label for="street" class="form-label">Street</label>
                                    <input type="text" class="form-control" id="street" name="street" 
                                           placeholder="Street name">
                                </div>
                                <div class="col-12 col-md-4 mb-3">
                                    <label for="road" class="form-label">Road</label>
                                    <input type="text" class="form-control" id="road" name="road" 
                                           placeholder="Road number">
                                </div>
                                <div class="col-12 mb-3">
                                    <label for="address1" class="form-label">Address Line 1 *</label>
                                    <input type="text" class="form-control" id="address1" name="address1" 
                                           placeholder="House number or any other address" required>
                                </div>
                                <div class="col-12 mb-3">
                                    <label for="address2" class="form-label">Address Line 2</label>
                                    <input type="text" class="form-control" id="address2" name="address2" 
                                           placeholder="Apartment, suite, or unit (optional)">
                                </div>
                            </div>
                        </div>

                        <!-- Delivery Method -->
                        <div class="checkout-section mb-5">
                            <h3 class="section-title mb-4">
                                <i class="fas fa-truck me-2"></i>Delivery Method
                            </h3>
                            <div class="delivery-options">
                                <div class="delivery-option">
                                    <div class="delivery-option-content">
                                        <div class="delivery-info">
                                            <h6 class="mb-1">Standard Delivery</h6>
                                            <small class="text-muted">30-45 minutes</small>
                                        </div>
                                        <div class="delivery-price">
                                            <span class="price">৳ 50</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Method -->
                        <div class="checkout-section mb-5">
                            <h3 class="section-title mb-4">
                                <i class="fas fa-credit-card me-2"></i>Payment Method
                            </h3>
                            <div class="payment-method-container">
                                <label for="payment_method" class="form-label">Choose method</label>
                                <div class="custom-select-wrapper">
                                    <select class="form-select payment-method-select" id="payment_method" name="payment_method" required>
                                        <option value="cod" selected>Cash on Delivery</option>
                                        <option value="card">Credit/Debit Card</option>
                                    </select>
                                    <div class="select-icon">
                                        <i class="fas fa-money-bill-wave" id="payment-icon"></i>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <!-- Coupon Code -->
                        <div class="checkout-section mb-5">
                            <h3 class="section-title mb-4">
                                <i class="fas fa-tag me-2"></i>Coupon Code
                            </h3>
                            <div class="row">
                                <div class="col-12 col-md-8 mb-2 mb-md-0">
                                    <input type="text" class="form-control" id="coupon_code" name="coupon_code" 
                                           placeholder="Enter coupon code">
                                </div>
                                <div class="col-12 col-md-4">
                                    <button type="button" class="btn btn-outline-primary w-100" id="applyCoupon">
                                        Apply Coupon
                                    </button>
                                </div>
                            </div>
                            <div id="couponMessage" class="mt-2"></div>
                        </div>

                        <!-- Order Notes -->
                        <div class="checkout-section mb-5">
                            <h3 class="section-title mb-4">
                                <i class="fas fa-sticky-note me-2"></i>Order Notes
                            </h3>
                            <textarea class="form-control" id="order_notes" name="order_notes" rows="3" 
                                      placeholder="Special delivery instructions (optional)"></textarea>
                        </div>

                        <!-- Trust Section -->
                        <div class="trust-section mb-4">
                            <div class="d-flex align-items-center justify-content-center text-muted">
                                <i class="fas fa-lock me-2"></i>
                                <span>Secure checkout protected by SSL encryption</span>
                            </div>
                        </div>

                        <!-- Place Order Button -->
                        <div class="place-order-section">
                            <button type="submit" class="btn btn-warning btn-lg w-100 py-3 py-md-3" id="placeOrderBtn">
                                <i class="fas fa-shopping-bag me-2"></i>
                                <span class="d-none d-sm-inline">Place Order - </span><span class="d-sm-none">Order - </span><span id="finalTotal">৳ 0.00</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Right Column - Order Summary -->
                <div class="col-12 col-lg-5 order-1 order-lg-2">
                    <div class="order-summary">
                        <h3 class="summary-title mb-4">
                            <i class="fas fa-shopping-cart me-2"></i>Order Summary
                        </h3>
                        
                        <!-- Cart Items -->
                        <div class="cart-items mb-4" id="cartItems">
                            <!-- Cart items will be loaded dynamically via JavaScript -->
                        </div>

                        <!-- Order Totals -->
                        <div class="order-totals">
                            <div class="total-row">
                                <span>Subtotal</span>
                                <span id="subtotal">৳ 0.00</span>
                            </div>
                            <div class="total-row">
                                <span>Tax (5%)</span>
                                <span id="tax">৳ 0.00</span>
                            </div>
                            <div class="total-row">
                                <span>Delivery Fee</span>
                                <span id="deliveryFee">৳ 50.00</span>
                            </div>
                            <div class="total-row coupon-row" id="couponRow" style="display: none;">
                                <span>Coupon Discount</span>
                                <div class="d-flex align-items-center">
                                    <span class="text-success" id="couponDiscount">-৳ 0.00</span>
                                    <button type="button" class="btn btn-sm btn-outline-danger ms-2" id="removeCouponBtn" style="display: none;">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            <hr>
                            <div class="total-row final-total">
                                <span>Total</span>
                                <span id="totalAmount">৳ 50.00</span>
                            </div>
                        </div>

                        <!-- Edit Cart Link -->
                        <div class="edit-cart-section mt-4">
                            <button type="button" class="btn btn-outline-secondary w-100" onclick="refreshCartAndShowModal()">
                                <i class="fas fa-edit me-2"></i>Edit Cart
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include '../components/footer.php'; ?>
