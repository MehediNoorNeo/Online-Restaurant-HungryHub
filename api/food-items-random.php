<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Database configuration
$host = 'localhost';
$dbname = 'hungry_hub';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . $e->getMessage()]);
    exit();
}

// Get parameters
$limit = $_GET['limit'] ?? 12;
$random = $_GET['random'] ?? false;
$category = $_GET['category'] ?? '';

try {
    // Build query based on parameters
    if ($random) {
        $query = "SELECT id, category, name, price, image, description FROM food_items ORDER BY RAND() LIMIT " . (int)$limit;
        $stmt = $pdo->prepare($query);
        $stmt->execute();
    } else if ($category) {
        $query = "SELECT id, category, name, price, image, description FROM food_items WHERE category = ? ORDER BY name LIMIT " . (int)$limit;
        $stmt = $pdo->prepare($query);
        $stmt->execute([$category]);
    } else {
        $query = "SELECT id, category, name, price, image, description FROM food_items ORDER BY category, name LIMIT " . (int)$limit;
        $stmt = $pdo->prepare($query);
        $stmt->execute();
    }
    $food_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the response
    $formatted_items = [];
    foreach ($food_items as $item) {
        $formatted_items[] = [
            'id' => $item['id'],
            'category' => $item['category'],
            'name' => $item['name'],
            'price' => (float)$item['price'],
            'imagePath' => $item['image'],
            'description' => $item['description']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $formatted_items,
        'count' => count($formatted_items)
    ]);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database query failed: ' . $e->getMessage()
    ]);
}
?>
