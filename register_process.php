<?php
/**
 * Register form handler - redirect via PHP Header
 */
require_once __DIR__ . '/config.php';

// කේතයේ ඇති අමතර හිස් ඉඩවල් මකා දමා "Headers already sent" error එක වැළැක්වීම
if (ob_get_level()) ob_end_clean();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: register.php");
    exit;
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

if (empty($name) || empty($email) || empty($password)) {
    $_SESSION['register_error'] = 'Name, email and password are required.';
    $_SESSION['register_old'] = $_POST;
    header("Location: register.php");
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['register_error'] = 'Please enter a valid email.';
    $_SESSION['register_old'] = $_POST;
    header("Location: register.php");
    exit;
}
if (strlen($password) < 6) {
    $_SESSION['register_error'] = 'Password must be at least 6 characters.';
    $_SESSION['register_old'] = $_POST;
    header("Location: register.php");
    exit;
}
if ($password !== $confirm) {
    $_SESSION['register_error'] = 'Passwords do not match.';
    $_SESSION['register_old'] = $_POST;
    header("Location: register.php");
    exit;
}

$stmt = $conn->prepare("SELECT id FROM customers WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    $_SESSION['register_error'] = 'This email is already registered. Please login or use another email.';
    $_SESSION['register_old'] = $_POST;
    header("Location: register.php");
    exit;
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$ins = $conn->prepare("INSERT INTO customers (email, password, name, phone) VALUES (?, ?, ?, ?)");
$ins->bind_param("ssss", $email, $hash, $name, $phone);
if (!$ins->execute()) {
    $_SESSION['register_error'] = 'Registration failed. Please try again.';
    $_SESSION['register_old'] = $_POST;
    header("Location: register.php");
    exit;
}

// සාර්ථකව රෙජිස්ටර් වූ පසු කෙලින්ම Login පිටුවට යැවීම
header("Location: login.php?registered=1");
exit;
