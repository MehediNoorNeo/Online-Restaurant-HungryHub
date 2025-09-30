<?php
require_once '../auth/auth_functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: signin.php');
    exit();
}

$user = getCurrentUser();
$message = '';
$message_type = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    
    // Validation
    if (empty($name) || empty($email)) {
        $_SESSION['flash_message'] = 'Name and email are required';
        $_SESSION['flash_type'] = 'error';
        header('Location: profile.php');
        exit();
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['flash_message'] = 'Please enter a valid email address';
        $_SESSION['flash_type'] = 'error';
        header('Location: profile.php');
        exit();
    } elseif (!empty($phone) && !preg_match('/^(\+?88)?01[3-9]\d{8}$/', $phone)) {
        $_SESSION['flash_message'] = 'Please enter a valid Bangladesh phone number (e.g., 01XXXXXXXXX or +8801XXXXXXXXX)';
        $_SESSION['flash_type'] = 'error';
        header('Location: profile.php');
        exit();
    } else {
        // Update profile
        $result = updateUserProfile($user['id'], $name, $email, $phone, $address);
        
        if ($result['success']) {
            $_SESSION['flash_message'] = $result['message'];
            $_SESSION['flash_type'] = 'success';
            header('Location: profile.php');
            exit();
        } else {
            $_SESSION['flash_message'] = $result['message'];
            $_SESSION['flash_type'] = 'error';
            header('Location: profile.php');
            exit();
        }
    }
}

// Get full user profile
$userProfile = getUserProfile($user['id']);

// Set page title for header
$page_title = 'Profile';

// Include profile CSS
$include_profile_css = true;
?>

<?php include '../components/header.php'; ?>

    <div class="profile-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <div class="profile-card">
                        <div class="profile-header">
                            <div class="profile-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            <h3 class="mb-0"><?php echo htmlspecialchars($user['name']); ?></h3>
                            <p class="mb-0">Member since <?php echo date('M Y', strtotime($userProfile['created_at'])); ?></p>
                            <button type="button" class="btn btn-light mt-3" id="editProfileBtn">
                                <i class="fas fa-edit me-2"></i>
                                Edit Profile
                            </button>
                        </div>
                        
                        <div class="profile-body">
                            <?php
                                if (isset($_SESSION['flash_message'])) {
                                    $message = $_SESSION['flash_message'];
                                    $message_type = $_SESSION['flash_type'] ?? 'info';
                                    unset($_SESSION['flash_message'], $_SESSION['flash_type']);
                                }
                            ?>
                            <?php if ($message): ?>
                                <div class="message <?php echo $message_type; ?>">
                                    <?php echo htmlspecialchars($message); ?>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" action="" id="profileForm">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="name" class="form-label">Full Name</label>
                                        <input type="text" class="form-control profile-field" id="name" name="name" 
                                               value="<?php echo htmlspecialchars($userProfile['name']); ?>" readonly required>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">Email Address</label>
                                        <input type="email" class="form-control profile-field" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($userProfile['email']); ?>" readonly required>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control profile-field" id="phone" name="phone" 
                                               inputmode="tel" pattern="^(\+?88)?01[3-9]\d{8}$" 
                                               title="Bangladesh mobile format: 01XXXXXXXXX or +8801XXXXXXXXX" 
                                               placeholder="e.g., 017XXXXXXXX or +88017XXXXXXXX" 
                                               value="<?php echo htmlspecialchars($userProfile['phone'] ?? ''); ?>" readonly>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="status" class="form-label">Status</label>
                                        <input type="text" class="form-control" value="<?php echo ucfirst($userProfile['status']); ?>" readonly>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="address" class="form-label">Address</label>
                                    <textarea class="form-control profile-field" id="address" name="address" rows="3" readonly><?php echo htmlspecialchars($userProfile['address'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="d-flex gap-2" id="actionButtons">
                                    <button type="submit" class="btn btn-primary" id="saveBtn" style="display: none;">
                                        <i class="fas fa-save me-2"></i>
                                        Save Changes
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" id="cancelBtn" style="display: none;">
                                        <i class="fas fa-times me-2"></i>
                                        Cancel
                                    </button>
                                    <a href="index.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left me-2"></i>
                                        Back to Home
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript for cart functionality -->
    <script src="../javaScript/script.js"></script>
    
    <!-- Profile Edit Functionality -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const editBtn = document.getElementById('editProfileBtn');
            const saveBtn = document.getElementById('saveBtn');
            const cancelBtn = document.getElementById('cancelBtn');
            const profileFields = document.querySelectorAll('.profile-field');
            const originalValues = {};
            
            // Store original values
            profileFields.forEach(field => {
                originalValues[field.id] = field.value;
            });
            
            // Edit button click handler
            editBtn.addEventListener('click', function() {
                // Enable editing
                profileFields.forEach(field => {
                    field.removeAttribute('readonly');
                    field.classList.add('editable');
                });
                
                // Show save and cancel buttons, hide edit button
                saveBtn.style.display = 'inline-block';
                cancelBtn.style.display = 'inline-block';
                editBtn.style.display = 'none';
            });
            
            // Cancel button click handler
            cancelBtn.addEventListener('click', function() {
                // Restore original values
                profileFields.forEach(field => {
                    field.value = originalValues[field.id];
                    field.setAttribute('readonly', 'readonly');
                    field.classList.remove('editable');
                });
                
                // Show edit button, hide save and cancel buttons
                editBtn.style.display = 'inline-block';
                saveBtn.style.display = 'none';
                cancelBtn.style.display = 'none';
            });
            
            // Save button click handler (form submission)
            saveBtn.addEventListener('click', function(e) {
                // The form will be submitted normally
                // No need to prevent default as we want the form to submit
            });
        });
    </script>
</body>
</html>
