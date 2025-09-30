<?php
header('Content-Type: application/json');

// Database configuration
$host = 'localhost';
$dbname = 'hungry_hub';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit();
}

// Get food IDs from request
$food_ids = $_GET['ids'] ?? '';
$food_ids_array = explode(',', $food_ids);

if (empty($food_ids_array) || $food_ids_array[0] === '') {
    echo json_encode(['error' => 'No food IDs provided']);
    exit();
}

// Sanitize food IDs
$food_ids_array = array_map('intval', $food_ids_array);
$placeholders = str_repeat('?,', count($food_ids_array) - 1) . '?';

// Get reviews for all food items
$query = "
    SELECT 
        r.food_id,
        AVG(r.rating) as avg_rating,
        COUNT(r.id) as review_count
    FROM reviews r 
    WHERE r.food_id IN ($placeholders)
    GROUP BY r.food_id
";

$stmt = $pdo->prepare($query);
$stmt->execute($food_ids_array);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Create a lookup array
$review_data = [];
foreach ($reviews as $review) {
    $review_data[$review['food_id']] = [
        'avg_rating' => round((float)$review['avg_rating'], 1),
        'review_count' => (int)$review['review_count']
    ];
}

// Prepare response with all requested food IDs
$response = [];
foreach ($food_ids_array as $food_id) {
    if (isset($review_data[$food_id])) {
        $response[$food_id] = $review_data[$food_id];
    } else {
        $response[$food_id] = [
            'avg_rating' => 0,
            'review_count' => 0
        ];
    }
}

echo json_encode($response);
?>
