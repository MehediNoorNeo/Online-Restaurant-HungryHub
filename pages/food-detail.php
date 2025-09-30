<?php
require_once '../auth/auth_functions.php';

// Get food item ID from URL
$food_id = $_GET['id'] ?? null;

if (!$food_id) {
    header('Location: index.php');
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
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Get food item details
$stmt = $pdo->prepare("SELECT * FROM food_items WHERE id = ?");
$stmt->execute([$food_id]);
$food_item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$food_item) {
    header('Location: index.php');
    exit();
}

// Get reviews for this food item
$stmt = $pdo->prepare("
    SELECT r.*, u.name as user_name 
    FROM reviews r 
    LEFT JOIN users u ON r.user_id = u.id 
    WHERE r.food_id = ? 
    ORDER BY r.created_at DESC
");
$stmt->execute([$food_id]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate average rating
$avg_rating = 0;
if (!empty($reviews)) {
    $total_rating = array_sum(array_column($reviews, 'rating'));
    $avg_rating = $total_rating / count($reviews);
}

// Handle review submission
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (isLoggedIn()) {
        $rating = (int)($_POST['rating'] ?? 0);
        $review_text = trim($_POST['review_text'] ?? '');
        $user_id = getCurrentUser()['id'];
        
        if ($rating >= 1 && $rating <= 5 && !empty($review_text)) {
            try {
                // Check if user already reviewed this item
                $stmt = $pdo->prepare("SELECT id FROM reviews WHERE food_id = ? AND user_id = ?");
                $stmt->execute([$food_id, $user_id]);
                
                if ($stmt->fetch()) {
                    // Update existing review
                    $stmt = $pdo->prepare("UPDATE reviews SET rating = ?, review_text = ?, updated_at = CURRENT_TIMESTAMP WHERE food_id = ? AND user_id = ?");
                    $stmt->execute([$rating, $review_text, $food_id, $user_id]);
                    $message = 'Review updated successfully!';
                } else {
                    // Insert new review
                    $stmt = $pdo->prepare("INSERT INTO reviews (food_id, user_id, rating, review_text) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$food_id, $user_id, $rating, $review_text]);
                    $message = 'Review submitted successfully!';
                }
                $message_type = 'success';
                
                // Refresh page to show updated reviews
                header("Location: food-detail.php?id=$food_id");
                exit();
                
            } catch(PDOException $e) {
                $message = 'Error submitting review: ' . $e->getMessage();
                $message_type = 'error';
            }
        } else {
            $message = 'Please provide a valid rating (1-5) and review text.';
            $message_type = 'error';
        }
    } else {
        $message = 'Please log in to submit a review.';
        $message_type = 'error';
    }
}

// Set page title
$page_title = $food_item['name'] . ' - Food Details';
$include_food_cards_css = true;
$include_food_detail_css = true;
?>

<?php include '../components/header.php'; ?>

<div class="food-detail-container">
    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <div class="food-detail-card">
                    <div class="food-image-section">
                        <img src="../<?php echo htmlspecialchars($food_item['image']); ?>" 
                             alt="<?php echo htmlspecialchars($food_item['name']); ?>" 
                             class="food-image">
                    </div>
                    
                    <div class="food-info">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="food-category"><?php echo htmlspecialchars($food_item['category']); ?></div>
                            <div class="card-quantity-controls">
                                <button class="btn btn-sm btn-outline-secondary" onclick="decreaseQuantity('<?php echo htmlspecialchars($food_item['name']); ?>')" id="remove-<?php echo htmlspecialchars(str_replace(' ', '-', $food_item['name'])); ?>">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <span class="card-quantity-display mx-2" id="qty-<?php echo htmlspecialchars(str_replace(' ', '-', $food_item['name'])); ?>">0</span>
                                <button class="btn btn-sm btn-outline-primary" onclick="increaseQuantity('<?php echo htmlspecialchars($food_item['name']); ?>')">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        <h1 class="food-title"><?php echo htmlspecialchars($food_item['name']); ?></h1>
                        
                        <div class="rating-section">
                            <div class="rating-stars">
                                <?php
                                $full_stars = floor($avg_rating);
                                $has_half = ($avg_rating - $full_stars) >= 0.5;
                                
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= $full_stars) {
                                        echo '<i class="fas fa-star"></i>';
                                    } elseif ($i == $full_stars + 1 && $has_half) {
                                        echo '<i class="fas fa-star-half-alt"></i>';
                                    } else {
                                        echo '<i class="far fa-star"></i>';
                                    }
                                }
                                ?>
                            </div>
                            <span class="rating-text">
                                <?php echo number_format($avg_rating, 1); ?> 
                                (<?php echo count($reviews); ?> reviews)
                            </span>
                        </div>
                        
                        <div class="food-price">
                            <span class="card-currency-symbol">৳</span><?php echo number_format($food_item['price'], 2); ?>
                        </div>
                        
                        <p class="food-description">
                            <?php echo htmlspecialchars($food_item['description']); ?>
                        </p>
                        
                        <button class="btn btn-add-to-cart w-100" onclick="addToCart('<?php echo htmlspecialchars($food_item['name']); ?>')">
                            <i class="fas fa-shopping-cart me-2"></i>
                            Add to Cart
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="reviews-section">
                    <h3 class="mb-4">Reviews & Ratings</h3>
                    
                    <?php if (isLoggedIn()): ?>
                    <div class="review-form">
                        <h5>Write a Review</h5>
                        <form method="POST">
                            <div class="star-rating" id="star-rating">
                                <span class="star" data-rating="1"><i class="far fa-star"></i></span>
                                <span class="star" data-rating="2"><i class="far fa-star"></i></span>
                                <span class="star" data-rating="3"><i class="far fa-star"></i></span>
                                <span class="star" data-rating="4"><i class="far fa-star"></i></span>
                                <span class="star" data-rating="5"><i class="far fa-star"></i></span>
                            </div>
                            <input type="hidden" name="rating" id="rating-input" value="0">
                            <div class="mb-3">
                                <textarea class="form-control" name="review_text" rows="4" 
                                          placeholder="Write your review here..." required></textarea>
                            </div>
                            <button type="submit" name="submit_review" class="btn btn-primary">
                                Submit Review
                            </button>
                        </form>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info">
                        <a href="signin.php" class="alert-link">Sign in</a> to write a review.
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'danger'; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="reviews-list">
                        <?php if (empty($reviews)): ?>
                        <p class="text-muted">No reviews yet. Be the first to review this item!</p>
                        <?php else: ?>
                        <?php foreach ($reviews as $review): ?>
                        <div class="review-item">
                            <div class="review-header">
                                <span class="review-user"><?php echo htmlspecialchars($review['user_name']); ?></span>
                                <span class="review-date"><?php echo date('M j, Y', strtotime($review['created_at'])); ?></span>
                            </div>
                            <div class="review-rating">
                                <?php
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= $review['rating']) {
                                        echo '<i class="fas fa-star"></i>';
                                    } else {
                                        echo '<i class="far fa-star"></i>';
                                    }
                                }
                                ?>
                            </div>
                            <div class="review-text">
                                <?php echo htmlspecialchars($review['review_text']); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Star rating functionality
