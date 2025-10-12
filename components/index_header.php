<?php
require_once '../auth/auth_functions.php';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>HungryHub — Your Cravings, Delivered.</title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  
  <!-- Google Font -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  
  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <link rel="stylesheet" href="../cssFiles/homePage.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="../cssFiles/cart_modal.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="../cssFiles/foodCards.css?v=<?php echo time(); ?>">
</head>
<body>
  <!-- Navbar -->
  <header class="site-header">
    <nav class="navbar navbar-expand-lg navbar-light">
      <div class="container-fluid">
        <a class="navbar-brand brand" href="#">
          <i class="fas fa-utensils me-2"></i>
          <span class="fw-bold">HungryHub</span>
        </a>

        <!-- Cart + Profile: always visible (outside collapse) -->
        <div class="d-flex gap-2 align-items-center ms-auto me-2 order-lg-3 ms-lg-0">
            <!-- Cart Section -->
            <div class="cart-section me-3">
              <a href="#cart" class="cart-link text-decoration-none">
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
                  <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                  <li><a class="dropdown-item" href="orders.php"><i class="fas fa-shopping-bag me-2"></i>Orders</a></li>
                  <li><hr class="dropdown-divider"></li>
                  <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                </ul>
              </div>
            <?php else: ?>
              <!-- Auth Buttons -->
              <a class="btn btn-outline-primary" href="signin.php">Sign In</a>
              <a class="btn btn-primary" href="signup.php">Sign Up</a>
            <?php endif; ?>
        </div>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse order-lg-2" id="navbarNav">
          <ul class="navbar-nav mx-auto">
            <li class="nav-item">
              <a class="nav-link" href="#menu">Menu</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="#how-it-works">How it works</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="#why">Why HungryHub</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="#testimonials">Reviews</a>
            </li>
          </ul>
        </div>
      </div>
    </nav>
  </header>

  <!-- Cart Modal Component -->
  <?php include 'cart_modal.php'; ?>