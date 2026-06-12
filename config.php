<?php
// Start output buffering so redirects work even if something echoes before header()
if (!ob_get_level()) {
    ob_start();
}
// Site Configuration (must be before session)
define('SITE_NAME', 'Ecodez Store');
define('SITE_URL', 'https://computerlk.com');
define('SITE_PATH', rtrim(parse_url(SITE_URL, PHP_URL_PATH) ?: '/', '/') . '/');
define('BASE_PATH', __DIR__ . DIRECTORY_SEPARATOR);

// Session ආරම්භ කිරීම සරල කිරීම
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'admin_ecodestore');
define('DB_PASS', 'Ravindu2001@');
define('DB_NAME', 'admin_ecodestore');

// Currency
define('CURRENCY', 'LKR');

// Google Sign-In (leave empty to hide Google login button)
define('GOOGLE_CLIENT_ID', '');
define('GOOGLE_CLIENT_SECRET', '');
define('GOOGLE_REDIRECT_URI', SITE_URL . '/auth/google-callback.php');
define('LOGIN_REDIRECT', SITE_URL . '/index.php');

// PayHere (Sri Lanka) – leave empty to hide PayHere at checkout
define('PAYHERE_MERCHANT_ID', '');
define('PAYHERE_APP_SECRET', ''); // for return verification if needed

// Connect to Database (try connecting to database)
$conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// If database doesn't exist, connect without selecting database
if ($conn->connect_error) {
    // Try connecting to MySQL only (to allow database setup)
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

    if ($conn->connect_error) {
        die("Database connection failed: " . $conn->connect_error . "<br>Please start XAMPP MySQL server.");
    }
}

// Set charset
$conn->set_charset("utf8mb4");
?>