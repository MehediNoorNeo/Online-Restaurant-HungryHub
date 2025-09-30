<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include authentication functions
require_once 'auth_functions.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
$isLoggedIn = isLoggedIn();

// Return JSON response
echo json_encode([
    'loggedIn' => $isLoggedIn,
    'message' => $isLoggedIn ? 'User is logged in' : 'User is not logged in'
]);
?>
