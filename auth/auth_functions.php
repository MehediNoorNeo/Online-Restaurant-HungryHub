<?php
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

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Function to register a new user
function registerUser($name, $email, $phone, $address, $password) {
    global $pdo;
    
    try {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Email already exists'];
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user
        $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, address, password) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $email, $phone, $address, $hashedPassword]);
        
        return ['success' => true, 'message' => 'User registered successfully', 'user_id' => $pdo->lastInsertId()];
        
    } catch(PDOException $e) {
        return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
    }
}

// Function to login user (email or Bangladesh phone)
function loginUser($identifier, $password) {
    global $pdo;
    
    try {
        // Determine if identifier is email or BD phone
        $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL);
        $isBdPhone = preg_match('/^(\+?88)?01[3-9]\d{8}$/', $identifier);

        if (!$isEmail && !$isBdPhone) {
            return ['success' => false, 'message' => 'Enter a valid email or Bangladesh phone number'];
        }

        if ($isEmail) {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
            $stmt->execute([$identifier]);
        } else {
            $normalized = $identifier;
            // Normalize BD phone: store and compare as last 11 digits (01XXXXXXXXX)
            if (strpos($normalized, '+88') === 0) { $normalized = substr($normalized, 3); }
            if (strpos($normalized, '880') === 0) { $normalized = substr($normalized, 3); }
            $stmt = $pdo->prepare("SELECT * FROM users WHERE (REPLACE(REPLACE(phone, '+88', ''), '880', '') = ?) AND status = 'active'");
            $stmt->execute([$normalized]);
        }
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_logged_in'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            
            return ['success' => true, 'message' => 'Login successful', 'user' => $user];
        } else {
            return ['success' => false, 'message' => 'Invalid credentials'];
        }
        
    } catch(PDOException $e) {
        return ['success' => false, 'message' => 'Login failed: ' . $e->getMessage()];
    }
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;
}

// Function to get current user data
function getCurrentUser() {
    if (isLoggedIn()) {
        return [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'],
            'email' => $_SESSION['user_email']
        ];
    }
    return null;
}

// Function to logout user
function logoutUser() {
    session_destroy();
    return ['success' => true, 'message' => 'Logged out successfully'];
}

// Function to update user profile
function updateUserProfile($userId, $name, $email, $phone, $address) {
    global $pdo;
    
    try {
        // Check if email is already taken by another user
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $userId]);
        
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Email already taken by another user'];
        }
        
        // Update user profile
        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ?, address = ? WHERE id = ?");
        $stmt->execute([$name, $email, $phone, $address, $userId]);
        
        // Update session data
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
        
        return ['success' => true, 'message' => 'Profile updated successfully'];
        
    } catch(PDOException $e) {
        return ['success' => false, 'message' => 'Update failed: ' . $e->getMessage()];
    }
}

// Function to get user profile
function getUserProfile($userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT id, name, email, phone, address, status, created_at FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return null;
    }
}
?>
