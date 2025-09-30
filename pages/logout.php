<?php
require_once '../auth/auth_functions.php';

// Logout user
logoutUser();

// Redirect to home page
header('Location: index.php');
exit();
?>
