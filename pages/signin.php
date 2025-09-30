<?php
require_once '../auth/auth_functions.php';

$message = '';
$message_type = '';

// Check for checkout redirect message
if (isset($_SESSION['checkout_message'])) {
    $message = $_SESSION['checkout_message'];
    $message_type = 'warning';
    unset($_SESSION['checkout_message']);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validation
    if (empty($identifier) || empty($password)) {
        $message = 'Email/Phone and password are required';
        $message_type = 'error';
    } else {
        // Attempt to login user
        $result = loginUser($identifier, $password);
        
        if ($result['success']) {
            // Check if user was redirected from checkout
            if (isset($_SESSION['checkout_redirect'])) {
                unset($_SESSION['checkout_redirect']);
                header('Location: checkout.php');
            } else {
                header('Location: index.php');
            }
            exit();
        } else {
            $message = $result['message'];
            $message_type = 'error';
        }
    }
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Sign In - HungryHub</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="../cssFiles/homePage.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../cssFiles/signin.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="auth-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-4">
                    <div class="auth-card">
                        <div class="auth-header">
                            <h2 class="mb-0">
                                <i class="fas fa-utensils me-2"></i>
                                HungryHub
                            </h2>
                            <p class="mb-0 mt-2">Welcome back!</p>
                        </div>
                        
                        <div class="auth-body">
                            <?php if ($message): ?>
                                <div class="message <?php echo $message_type; ?>">
                                    <?php echo htmlspecialchars($message); ?>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="identifier" class="form-label">Email or Phone</label>
                                    <input type="text" class="form-control" id="identifier" name="identifier" 
                                           inputmode="email" placeholder="Enter email or BD phone (01XXXXXXXXX or +8801XXXXXXXXX)"
                                           value="<?php echo htmlspecialchars($_POST['identifier'] ?? ''); ?>" required>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                                
                                <button type="submit" class="btn btn-auth w-100 text-white">
                                    <i class="fas fa-sign-in-alt me-2"></i>
                                    Sign In
                                </button>
                            </form>
                            
                            <div class="text-center mt-4">
                                <p class="mb-0">Don't have an account? 
                                    <a href="signup.php" class="text-decoration-none fw-semibold" style="color: #667eea;">
                                        Sign Up
                                    </a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
