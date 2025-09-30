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
    header('Location: checkout.php?error=database_error');
    exit();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: checkout.php');
    exit();
}

// Validate required fields
$required_fields = ['full_name', 'email', 'phone', 'area', 'address1', 'payment_method'];
$errors = [];

foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
    }
}

if (!empty($errors)) {
    $_SESSION['checkout_errors'] = $errors;
    header('Location: checkout.php');
    exit();
}

// Validate email format
if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    $_SESSION['checkout_errors'] = ['Please enter a valid email address'];
    header('Location: checkout.php');
    exit();
}

// Validate phone format (Bangladesh)
$phone = preg_replace('/\D/', '', $_POST['phone']);
if (!preg_match('/^(\+?88)?01[3-9]\d{8}$/', $phone)) {
    $_SESSION['checkout_errors'] = ['Please enter a valid Bangladesh phone number'];
    header('Location: checkout.php');
    exit();
}

// Get cart data from session or localStorage (passed via form)
$cart_data = $_POST['cart_data'] ?? '';
if (empty($cart_data)) {
    $_SESSION['checkout_errors'] = ['Cart is empty. Please add items to your cart.'];
    header('Location: checkout.php');
    exit();
}

$cart_items = json_decode($cart_data, true);
if (json_last_error() !== JSON_ERROR_NONE || empty($cart_items)) {
    $_SESSION['checkout_errors'] = ['Invalid cart data. Please try again.'];
    header('Location: checkout.php');
    exit();
}

// If a coupon code is typed but not applied on client, block submission
$typed_coupon = isset($_POST['coupon_code']) ? trim($_POST['coupon_code']) : '';
$coupon_applied_flag = isset($_POST['coupon_applied']) ? $_POST['coupon_applied'] : '0';
if (!empty($typed_coupon) && $coupon_applied_flag !== '1') {
    $_SESSION['checkout_errors'] = ['Please click "Apply Coupon" to validate your coupon before placing the order.'];
    header('Location: checkout.php');
    exit();
}

try {
    // Get food items data to calculate prices
    $stmt = $pdo->query("SELECT name, price, category FROM food_items");
    $food_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $food_items_map = [];
    foreach ($food_items as $item) {
        $food_items_map[$item['name']] = $item;
    }
    
    // Calculate order totals
    $subtotal = 0;
    $order_items = [];
    
    foreach ($cart_items as $item_name => $quantity) {
        if (isset($food_items_map[$item_name])) {
            $food_item = $food_items_map[$item_name];
            $unit_price = $food_item['price'];
            $total_price = $unit_price * $quantity;
            $subtotal += $total_price;
            
            $order_items[] = [
                'item_name' => $item_name,
                'category' => $food_item['category'],
                'quantity' => (int)$quantity,
                'unit_price' => (float)$unit_price,
                'total_price' => (float)$total_price
            ];
        }
    }
    
    if (empty($order_items)) {
        $_SESSION['checkout_errors'] = ['No valid items found in cart.'];
        header('Location: checkout.php');
        exit();
    }
    
    // Calculate tax (5%)
    $tax_rate = 0.05;
    $tax_amount = $subtotal * $tax_rate;
    
    // Delivery fee
    $delivery_fee = 50.00;
    
    // Coupon discount
    $coupon_code = $_POST['coupon_code'] ?? '';
    $coupon_discount = 0.00;
    
    // Debug: Log received POST data
    error_log('POST data received: ' . print_r($_POST, true));
    error_log('Coupon code from POST: ' . $coupon_code);
    
    if (!empty($coupon_code)) {
        // Validate coupon
        $stmt = $pdo->prepare("SELECT * FROM coupons WHERE coupon_code = ?");
        $stmt->execute([strtoupper($coupon_code)]);
        $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($coupon) {
            $coupon_discount = ($subtotal * $coupon['percentage']) / 100;
        }
    }
    
    // Calculate total
    $total = $subtotal + $tax_amount + $delivery_fee - $coupon_discount;
    
    // Generate public order number (human-friendly)
    $order_number = 'HH' . date('Ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    // Prepare order data (not yet inserted for card payments)
    $order_data = [
        'order_id' => $order_number,
        'user_id' => $user['id'],
        'customer_name' => $_POST['full_name'],
        'customer_email' => $_POST['email'],
        'customer_phone' => $phone,
        'delivery_area' => $_POST['area'],
        'delivery_street' => $_POST['street'] ?? null,
        'delivery_road' => $_POST['road'] ?? null,
        'delivery_address1' => $_POST['address1'],
        'delivery_address2' => $_POST['address2'] ?? null,
        'order_items' => json_encode($order_items),
        'subtotal' => $subtotal,
        'tax_amount' => $tax_amount,
        'delivery_fee' => $delivery_fee,
        'coupon_code' => !empty($coupon_code) ? strtoupper($coupon_code) : null,
        'coupon_discount' => $coupon_discount,
        'total' => $total,
        'payment_method' => $_POST['payment_method'],
        // For card payments, we will insert after successful payment. Keep 'pending' here.
        'payment_status' => 'pending',
        'status' => 'pending',
        'order_notes' => $_POST['order_notes'] ?? null
    ];
    
    // For COD, insert immediately; for card, defer insert until payment success
    if ($_POST['payment_method'] === 'card') {
        // Store order data for card payment processing
        $_SESSION['pending_order'] = $order_data;
        unset($_SESSION['checkout_errors']);
        header('Location: card-payment.php');
    } else {
        // Insert order into database now for COD
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

        // Store order success data in session (used by order-confirmation)
        $_SESSION['order_success'] = [
            'id' => $inserted_id,
            'order_id' => $order_number,
            'total' => $total,
            'discount_total' => $total,
            'payment_method' => $_POST['payment_method']
        ];

        // Clear any previous errors
        unset($_SESSION['checkout_errors']);

        // Clear client cart on next page so badge resets to 0
        $_SESSION['clear_cart_client'] = true;
        header('Location: order-confirmation.php');
    }
    
} catch(PDOException $e) {
    error_log("Order processing error: " . $e->getMessage());
    $_SESSION['checkout_errors'] = ['Failed to process order. Please try again.'];
    header('Location: checkout.php');
    exit();
}
?>
