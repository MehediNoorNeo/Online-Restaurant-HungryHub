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
    // Handle password change action
    if (($_POST['action'] ?? '') === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        if (strlen($newPassword) < 6) {
            $_SESSION['flash_message'] = 'New password must be at least 6 characters';
            $_SESSION['flash_type'] = 'error';
        } elseif ($newPassword !== $confirmPassword) {
            $_SESSION['flash_message'] = 'New password and confirmation do not match';
            $_SESSION['flash_type'] = 'error';
        } else {
            $change = changeUserPassword($user['id'], $currentPassword, $newPassword);
            $_SESSION['flash_message'] = $change['message'];
            $_SESSION['flash_type'] = $change['success'] ? 'success' : 'error';
        }
        header('Location: profile.php');
        exit();
    }
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
$passwordLastChanged = getPasswordLastChanged($user['id']);

// Set page title for header
$page_title = 'Profile';

// Include profile CSS
$include_profile_css = true;
?>

<?php include '../components/header.php'; ?>

    <div class="profile-container">
        <div class="container-fluid">
            <div class="row">
                <!-- Sidebar Navigation -->
                <div class="col-lg-3 col-md-4">
                    <div class="profile-sidebar">
                        <div class="profile-card">
                            <div class="profile-header">
                                <div class="profile-avatar-container">
                                    <div class="profile-avatar" id="profileAvatar">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <button class="avatar-upload-btn" id="avatarUploadBtn" title="Change Profile Picture">
                                        <i class="fas fa-camera"></i>
                                    </button>
                                    <input type="file" id="avatarInput" accept="image/*" style="display: none;">
                                </div>
                                <h3 class="profile-name"><?php echo htmlspecialchars($user['name']); ?></h3>
                                <p class="profile-email"><?php echo htmlspecialchars($userProfile['email']); ?></p>
                                <div class="member-since">
                                    <i class="fas fa-calendar-alt me-2"></i>
                                    Member since <?php echo date('M Y', strtotime($userProfile['created_at'])); ?>
                                </div>
                            </div>
                            
                            <div class="profile-stats">
                                <div class="stat-item">
                                    <div class="stat-icon">
                                        <i class="fas fa-shopping-bag"></i>
                                    </div>
                                    <div class="stat-content">
                                        <div class="stat-number">0</div>
                                        <div class="stat-label">Total Orders</div>
                                    </div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-icon">
                                        <i class="fas fa-star"></i>
                                    </div>
                                    <div class="stat-content">
                                        <div class="stat-number">4.8</div>
                                        <div class="stat-label">Rating</div>
                                    </div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-icon">
                                        <i class="fas fa-trophy"></i>
                                    </div>
                                    <div class="stat-content">
                                        <div class="stat-number">Gold</div>
                                        <div class="stat-label">Member Level</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="profile-actions">
                                <button type="button" class="btn btn-primary btn-edit" id="editProfileBtn">
                                    <i class="fas fa-edit me-2"></i>
                                    Edit Profile
                                </button>
                                <a href="orders.php" class="btn btn-outline-primary">
                                    <i class="fas fa-shopping-bag me-2"></i>
                                    View Orders
                                </a>
                                <a href="index.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>
                                    Back to Home
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Main Content -->
                <div class="col-lg-9 col-md-8">
                    <div class="profile-main">
                        <!-- Flash Messages -->
                        <?php
                            if (isset($_SESSION['flash_message'])) {
                                $message = $_SESSION['flash_message'];
                                $message_type = $_SESSION['flash_type'] ?? 'info';
                                unset($_SESSION['flash_message'], $_SESSION['flash_type']);
                            }
                        ?>
                        <?php if ($message): ?>
                            <div class="alert alert-<?php echo $message_type === 'error' ? 'danger' : $message_type; ?> alert-dismissible fade show" role="alert">
                                <i class="fas fa-<?php echo $message_type === 'error' ? 'exclamation-triangle' : ($message_type === 'success' ? 'check-circle' : 'info-circle'); ?> me-2"></i>
                                <?php echo htmlspecialchars($message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Profile Information Card -->
                        <div class="profile-info-card">
                            <div class="card-header">
                                <h4 class="card-title">
                                    <i class="fas fa-user-edit me-2"></i>
                                    Personal Information
                                </h4>
                                <p class="card-subtitle">Manage your personal details and contact information</p>
                            </div>
                            
                            <div class="card-body">
                                <form method="POST" action="" id="profileForm">
                                    <div class="row">
                                        <div class="col-md-6 mb-4">
                                            <div class="form-group">
                                                <label for="name" class="form-label">
                                                    <i class="fas fa-user me-2"></i>
                                                    Full Name
                                                </label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control profile-field" id="name" name="name" 
                                                           value="<?php echo htmlspecialchars($userProfile['name']); ?>" readonly required>
                                                    <span class="input-group-text">
                                                        <i class="fas fa-lock"></i>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6 mb-4">
                                            <div class="form-group">
                                                <label for="email" class="form-label">
                                                    <i class="fas fa-envelope me-2"></i>
                                                    Email Address
                                                </label>
                                                <div class="input-group">
                                                    <input type="email" class="form-control profile-field" id="email" name="email" 
                                                           value="<?php echo htmlspecialchars($userProfile['email']); ?>" readonly required>
                                                    <span class="input-group-text">
                                                        <i class="fas fa-lock"></i>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-4">
                                            <div class="form-group">
                                                <label for="phone" class="form-label">
                                                    <i class="fas fa-phone me-2"></i>
                                                    Phone Number
                                                </label>
                                                <div class="input-group">
                                                    <span class="input-group-text">+880</span>
                                                    <input type="tel" class="form-control profile-field" id="phone" name="phone" 
                                                           inputmode="tel" pattern="^(\+?88)?01[3-9]\d{8}$" 
                                                           title="Bangladesh mobile format: 01XXXXXXXXX or +8801XXXXXXXXX" 
                                                           placeholder="1XXXXXXXXX" 
                                                           value="<?php echo htmlspecialchars(str_replace('+880', '', $userProfile['phone'] ?? '')); ?>" readonly>
                                                </div>
                                                <div class="form-text">Enter your 11-digit mobile number</div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6 mb-4">
                                            <div class="form-group">
                                                <label for="status" class="form-label">
                                                    <i class="fas fa-shield-alt me-2"></i>
                                                    Account Status
                                                </label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control" value="<?php echo ucfirst($userProfile['status']); ?>" readonly>
                                                    <span class="input-group-text status-badge status-<?php echo strtolower($userProfile['status']); ?>">
                                                        <i class="fas fa-check-circle"></i>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <div class="form-group">
                                            <label for="address" class="form-label">
                                                <i class="fas fa-map-marker-alt me-2"></i>
                                                Address
                                            </label>
                                            <textarea class="form-control profile-field" id="address" name="address" rows="4" 
                                                      placeholder="Enter your full address..." readonly><?php echo htmlspecialchars($userProfile['address'] ?? ''); ?></textarea>
                                            <div class="form-text">Provide your complete address for delivery purposes</div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-actions" id="actionButtons">
                                        <button type="submit" class="btn btn-success btn-save" id="saveBtn" style="display: none;">
                                            <i class="fas fa-save me-2"></i>
                                            Save Changes
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-cancel" id="cancelBtn" style="display: none;">
                                            <i class="fas fa-times me-2"></i>
                                            Cancel
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Account Security Card -->
                        <div class="profile-info-card">
                            <div class="card-header">
                                <h4 class="card-title">
                                    <i class="fas fa-shield-alt me-2"></i>
                                    Account Security
                                </h4>
                                <p class="card-subtitle">Manage your account security settings</p>
                            </div>
                            
                            <div class="card-body">
                                <div class="security-options">
                                    <div class="security-item">
                                        <div class="security-info">
                                            <h6>Password</h6>
                                            <p>Last changed: <?php echo $passwordLastChanged ? date('M d, Y', strtotime($passwordLastChanged)) : 'Never'; ?></p>
                                        </div>
                                        <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                                            <i class="fas fa-key me-2"></i>
                                            Change Password
                                        </button>
                                    </div>
                                    
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-key me-2"></i>Change Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Current Password</label>
                            <input type="password" class="form-control" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" class="form-control" name="new_password" minlength="6" required>
                            <div class="form-text">At least 6 characters</div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" name="confirm_password" minlength="6" required>
                        </div>
                        <input type="hidden" name="action" value="change_password">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Password</button>
                    </div>
                </form>
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
            const avatarInput = document.getElementById('avatarInput');
            const avatarUploadBtn = document.getElementById('avatarUploadBtn');
            const profileAvatar = document.getElementById('profileAvatar');
            
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
            
            // Avatar upload functionality
            avatarUploadBtn.addEventListener('click', function() {
                avatarInput.click();
            });
            
            avatarInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        profileAvatar.innerHTML = `<img src="${e.target.result}" alt="Profile Picture" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">`;
                    };
                    reader.readAsDataURL(file);
                }
            });
            
            // Form validation
            const form = document.getElementById('profileForm');
            form.addEventListener('submit', function(e) {
                const phoneField = document.getElementById('phone');
                const phoneValue = phoneField.value;
                
                // Validate phone number
                if (phoneValue && !/^01[3-9]\d{8}$/.test(phoneValue)) {
                    e.preventDefault();
                    alert('Please enter a valid Bangladesh mobile number (e.g., 017XXXXXXXX)');
                    phoneField.focus();
                    return false;
                }
            });
            
            // Auto-dismiss alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });
    </script>
</body>
</html>
