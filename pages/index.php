<?php
// Page configuration
$page_title = 'HungryHub - Your Cravings, Delivered';
$page_description = 'Explore a world of flavors, including salads, rolls, and pure vegetarian options — get your favorite food delivered fast.';
$page_keywords = 'food delivery, online ordering, restaurant, hungryhub, salads, rolls, desserts, pasta, noodles';

// Database configuration
$host = 'localhost';
$dbname = 'hungry_hub';
$username = 'root';
$password = '';

// Get dynamic content from database
$featured_categories = [];
$total_food_items = 0;
$total_categories = 0;
// Latest reviews for testimonials
$latest_reviews = [];

try {
  $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  // Get featured categories (top 3 categories with most items)
  $stmt = $pdo->query("
        SELECT category, COUNT(*) as item_count 
        FROM food_items 
        GROUP BY category 
        ORDER BY item_count DESC 
        LIMIT 3
    ");
  $featured_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // Get total counts
  $stmt = $pdo->query("SELECT COUNT(*) as total FROM food_items");
  $total_food_items = $stmt->fetch()['total'];

  $stmt = $pdo->query("SELECT COUNT(DISTINCT category) as total FROM food_items");
  $total_categories = $stmt->fetch()['total'];

  // Fetch latest reviews with user names for homepage testimonials
  $stmt = $pdo->query("\n        SELECT r.review_text, r.rating, r.created_at,\n               COALESCE(u.name, 'Anonymous') AS user_name,\n               fi.name AS food_name, fi.image AS food_image\n        FROM reviews r\n        LEFT JOIN users u ON r.user_id = u.id\n        LEFT JOIN food_items fi ON r.food_id = fi.id\n        WHERE r.review_text IS NOT NULL AND TRIM(r.review_text) <> ''\n        ORDER BY r.created_at DESC\n        LIMIT 10\n    ");
  $latest_reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  // Fallback values if database connection fails
  $featured_categories = [
    ['category' => 'Salad', 'item_count' => 8],
    ['category' => 'Desserts', 'item_count' => 6],
    ['category' => 'Pasta', 'item_count' => 5]
  ];
  $total_food_items = 50;
  $total_categories = 8;
  $latest_reviews = [];
}

include '../components/index_header.php';
?>

<main>
  <!-- HERO -->
  <section class="hero position-relative overflow-hidden">
    <div class="hero-bg position-absolute w-100 h-100">
      <img src="../assets/hero_bg_image.jpg"
        alt="Delicious food selection"
        class="w-100 h-100 object-cover">
      <div class="hero-overlay position-absolute top-0 start-0 w-100 h-100"></div>
    </div>

    <div class="container-fluid position-relative">
      <div class="row min-vh-75 align-items-center">
        <div class="col-lg-8 col-xl-7">
          <div class="hero-content text-white">
            <h1 class="display-4 fw-bold mb-4 animate-fade-in">
              Your Cravings, <span class="text-warning">Delivered.</span>
            </h1>
            <p class="lead fs-4 mb-5 animate-fade-in-delay">
              Explore a world of flavors with <?php echo $total_food_items; ?>+ delicious dishes across <?php echo $total_categories; ?> categories — get your favorite food delivered fast.
            </p>

            <div class="d-flex flex-wrap gap-3 mb-4 animate-fade-in-delay-2">
              <a class="btn btn-warning btn-lg px-4 py-3 fw-semibold" href="#menu">
                <i class="fas fa-shopping-cart me-2"></i>Order Now
              </a>
              <a class="btn btn-outline-light btn-lg px-4 py-3 fw-semibold" href="#menu">
                <i class="fas fa-utensils me-2"></i>Browse Menu
              </a>
            </div>

            <div class="row g-3 text-center animate-fade-in-delay-3">
              <div class="col-4">
                <div class="feature-badge">
                  <i class="fas fa-leaf text-warning fs-3 mb-2"></i>
                  <div class="small fw-semibold">Fresh Ingredients</div>
                </div>
              </div>
              <div class="col-4">
                <div class="feature-badge">
                  <i class="fas fa-shield-alt text-warning fs-3 mb-2"></i>
                  <div class="small fw-semibold">Secure Ordering</div>
                </div>
              </div>
              <div class="col-4">
                <div class="feature-badge">
                  <i class="fas fa-shipping-fast text-warning fs-3 mb-2"></i>
                  <div class="small fw-semibold">Fast Delivery</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- EXPLORE OUR MENU -->
  <section id="menu" class="py-5 bg-light">
    <div class="container-fluid">
      <div class="text-center mb-5">
        <h2 class="display-5 fw-bold text-dark mb-3">Explore Our Menu</h2>
        <p class="lead text-muted">Tap a category to browse delicious options</p>
      </div>

      <?php include '../components/menu_explorer.php'; ?>
    </div>
  </section>

  <!-- FEATURED FOOD ITEMS -->
  <section id="featured-food" class="py-5 bg-light">
    <div class="container-fluid">
      <div class="text-center mb-5">
        <h2 class="display-5 fw-bold text-dark mb-3">Featured Food Items</h2>
        <p class="lead text-muted">Discover our most popular dishes from all categories</p>
      </div>
      <div class="row g-4" id="featured-food-items">
        <!-- Featured food items will be dynamically loaded here -->
      </div>
    </div>
  </section>

  <!-- DYNAMIC CONTENT LOADING SCRIPT -->
  <script>
    // Load featured food items dynamically
    document.addEventListener('DOMContentLoaded', function() {
      loadFeaturedFoodItems();
    });

    async function loadFeaturedFoodItems() {
      const container = document.getElementById('featured-food-items');
      if (!container) return;

      // Show loading spinner
      container.innerHTML = '<div class="col-12 text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';

      try {
        // Fetch random food items from API
        const response = await fetch('../api/food-items-simple.php?random=true&limit=12');
        const data = await response.json();

        if (!data.success) {
          throw new Error(data.error || 'Failed to fetch food items');
        }

        const foodItems = data.data;
        container.innerHTML = '';

        if (foodItems.length === 0) {
          container.innerHTML = '<div class="col-12 text-center"><p class="text-muted">No featured items available at the moment.</p></div>';
          return;
        }

        // Get food IDs for review lookup
        const foodIds = foodItems.map(item => item.id);

        // Fetch review data
        let reviewData = {};
        try {
          const reviewResponse = await fetch(`../api/food-reviews.php?ids=${foodIds.join(',')}`);
          const reviewResult = await reviewResponse.json();
          reviewData = reviewResult;
        } catch (reviewError) {
          console.warn('Could not fetch review data:', reviewError);
        }

        // Generate food cards
        foodItems.forEach((foodItem, index) => {
          const itemName = foodItem.name;
          const imagePath = `../${foodItem.image}`;

          // Get review data for this food item
          const reviews = reviewData[foodItem.id] || {
            avg_rating: 0,
            review_count: 0
          };
          const rating = reviews.avg_rating;
          const reviewCount = reviews.review_count;

          const foodCard = document.createElement('div');
          foodCard.className = 'col-md-6 col-lg-4 col-xl-3 mb-4';
          foodCard.innerHTML = `
                    <div class="card food-card h-100 shadow-sm border-0" onclick="openFoodDetail(${foodItem.id})" style="cursor: pointer;">
                        <div class="position-relative">
                            <img src="${imagePath}" alt="${itemName}" class="card-img-top food-image" style="height: 220px; object-fit: cover;">
                            <div class="position-absolute top-0 end-0 m-2">
                                <span class="badge bg-warning text-dark">
                                    <i class="fas fa-star me-1"></i>${rating > 0 ? rating : '0.0'}
                                </span>
                            </div>
                            <div class="position-absolute top-0 start-0 m-2">
                                <span class="badge bg-success">Fresh</span>
                            </div>
                        </div>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title fw-bold text-dark mb-2">${itemName}</h5>
                            <div class="d-flex align-items-center mb-3">
                                <div class="rating-stars me-2">${generateStarRating(rating)}</div>
                                <small class="text-muted">(${reviewCount} reviews)</small>
                            </div>
                            <div class="mb-3">
                                <span class="h5 text-warning fw-bold"><span class="currency-symbol">৳</span>${foodItem.price.toFixed(2)}</span>
                                <small class="text-muted ms-2">per serving</small>
                            </div>
                            <div class="mt-auto">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div class="btn-group" role="group" onclick="event.stopPropagation()">
                                        <button class="btn btn-outline-secondary btn-sm" onclick="removeFromCart('${itemName}')" id="remove-${itemName.replace(/\s+/g, '-')}" ${getItemQuantity(itemName) === 0 ? 'disabled' : ''}>
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <span class="btn btn-light border" id="qty-${itemName.replace(/\s+/g, '-')}">${getItemQuantity(itemName)}</span>
                                        <button class="btn btn-outline-primary btn-sm" onclick="addToCart('${itemName}')">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                                <button class="btn btn-warning w-100 fw-semibold" onclick="event.stopPropagation(); addToCart('${itemName}')">
                                    <i class="fas fa-shopping-cart me-2"></i>Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                `;
          container.appendChild(foodCard);
        });

        // Update quantity displays
        setTimeout(updateAllQuantityDisplays, 100);

      } catch (error) {
        console.error('Error loading featured food items:', error);
        container.innerHTML = '<div class="col-12 text-center"><p class="text-danger">Error loading featured items. Please try again later.</p></div>';
      }
    }

    // Generate star rating HTML
    function generateStarRating(rating) {
      const fullStars = Math.floor(rating);
      const hasHalfStar = rating % 1 !== 0;
      let stars = '';

      for (let i = 0; i < fullStars; i++) {
        stars += '<i class="fas fa-star text-warning"></i>';
      }

      if (hasHalfStar) {
        stars += '<i class="fas fa-star-half-alt text-warning"></i>';
      }

      const emptyStars = 5 - Math.ceil(rating);
      for (let i = 0; i < emptyStars; i++) {
        stars += '<i class="far fa-star text-warning"></i>';
      }

      return stars;
    }

    // Cart functions are now handled by script.js
  </script>

  <!-- Include main JavaScript file for cart functionality -->
  <script src="../javaScript/script.js"></script>

  <!-- HOW IT WORKS -->
  <section id="how-it-works" class="py-5">
    <div class="container-fluid">
      <div class="text-center mb-5">
        <h2 class="display-5 fw-bold text-dark mb-3">How It Works</h2>
        <p class="lead text-muted">Get your favorite food in just 3 simple steps</p>
      </div>

      <div class="row g-4">
        <div class="col-md-4">
          <div class="text-center">
            <div class="step-icon mx-auto mb-4 d-flex align-items-center justify-content-center">
              <i class="fas fa-search text-white fs-2"></i>
            </div>
            <h4 class="fw-bold mb-3">1. Find Your Food</h4>
            <p class="text-muted">Browse categories, filter by diet, and discover dishes you love.</p>
          </div>
        </div>

        <div class="col-md-4">
          <div class="text-center">
            <div class="step-icon mx-auto mb-4 d-flex align-items-center justify-content-center">
              <i class="fas fa-shopping-cart text-white fs-2"></i>
            </div>
            <h4 class="fw-bold mb-3">2. Place Your Order</h4>
            <p class="text-muted">Add to cart, choose delivery or pickup, and checkout securely.</p>
          </div>
        </div>

        <div class="col-md-4">
          <div class="text-center">
            <div class="step-icon mx-auto mb-4 d-flex align-items-center justify-content-center">
              <i class="fas fa-utensils text-white fs-2"></i>
            </div>
            <h4 class="fw-bold mb-3">3. Enjoy Your Meal</h4>
            <!-- <p class="text-muted">Track your delivery and enjoy fresh food delivered to your door.</p> -->
            <p class="text-muted">Enjoy fresh food delivered to your door.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- WHY CHOOSE -->
  <section id="why" class="py-5 bg-light">
    <div class="container-fluid">
      <div class="text-center mb-5">
        <h2 class="display-5 fw-bold text-dark mb-3">Why Choose HungryHub?</h2>
        <p class="lead text-muted">We're committed to delivering the best food experience</p>
      </div>

      <div class="row g-4">
        <div class="col-md-6 col-lg-3">
          <div class="feature-card text-center p-4 h-100">
            <div class="feature-icon mb-3">
              <i class="fas fa-star text-warning fs-1"></i>
            </div>
            <h5 class="fw-bold mb-3">Customer Reviews & Ratings</h5>
            <p class="text-muted">Read authentic reviews and ratings from our satisfied customers before ordering.</p>
          </div>
        </div>

        <div class="col-md-6 col-lg-3">
          <div class="feature-card text-center p-4 h-100">
            <div class="feature-icon mb-3">
              <i class="fas fa-globe text-primary fs-1"></i>
            </div>
            <h5 class="fw-bold mb-3">Wide Variety of Cuisines</h5>
            <p class="text-muted">From <?php echo implode(' to ', array_column($featured_categories, 'category')); ?> — <?php echo $total_food_items; ?>+ choices for every appetite.</p>
          </div>
        </div>

        <div class="col-md-6 col-lg-3">
          <div class="feature-card text-center p-4 h-100">
            <div class="feature-icon mb-3">
              <i class="fas fa-leaf text-success fs-1"></i>
            </div>
            <h5 class="fw-bold mb-3">Fresh, Quality Ingredients</h5>
            <p class="text-muted">We partner with trusted kitchens that use carefully sourced ingredients.</p>
          </div>
        </div>

        <div class="col-md-6 col-lg-3">
          <div class="feature-card text-center p-4 h-100">
            <div class="feature-icon mb-3">
              <i class="fas fa-shield-alt text-info fs-1"></i>
            </div>
            <h5 class="fw-bold mb-3">Secure & Easy Ordering</h5>
            <p class="text-muted">Multiple payment options and saved addresses for faster checkouts.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- TESTIMONIALS -->
  <section id="testimonials" class="py-5">
    <div class="container-fluid">
      <div class="text-center mb-5">
        <h2 class="display-5 fw-bold text-dark mb-3">What Customers Say</h2>
        <p class="lead text-muted">Hear from our satisfied customers</p>
      </div>

      <div id="testimonialCarousel" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-inner">
          <?php if (!empty($latest_reviews)) { ?>
            <?php foreach ($latest_reviews as $idx => $review) { ?>
              <div class="carousel-item <?php echo $idx === 0 ? 'active' : ''; ?>">
                <div class="row justify-content-center">
                  <div class="col-lg-8">
                    <div class="testimonial-card text-center p-5 rounded-4 shadow-sm bg-white">
                      <img src="<?php echo isset($review['food_image']) && $review['food_image'] ? '../' . htmlspecialchars($review['food_image']) : '../assets/user_signup.jpg'; ?>"
                        alt="<?php echo htmlspecialchars($review['food_name'] ?? 'Food'); ?>"
                        class="rounded-circle mb-4"
                        width="80" height="80">
                      <div class="mb-2">
                        <?php
                        $rounded = (int)round((float)($review['rating'] ?? 0));
                        for ($i = 1; $i <= 5; $i++) {
                          $filled = $i <= $rounded ? 'text-warning' : 'text-muted';
                          echo '<i class="fas fa-star ' . $filled . '"></i>';
                        }
                        ?>
                      </div>
                      <blockquote class="blockquote">
                        <p class="fs-5 mb-4">"<?php echo htmlspecialchars($review['review_text']); ?>"</p>
                      </blockquote>
                      <div class="mb-2">
                        <span class="fw-semibold text-dark"><?php echo htmlspecialchars($review['food_name'] ?? ''); ?></span>
                      </div>
                      <footer class="blockquote-footer">
                        <cite class="fw-semibold"><?php echo htmlspecialchars($review['user_name']); ?></cite>
                        <small class="text-muted">Verified Customer</small>
                      </footer>
                    </div>
                  </div>
                </div>
              </div>
            <?php } ?>
          <?php } else { ?>
            <div class="carousel-item active">
              <div class="row justify-content-center">
                <div class="col-lg-8">
                  <div class="testimonial-card text-center p-5 rounded-4 shadow-sm bg-white">
                    <img src="../assets/user_signup.jpg"
                      alt="Customer"
                      class="rounded-circle mb-4"
                      width="80" height="80">
                    <blockquote class="blockquote">
                      <p class="fs-5 mb-4">"No reviews yet. Be the first to share your HungryHub experience!"</p>
                    </blockquote>
                    <footer class="blockquote-footer">
                      <cite class="fw-semibold">Our Customers</cite>
                      <small class="text-muted">HungryHub Community</small>
                    </footer>
                  </div>
                </div>
              </div>
            </div>
          <?php } ?>
        </div>

        <button class="carousel-control-prev" type="button" data-bs-target="#testimonialCarousel" data-bs-slide="prev">
          <span class="carousel-control-prev-icon" aria-hidden="true"></span>
          <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#testimonialCarousel" data-bs-slide="next">
          <span class="carousel-control-next-icon" aria-hidden="true"></span>
          <span class="visually-hidden">Next</span>
        </button>
      </div>
    </div>
  </section>

</main>

<?php include '../components/footer.php'; ?>