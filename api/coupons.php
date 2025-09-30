<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database configuration
$host = 'localhost';
$dbname = 'hungry_hub';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Fetch all coupons
        $stmt = $pdo->query("SELECT coupon_code, percentage FROM coupons ORDER BY id DESC");
        $coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $coupons
        ]);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validate specific coupon
        $input = json_decode(file_get_contents('php://input'), true);
        $couponCode = strtoupper(trim($input['coupon_code'] ?? ''));
        $userId = isset($input['user_id']) ? (int)$input['user_id'] : 0;
        
        if (empty($couponCode)) {
            echo json_encode([
                'success' => false,
                'message' => 'Coupon code is required'
            ]);
            exit();
        }
        
        $stmt = $pdo->prepare("SELECT coupon_code, percentage FROM coupons WHERE coupon_code = ?");
        $stmt->execute([$couponCode]);
        $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($coupon) {
            // If user id provided, ensure this user hasn't used this coupon before
            if ($userId > 0) {
                $usedStmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND coupon_code = ?");
                $usedStmt->execute([$userId, $couponCode]);
                $alreadyUsed = (int)$usedStmt->fetchColumn() > 0;
                if ($alreadyUsed) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'You have already used this coupon.'
                    ]);
                    exit();
                }
            }
            echo json_encode([
                'success' => true,
                'data' => $coupon
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid coupon code'
            ]);
        }
    }
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
