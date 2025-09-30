<?php
require_once '../auth/auth_functions.php';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title><?php echo isset($page_title) ? $page_title . ' — ' : ''; ?>HungryHub</title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  
  <!-- Google Font -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  
  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <link rel="stylesheet" href="../cssFiles/homePage.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="../cssFiles/cart_modal.css?v=<?php echo time(); ?>">
  <?php if (isset($include_food_cards_css) && $include_food_cards_css): ?>
  <link rel="stylesheet" href="../cssFiles/foodCards.css?v=<?php echo time(); ?>">
  <?php endif; ?>
  <?php if (isset($include_profile_css) && $include_profile_css): ?>
  <link rel="stylesheet" href="../cssFiles/profile.css?v=<?php echo time(); ?>">
  <?php endif; ?>
  <?php if (isset($include_food_detail_css) && $include_food_detail_css): ?>
  <link rel="stylesheet" href="../cssFiles/food-detail.css?v=<?php echo time(); ?>">
  <?php endif; ?>
  <?php if (isset($include_checkout_css) && $include_checkout_css): ?>
  <link rel="stylesheet" href="../cssFiles/checkout.css?v=<?php echo time(); ?>">
  <?php endif; ?>
  <?php if (isset($include_card_payment_css) && $include_card_payment_css): ?>
  <link rel="stylesheet" href="../cssFiles/card-payment.css?v=<?php echo time(); ?>">
  <?php endif; ?>
</head>
<body>
  <script>
    // expose logged in user id to JS for coupon validation
    <?php if (function_exists('isLoggedIn') && isLoggedIn()): ?>
      window.__currentUserId = <?php echo (int) getCurrentUser()['id']; ?>;
    <?php else: ?>
      window.__currentUserId = 0;
    <?php endif; ?>
  </script>
  <!-- Navbar -->
  <header class="site-header">
    <nav class="navbar navbar-expand-lg navbar-light">
      <div class="container-fluid">
        <a class="navbar-brand brand" href="<?php echo (basename($_SERVER['PHP_SELF']) == 'checkout.php') ? '../pages/index.php' : 'index.php'; ?>">
          <i class="fas fa-utensils me-2"></i>
          <span class="fw-bold">HungryHub</span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
          <ul class="navbar-nav mx-auto">
            <li class="nav-item">
              <a class="nav-link" href="<?php echo (basename($_SERVER['PHP_SELF']) == 'checkout.php') ? '../pages/index.php#menu' : 'index.php#menu'; ?>">Menu</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="<?php echo (basename($_SERVER['PHP_SELF']) == 'checkout.php') ? '../pages/index.php#how-it-works' : 'index.php#how-it-works'; ?>">How it works</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="<?php echo (basename($_SERVER['PHP_SELF']) == 'checkout.php') ? '../pages/index.php#why' : 'index.php#why'; ?>">Why HungryHub</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="<?php echo (basename($_SERVER['PHP_SELF']) == 'checkout.php') ? '../pages/index.php#testimonials' : 'index.php#testimonials'; ?>">Reviews</a>
            </li>
          </ul>
          
          <div class="d-flex gap-2 align-items-center">
            <!-- Cart Section -->
            <div class="cart-section me-3">
              <a href="#" class="cart-link text-decoration-none" onclick="event.preventDefault(); showCartModal();">
                <div class="cart-icon-container position-relative">
                  <i class="fas fa-shopping-cart cart-icon"></i>
                  <span class="cart-badge">0</span>
                </div>
              </a>
            </div>
            
            <?php if (isLoggedIn()): ?>
              <!-- Profile Dropdown -->
              <div class="dropdown">
                <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                  <i class="fas fa-user me-2"></i>
                  <?php echo htmlspecialchars(getCurrentUser()['name']); ?>
                </button>
                <ul class="dropdown-menu">
                  <li><a class="dropdown-item" href="<?php echo (basename($_SERVER['PHP_SELF']) == 'checkout.php') ? '../pages/profile.php' : 'profile.php'; ?>"><i class="fas fa-user me-2"></i>Profile</a></li>
                  <li><a class="dropdown-item" href="<?php echo (basename($_SERVER['PHP_SELF']) == 'checkout.php') ? '../pages/orders.php' : 'orders.php'; ?>"><i class="fas fa-shopping-bag me-2"></i>Orders</a></li>
                  <li><hr class="dropdown-divider"></li>
                  <li><a class="dropdown-item" href="<?php echo (basename($_SERVER['PHP_SELF']) == 'checkout.php') ? '../pages/logout.php' : 'logout.php'; ?>"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                </ul>
              </div>
            <?php else: ?>
              <!-- Auth Buttons -->
              <a class="btn btn-outline-primary" href="<?php echo (basename($_SERVER['PHP_SELF']) == 'checkout.php') ? '../pages/signin.php' : 'signin.php'; ?>">Sign In</a>
              <a class="btn btn-primary" href="<?php echo (basename($_SERVER['PHP_SELF']) == 'checkout.php') ? '../pages/signup.php' : 'signup.php'; ?>">Sign Up</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </nav>
  </header>

  <!-- Cart Modal Component -->
  <?php include 'cart_modal.php'; ?>