document.addEventListener('DOMContentLoaded', function() {
    const stars = document.querySelectorAll('.star');
    const ratingInput = document.getElementById('rating-input');
    
    stars.forEach(star => {
        star.addEventListener('click', function() {
            const rating = parseInt(this.dataset.rating);
            ratingInput.value = rating;
            
            stars.forEach((s, index) => {
                if (index < rating) {
                    s.classList.add('active');
                    s.querySelector('i').className = 'fas fa-star';
                } else {
                    s.classList.remove('active');
                    s.querySelector('i').className = 'far fa-star';
                }
            });
        });
        
        star.addEventListener('mouseenter', function() {
            const rating = parseInt(this.dataset.rating);
            stars.forEach((s, index) => {
                if (index < rating) {
                    s.querySelector('i').className = 'fas fa-star';
                } else {
                    s.querySelector('i').className = 'far fa-star';
                }
            });
        });
    });
    
    document.getElementById('star-rating').addEventListener('mouseleave', function() {
        const currentRating = parseInt(ratingInput.value);
        stars.forEach((s, index) => {
            if (index < currentRating) {
                s.querySelector('i').className = 'fas fa-star';
            } else {
                s.querySelector('i').className = 'far fa-star';
            }
        });
    });
});
</script>

<!-- Include main JavaScript file for cart functionality -->
<script src="../javaScript/script.js"></script>

<?php include '../components/footer.php'; ?>
