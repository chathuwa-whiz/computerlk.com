<?php
require_once __DIR__ . '/config.php';
unset($_SESSION['customer_id'], $_SESSION['customer_email'], $_SESSION['customer_name'], $_SESSION['customer_avatar']);
header('Location: ' . (defined('LOGIN_REDIRECT') ? LOGIN_REDIRECT : SITE_URL . '/index.php'));
exit;
