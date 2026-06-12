<?php
require_once __DIR__ . '/config.php';

if (ob_get_level()) ob_end_clean();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    $_SESSION['login_error'] = 'Please enter email and password.';
    header("Location: login.php");
    exit;
}

$stmt = $conn->prepare("SELECT id, email, password, name, status FROM customers WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user || $user['status'] !== 'active') {
    $_SESSION['login_error'] = 'Invalid email or password.';
    header("Location: login.php");
    exit;
}
if (empty($user['password'])) {
    $_SESSION['login_error'] = 'This account uses Google login. Please sign in with Google.';
    header("Location: login.php");
    exit;
}
if (!password_verify($password, $user['password'])) {
    $_SESSION['login_error'] = 'Invalid email or password.';
    header("Location: login.php");
    exit;
}

// Sessions Set කිරීම
$_SESSION['customer_id'] = (int) $user['id'];
$_SESSION['customer_email'] = $user['email'];
$_SESSION['customer_name'] = $user['name'] ? trim($user['name']) : $user['email'];

$redirect = trim($_POST['redirect'] ?? $_GET['redirect'] ?? '') ?: LOGIN_REDIRECT;
if (strpos($redirect, 'http') !== 0) {
    $redirect = SITE_URL . '/' . ltrim($redirect, '/');
}

// අදාළ පිටුවට යොමු කිරීම
header("Location: " . $redirect);
exit;
