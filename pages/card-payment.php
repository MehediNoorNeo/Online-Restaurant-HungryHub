<?php
// Page configuration
$page_title = 'Card Payment - HungryHub';
$include_checkout_css = true;
$include_checkout_js = true;

// Database configuration
$host = 'localhost';
$dbname = 'hungry_hub';
$username = 'root';
$password = '';

// Get user data if logged in
$user = null;
$userProfile = null;

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
        $_SESSION['checkout_message'] = 'Please sign in to proceed with payment.';
        header('Location: signin.php');
        exit();
    }
    
} catch(PDOException $e) {
    // Handle database connection error
    $user = null;
    $userProfile = null;
}

include '../components/header.php';
?>

<main class="card-payment-page">
    <!-- Progress Indicator -->
    <section class="progress-section py-4 bg-light">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="progress-indicator d-flex align-items-center justify-content-between">
                        <div class="step completed">
                            <div class="step-circle">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <span class="step-label">Cart</span>
                        </div>
                        <div class="step-line completed"></div>
                        <div class="step completed">
                            <div class="step-circle">
                                <i class="fas fa-info-circle"></i>
                            </div>
                            <span class="step-label">Information</span>
                        </div>
                        <div class="step-line completed"></div>
                        <div class="step active">
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

    <!-- Card Payment Form -->
    <section class="card-payment-section py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card-payment-container">
                        <div class="payment-header">
                            <h2 class="payment-title">
                                <i class="fas fa-credit-card me-3"></i>Card Payment
                            </h2>
                            <p class="payment-subtitle">Enter your card details to complete the payment</p>
                        </div>

                        <form id="cardPaymentForm" class="card-payment-form" method="POST" action="process-card-payment.php">
                            <!-- Error Messages -->
                            <?php if (isset($_SESSION['card_payment_errors'])): ?>
                                <div class="alert alert-danger mb-4">
                                    <h6><i class="fas fa-exclamation-triangle me-2"></i>Please fix the following errors:</h6>
                                    <ul class="mb-0">
                                        <?php foreach ($_SESSION['card_payment_errors'] as $error): ?>
                                            <li><?php echo htmlspecialchars($error); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                                <?php unset($_SESSION['card_payment_errors']); ?>
                            <?php endif; ?>

                            <!-- Card Information -->
                            <div class="card-info-section">
                                <h3 class="section-title mb-4">
                                    <i class="fas fa-credit-card me-2"></i>Card Information
                                </h3>
                                
                                <div class="row">
                                    <div class="col-12 mb-4">
                                        <label for="card_number" class="form-label">Card Number *</label>
                                        <div class="card-input-wrapper">
                                            <input type="text" class="form-control card-input" id="card_number" name="card_number" 
                                                   placeholder="1234 5678 9012 3456" maxlength="19" required>
                                            <div class="card-type-icon" id="cardTypeIcon">
                                                <i class="fas fa-credit-card"></i>
                                            </div>
                                        </div>
                                        <div class="form-text">Enter your 16-digit card number</div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-4">
                                        <label for="expiry_date" class="form-label">Expiry Date *</label>
                                        <input type="text" class="form-control card-input" id="expiry_date" name="expiry_date" 
                                               placeholder="MM/YY" maxlength="5" required>
                                        <div class="form-text">Format: MM/YY</div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-4">
                                        <label for="cvv" class="form-label">CVV *</label>
                                        <div class="cvv-input-wrapper">
                                            <input type="text" class="form-control card-input" id="cvv" name="cvv" 
                                                   placeholder="123" maxlength="4" required>
                                            <div class="cvv-info" data-bs-toggle="tooltip" title="3 or 4 digit security code">
                                                <i class="fas fa-question-circle"></i>
                                            </div>
                                        </div>
                                        <div class="form-text">3 or 4 digit security code</div>
                                    </div>
                                    
                                    <div class="col-12 mb-4">
                                        <label for="cardholder_name" class="form-label">Cardholder Name *</label>
                                        <input type="text" class="form-control card-input" id="cardholder_name" name="cardholder_name" 
                                               placeholder="John Doe" required>
                                        <div class="form-text">Name as it appears on the card</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Billing Address -->
                            <div class="billing-section">
                                <h3 class="section-title mb-4">
                                    <i class="fas fa-map-marker-alt me-2"></i>Billing Address
                                </h3>
                                
                                <div class="row">
                                    <div class="col-12 mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="sameAsDelivery" checked>
                                            <label class="form-check-label" for="sameAsDelivery">
                                                Same as delivery address
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div id="billingAddressFields" style="display: none;">
                                        <div class="col-12 mb-3">
                                            <label for="billing_address" class="form-label">Billing Address *</label>
                                            <input type="text" class="form-control card-input" id="billing_address" name="billing_address" 
                                                   placeholder="Enter billing address">
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="billing_city" class="form-label">City *</label>
                                            <input type="text" class="form-control card-input" id="billing_city" name="billing_city" 
                                                   placeholder="Enter city">
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="billing_postal" class="form-label">Postal Code *</label>
                                            <input type="text" class="form-control card-input" id="billing_postal" name="billing_postal" 
                                                   placeholder="Enter postal code">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Security Notice -->
                            <div class="security-notice">
                                <div class="alert alert-info">
                                    <i class="fas fa-shield-alt me-2"></i>
                                    <strong>Secure Payment:</strong> Your card information is encrypted and secure. We do not store your card details.
                                </div>
                            </div>

                            <!-- Payment Buttons -->
                            <div class="payment-actions">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <button type="button" class="btn btn-outline-secondary w-100" onclick="goBackToCheckout()">
                                            <i class="fas fa-arrow-left me-2"></i>Back to Checkout
                                        </button>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <button type="submit" class="btn btn-warning w-100" id="processPaymentBtn">
                                            <i class="fas fa-lock me-2"></i>
                                            Process Payment - <span id="paymentTotal">৳ 0.00</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include '../components/footer.php'; ?>

<!-- Card Payment JavaScript -->
<script src="../javaScript/card-payment.js?v=<?php echo time(); ?>"></script>
