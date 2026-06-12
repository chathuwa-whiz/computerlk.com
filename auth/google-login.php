<?php
require_once dirname(__DIR__) . '/config.php';

if (empty(GOOGLE_CLIENT_ID) || empty(GOOGLE_CLIENT_SECRET)) {
    header('Location: ' . SITE_URL . '/login.php?error=google_not_configured');
    exit;
}

$redirect_uri = GOOGLE_REDIRECT_URI;
$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;
$_SESSION['oauth_redirect'] = $_GET['redirect'] ?? '';

$url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
    'client_id'     => GOOGLE_CLIENT_ID,
    'redirect_uri'  => $redirect_uri,
    'response_type' => 'code',
    'scope'         => 'email profile',
    'state'         => $state,
    'access_type'   => 'online',
    'prompt'        => 'select_account',
]);

header('Location: ' . $url);
exit;
