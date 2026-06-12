<?php
session_start();
header('Content-Type: application/json');

require_once dirname(__DIR__) . '/config.php';

if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'add') {
    $product_id = (int)($_POST['product_id'] ?? $_GET['product_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? $_GET['quantity'] ?? 1);
    if ($product_id < 1 || $quantity < 1) {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        exit;
    }
    if (!isset($_SESSION['cart'][$product_id])) $_SESSION['cart'][$product_id] = 0;
    $_SESSION['cart'][$product_id] += $quantity;
    echo json_encode(['success' => true, 'count' => array_sum($_SESSION['cart'])]);
    exit;
}

if ($action === 'update') {
    $product_id = (int)($_POST['product_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 0);
    if ($product_id < 1) {
        echo json_encode(['success' => false]);
        exit;
    }
    if ($quantity <= 0) unset($_SESSION['cart'][$product_id]);
    else $_SESSION['cart'][$product_id] = $quantity;
    echo json_encode(['success' => true, 'count' => array_sum($_SESSION['cart'])]);
    exit;
}

if ($action === 'remove') {
    $product_id = (int)($_POST['product_id'] ?? $_GET['product_id'] ?? 0);
    unset($_SESSION['cart'][$product_id]);
    echo json_encode(['success' => true, 'count' => array_sum($_SESSION['cart'])]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action']);
