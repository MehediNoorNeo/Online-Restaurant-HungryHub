<?php
require_once '../auth/auth_functions.php';

$message = '';
$message_type = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($name) || empty($email) || empty($password)) {
        $message = 'Name, email, and password are required';
        $message_type = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address';
        $message_type = 'error';
    } elseif (!empty($phone) && !preg_match('/^(\+?88)?01[3-9]\d{8}$/', $phone)) {
        $message = 'Please enter a valid Bangladesh phone number (e.g., 01XXXXXXXXX or +8801XXXXXXXXX)';
        $message_type = 'error';
    } elseif (strlen($password) < 6) {
        $message = 'Password must be at least 6 characters long';
        $message_type = 'error';
    } elseif ($password !== $confirm_password) {
        $message = 'Passwords do not match';
        $message_type = 'error';
    } else {
        // Attempt to register user
        $result = registerUser($name, $email, $phone, $address, $password);
        
        if ($result['success']) {
            // Auto-login after successful registration
            $loginResult = loginUser($email, $password);
            if ($loginResult['success']) {
                header('Location: index.php');
                exit();
            }
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
    <title>Sign Up - HungryHub</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="../cssFiles/homePage.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../cssFiles/signup.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="auth-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-5">
                    <div class="auth-card">
                        <div class="auth-header">
                            <h2 class="mb-0">
                                <i class="fas fa-utensils me-2"></i>
                                HungryHub
                            </h2>
                            <p class="mb-0 mt-2">Create your account</p>
                        </div>
                        
                        <div class="auth-body">
                            <?php if ($message): ?>
                                <div class="message <?php echo $message_type; ?>">
                                    <?php echo htmlspecialchars($message); ?>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           inputmode="tel" pattern="^(\+?88)?01[3-9]\d{8}$" 
                                           title="Bangladesh mobile format: 01XXXXXXXXX or +8801XXXXXXXXX" 
                                           placeholder="e.g., 017XXXXXXXX or +88017XXXXXXXX" 
                                           value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="address" class="form-label">Address</label>
                                    <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="confirm_password" class="form-label">Confirm Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                                
                                <button type="submit" class="btn btn-auth w-100 text-white">
                                    <i class="fas fa-user-plus me-2"></i>
                                    Create Account
                                </button>
                            </form>
                            
                            <div class="text-center mt-4">
                                <p class="mb-0">Already have an account? 
                                    <a href="signin.php" class="text-decoration-none fw-semibold" style="color: #667eea;">
                                        Sign In
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
