    <!-- Menu Explorer Section -->
    <section class="py-2 bg-white">
      <div class="container-fluid">
        <div class="row g-2 justify-content-center">
          <?php
          // Database configuration
          $host = 'localhost';
          $dbname = 'hungry_hub';
          $username = 'root';
          $password = '';

          try {
              $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
              $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
              
              // Get distinct categories from database
              $stmt = $pdo->query("SELECT DISTINCT category FROM food_items ORDER BY category");
              $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
              
              // Default categories with their images (fallback)
              $default_categories = [
                  'Salad' => '../assets/salad_pic.png',
                  'Rolls' => '../assets/rolls_pic.png',
                  'Desserts' => '../assets/desserts_pic.png',
                  'Sandwich' => '../assets/sandwich_pic.png',
                  'Cake' => '../assets/cake_pic.png',
                  'Pure Veg' => '../assets/pure_veg_pic.png',
                  'Pasta' => '../assets/pasta_pic.png',
                  'Noodles' => '../assets/noodles_pic.png'
              ];
              
              // Merge database categories with default categories
              $all_categories = array_unique(array_merge($categories, array_keys($default_categories)));
              sort($all_categories);
              
              // Function to get category image
              function getCategoryImage($category, $default_categories) {
                  $category_slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '', $category));
                  
                  // Check if category has custom picture in main assets folder
                  $custom_image = "../assets/{$category_slug}_pic.png";
                  if (file_exists($custom_image)) {
                      return $custom_image;
                  }
                  
                  // Check if category has custom icon in main assets folder
                  $custom_icon = "../assets/{$category_slug}_icon.png";
                  if (file_exists($custom_icon)) {
                      return $custom_icon;
                  }
                  
                  // Use default image
                  return $default_categories[$category] ?? '../assets/default_category.png';
              }
              
              // Function to get category page URL
              function getCategoryPageUrl($category) {
                  $category_slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '', $category));
                  $page_url = "{$category_slug}.php";
                  
                  // Check if PHP page exists
                  if (file_exists($page_url)) {
                      return $page_url;
                  }
                  
                  // Fallback to default pages for existing categories
                  $default_pages = [
                      'Salad' => 'salad.php',
                      'Rolls' => 'rolls.php',
                      'Desserts' => 'desserts.php',
                      'Sandwich' => 'sandwich.php',
                      'Cake' => 'cake.php',
                      'Pure Veg' => 'pureveg.php',
                      'Pasta' => 'pasta.php',
                      'Noodles' => 'noodles.php'
                  ];
                  
                  return $default_pages[$category] ?? $page_url;
              }
              
              // Display categories
              foreach ($all_categories as $category) {
                  $category_image = getCategoryImage($category, $default_categories);
                  $category_url = getCategoryPageUrl($category);
                  $category_slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '', $category));
                  
                  echo '<div class="col-6 col-sm-4 col-md-3 col-lg-2 col-xl-1">';
                  echo '<a href="' . htmlspecialchars($category_url) . '" class="text-decoration-none">';
                  echo '<div class="menu-category-card text-center">';
                  echo '<div class="menu-image-container">';
                  echo '<img src="' . htmlspecialchars($category_image) . '" alt="' . htmlspecialchars($category) . '" class="menu-image">';
                  echo '</div>';
                  echo '<h6 class="menu-label mt-1">' . htmlspecialchars($category) . '</h6>';
                  echo '</div>';
                  echo '</a>';
                  echo '</div>';
              }
              
          } catch(PDOException $e) {
              // Fallback to default categories if database connection fails
              $default_categories = [
                  'Salad' => '../assets/salad_pic.png',
                  'Rolls' => '../assets/rolls_pic.png',
                  'Desserts' => '../assets/desserts_pic.png',
                  'Sandwich' => '../assets/sandwich_pic.png',
                  'Cake' => '../assets/cake_pic.png',
                  'Pure Veg' => '../assets/pure_veg_pic.png',
                  'Pasta' => '../assets/pasta_pic.png',
                  'Noodles' => '../assets/noodles_pic.png'
              ];
              
              foreach ($default_categories as $category => $image) {
                  $category_url = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '', $category)) . '.php';
                  if ($category === 'Pure Veg') $category_url = 'pureveg.php';
                  
                  echo '<div class="col-6 col-sm-4 col-md-3 col-lg-2 col-xl-1">';
                  echo '<a href="' . htmlspecialchars($category_url) . '" class="text-decoration-none">';
                  echo '<div class="menu-category-card text-center">';
                  echo '<div class="menu-image-container">';
                  echo '<img src="' . htmlspecialchars($image) . '" alt="' . htmlspecialchars($category) . '" class="menu-image">';
                  echo '</div>';
                  echo '<h6 class="menu-label mt-1">' . htmlspecialchars($category) . '</h6>';
                  echo '</div>';
                  echo '</a>';
                  echo '</div>';
              }
          }
          ?>
        </div>
      </div>
    </section>
