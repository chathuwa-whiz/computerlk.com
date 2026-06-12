<?php
require_once dirname(__DIR__) . '/config.php';

if (empty(GOOGLE_CLIENT_ID) || empty(GOOGLE_CLIENT_SECRET)) {
    header('Location: ' . SITE_URL . '/login.php?error=google_not_configured');
    exit;
}

$error = $_GET['error'] ?? '';
$code = $_GET['code'] ?? '';
$state = $_GET['state'] ?? '';

if ($error) {
    header('Location: ' . SITE_URL . '/login.php?error=' . urlencode($error));
    exit;
}

if ($state !== ($_SESSION['oauth_state'] ?? '')) {
    header('Location: ' . SITE_URL . '/login.php?error=invalid_state');
    exit;
}

$redirect_after = $_SESSION['oauth_redirect'] ?? SITE_URL;
unset($_SESSION['oauth_state'], $_SESSION['oauth_redirect']);

if (empty($code)) {
    header('Location: ' . SITE_URL . '/login.php?error=no_code');
    exit;
}

$token_url = 'https://oauth2.googleapis.com/token';
$body = http_build_query([
    'code'          => $code,
    'client_id'     => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri'  => GOOGLE_REDIRECT_URI,
    'grant_type'    => 'authorization_code',
]);

$ctx = stream_context_create([
    'http' => [
        'method'  => 'POST',
        'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
        'content' => $body,
    ],
]);
$token_json = @file_get_contents($token_url, false, $ctx);
$token_data = $token_json ? json_decode($token_json, true) : null;
$access_token = $token_data['access_token'] ?? null;

if (!$access_token) {
    header('Location: ' . SITE_URL . '/login.php?error=token_failed');
    exit;
}

$userinfo_url = 'https://www.googleapis.com/oauth2/v2/userinfo?access_token=' . urlencode($access_token);
$user_json = @file_get_contents($userinfo_url);
$user = $user_json ? json_decode($user_json, true) : null;

if (!$user || empty($user['email'])) {
    header('Location: ' . SITE_URL . '/login.php?error=userinfo_failed');
    exit;
}

$email = $user['email'];
$name = $user['name'] ?? $email;
$google_id = $user['id'] ?? '';
$avatar = $user['picture'] ?? null;

$stmt = $conn->prepare("SELECT id, email, name, status FROM customers WHERE email = ? OR (google_id = ? AND ? != '') LIMIT 1");
$stmt->bind_param("sss", $email, $google_id, $google_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if ($row) {
    if ($row['status'] !== 'active') {
        header('Location: ' . SITE_URL . '/login.php?error=account_inactive');
        exit;
    }
    $customer_id = (int) $row['id'];
    $av = $avatar ? $conn->real_escape_string($avatar) : '';
    $conn->query("UPDATE customers SET name = '" . $conn->real_escape_string($name) . "', google_id = '" . $conn->real_escape_string($google_id) . "', avatar = '" . $av . "', updated_at = NOW() WHERE id = " . $customer_id);
} else {
    $stmt = $conn->prepare("INSERT INTO customers (email, name, google_id, avatar, status) VALUES (?, ?, ?, ?, 'active')");
    $stmt->bind_param("ssss", $email, $name, $google_id, $avatar);
    $stmt->execute();
    $customer_id = (int) $conn->insert_id;
}

$_SESSION['customer_id'] = (int) $customer_id;
$_SESSION['customer_email'] = $email;
$_SESSION['customer_name'] = $name ? trim($name) : $email;

$redirect_after = (strpos($redirect_after, 'http') === 0 || strpos($redirect_after, '/') === 0) ? $redirect_after : (rtrim(SITE_URL, '/') . '/' . ltrim($redirect_after, '/'));
if (strpos($redirect_after, 'http') !== 0) {
    $redirect_after = rtrim(SITE_URL, '/') . '/index.php';
}
session_write_close();
header('Location: ' . $redirect_after);
exit;
