<?php
session_start();

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
    
    if (!isLoggedIn()) {
        header('Location: signin.php');
        exit();
    }
    
    $user = getCurrentUser();
    $userProfile = getUserProfile($user['id']);
    
} catch(PDOException $e) {
    error_log("Database connection error: " . $e->getMessage());
    header('Location: card-payment.php?error=database_error');
    exit();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: card-payment.php');
    exit();
}

// Validate required fields
$required_fields = ['card_number', 'expiry_date', 'cvv', 'cardholder_name'];
$errors = [];

foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
    }
}

if (!empty($errors)) {
    $_SESSION['card_payment_errors'] = $errors;
    header('Location: card-payment.php');
    exit();
}

// Validate card number (basic validation)
$card_number = preg_replace('/\D/', '', $_POST['card_number']);
if (strlen($card_number) < 13 || strlen($card_number) > 19) {
    $_SESSION['card_payment_errors'] = ['Please enter a valid card number'];
    header('Location: card-payment.php');
    exit();
}

// Validate expiry date
$expiry_date = $_POST['expiry_date'];
if (!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $expiry_date)) {
    $_SESSION['card_payment_errors'] = ['Please enter a valid expiry date (MM/YY)'];
    header('Location: card-payment.php');
    exit();
}

// Check if card is expired
$expiry_parts = explode('/', $expiry_date);
$expiry_month = (int)$expiry_parts[0];
$expiry_year = 2000 + (int)$expiry_parts[1];
$current_year = (int)date('Y');
$current_month = (int)date('n');

if ($expiry_year < $current_year || ($expiry_year == $current_year && $expiry_month < $current_month)) {
    $_SESSION['card_payment_errors'] = ['Card has expired'];
    header('Location: card-payment.php');
    exit();
}

// Validate CVV
$cvv = $_POST['cvv'];
if (!preg_match('/^\d{3,4}$/', $cvv)) {
    $_SESSION['card_payment_errors'] = ['Please enter a valid CVV (3 or 4 digits)'];
    header('Location: card-payment.php');
    exit();
}

// Get pending order from session
if (!isset($_SESSION['pending_order'])) {
    $_SESSION['card_payment_errors'] = ['No pending order found. Please start checkout again.'];
    header('Location: checkout.php');
    exit();
}

$order_data = $_SESSION['pending_order'];

try {
    // Simulate payment processing (in real app, integrate with payment gateway)
    // For demo purposes, we'll assume payment is successful

    // Insert order now that payment succeeded
    $order_data['payment_status'] = 'paid';
    $sql = "INSERT INTO orders (
        order_id, user_id, customer_name, customer_email, customer_phone,
        delivery_area, delivery_street, delivery_road, delivery_address1, delivery_address2,
        order_items, subtotal, tax_amount, delivery_fee, coupon_code, coupon_discount,
        total, payment_method, payment_status, status, order_notes
    ) VALUES (
        :order_id, :user_id, :customer_name, :customer_email, :customer_phone,
        :delivery_area, :delivery_street, :delivery_road, :delivery_address1, :delivery_address2,
        :order_items, :subtotal, :tax_amount, :delivery_fee, :coupon_code, :coupon_discount,
        :total, :payment_method, :payment_status, :status, :order_notes
    )";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($order_data);
    $inserted_id = $pdo->lastInsertId();
    
    // Store order success data in session (use numeric id and human-friendly order_id)
    $_SESSION['order_success'] = [
        'id' => $inserted_id,                    // numeric primary key in orders table
        'order_id' => $order_data['order_id'],   // public order number
        'total' => $order_data['total'],
        'discount_total' => $order_data['discount_total'] ?? $order_data['total'],
        'payment_method' => 'card'
    ];
    
    // Clear pending order and any errors
    unset($_SESSION['pending_order']);
    unset($_SESSION['card_payment_errors']);
    
    // Clear client-side cart so the badge resets to 0 on confirmation page
    // The confirmation page will also clear localStorage via JS, but we add a server-side hint
    $_SESSION['clear_cart_client'] = true;
    
    // Redirect to confirmation page
    header('Location: order-confirmation.php');
    
} catch(PDOException $e) {
    error_log("Card payment processing error: " . $e->getMessage());
    $_SESSION['card_payment_errors'] = ['Failed to process payment. Please try again.'];
    header('Location: card-payment.php');
    exit();
}
?>
