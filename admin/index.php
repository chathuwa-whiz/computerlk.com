<?php
session_start();
require_once '../config.php';

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - <?php echo SITE_NAME; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>

<div class="admin-container">
    <div class="admin-sidebar">
        <div class="sidebar-header">
            <h3><?php echo SITE_NAME; ?></h3>
            <p class="text-muted small">Admin Panel</p>
        </div>

        <ul class="sidebar-nav">
            <li class="<?php echo $page === 'dashboard' ? 'active' : ''; ?>">
                <a href="index.php?page=dashboard">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
            <li class="<?php echo $page === 'products' ? 'active' : ''; ?>">
                <a href="index.php?page=products">
                    <i class="bi bi-box"></i> Products
                </a>
            </li>
            <li class="<?php echo $page === 'categories' ? 'active' : ''; ?>">
                <a href="index.php?page=categories">
                    <i class="bi bi-grid"></i> Categories
                </a>
            </li>
            <li class="<?php echo $page === 'orders' ? 'active' : ''; ?>">
                <a href="index.php?page=orders">
                    <i class="bi bi-cart"></i> Orders
                </a>
            </li>
            <li class="<?php echo $page === 'delivery' ? 'active' : ''; ?>">
               <a href="index.php?page=delivery">
                   <i class="bi bi-truck"></i> Delivery Settings
              </a>
           </li>
            <li class="<?php echo $page === 'customers' ? 'active' : ''; ?>">
                <a href="index.php?page=customers">
                    <i class="bi bi-people"></i> Customers
                </a>
            </li>
            <li class="<?php echo $page === 'promotions' ? 'active' : ''; ?>">
                <a href="index.php?page=promotions">
                    <i class="bi bi-megaphone"></i> Promotions (Banners)
                </a>
            </li>
            <li class="<?php echo $page === 'settings' ? 'active' : ''; ?>">
                <a href="index.php?page=settings">
                    <i class="bi bi-gear"></i> Settings
                </a>
            </li>
            <li>
                <a href="logout.php" class="text-danger">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </li>
        </ul>
    </div>

    <div class="admin-main">
        <div class="admin-topbar">
            <button class="btn btn-link sidebar-toggle">
                <i class="bi bi-list"></i>
            </button>
            
            <div class="ms-auto d-flex align-items-center">
                <div class="dropdown">
                    <button class="btn btn-link dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i> Admin
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#">Profile</a></li>
                        <li><a class="dropdown-item" href="#">Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="logout.php">Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="admin-content p-4">
            <?php
            // පිටු ලෝඩ් කරන කොටසට 'promotions' එකතු කර ඇත
            switch ($page) {
                case 'dashboard':
                    include 'pages/dashboard.php';
                    break;
                case 'products':
                    include 'pages/products.php';
                    break;
                case 'categories':
                    include 'pages/categories.php';
                    break;
                case 'orders':
                    include 'pages/orders.php';
                    break;
                case 'delivery': 
                    include 'pages/delivery.php';
                    break;
                case 'promotions': // <--- අලුතින් එකතු කළ කොටස
                    include 'pages/promotions.php';
                    break;
                case 'customers':
                    include 'pages/customers.php';
                    break;
                case 'settings':
                    include 'pages/settings.php';
                    break;
                case 'order_view':
                    include 'pages/order_view.php';
                    break;
                default:
                    include 'pages/dashboard.php';
            }
            ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/admin.js"></script>

</body>
</html>