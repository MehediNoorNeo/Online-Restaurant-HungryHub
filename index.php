<?php
// Redirect root requests to the main homepage
$queryString = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
header("Location: pages/index.php" . $queryString);
exit();
