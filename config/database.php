<?php
// Database configuration

// Automatically detect whether running locally (XAMPP) or in production (InfinityFree)
$serverName = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
$cleanHost = explode(':', $serverName)[0];

$is_local = in_array($cleanHost, ['localhost', '127.0.0.1', '::1'])
    || (empty($cleanHost) && php_sapi_name() === 'cli' && empty(getenv('DB_HOST')));

if ($is_local) {
    // Local development configuration (XAMPP)
    $host = 'localhost';
    $port = 3307;
    $dbname = 'hungry_hub';
    $username = 'root';
    $password = '';
} else {
    // Production configuration (InfinityFree)
    $host = 'sql104.infinityfree.com';
    $port = 3306;
    $dbname = 'if0_43010794_hungry_hub';
    $username = 'if0_43010794';
    $password = 'mehedi153noor';
}

// Allow environment variable overrides if present
if (getenv('DB_HOST')) {
    $host = getenv('DB_HOST');
}
if (getenv('DB_PORT')) {
    $port = getenv('DB_PORT');
}
if (getenv('DB_NAME')) {
    $dbname = getenv('DB_NAME');
}
if (getenv('DB_USER')) {
    $username = getenv('DB_USER');
}
if (getenv('DB_PASS') !== false && getenv('DB_PASS') !== null) {
    $password = getenv('DB_PASS');
}
