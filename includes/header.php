<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database එකෙන් අලුත් Settings ලබා ගැනීම
$site_settings = [];
if (isset($conn)) {
    $res = $conn->query("SELECT * FROM site_settings");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $site_settings[$row['setting_key']] = $row['setting_value'];
        }
    }
}

// වර්ණ සකසා ගැනීම
$theme_color = $site_settings['theme_color'] ?? '#00a046';
$site_logo = $site_settings['logo'] ?? '';
$site_name_display = $site_settings['site_name'] ?? (defined('SITE_NAME') ? SITE_NAME : 'Ecodez Store');

// Theme එකට ගැලපෙන Darker වර්ණය සෑදීම (Gradients සඳහා)
function adjustBrightness($hex, $steps) {
    $hex = str_replace('#', '', $hex);
    if (strlen($hex) == 3) $hex = str_repeat(substr($hex,0,1), 2).str_repeat(substr($hex,1,1), 2).str_repeat(substr($hex,2,1), 2);
    $return = '#';
    foreach (str_split($hex, 2) as $color) {
        $return .= str_pad(dechex(max(0,min(255,hexdec($color) + $steps))), 2, '0', STR_PAD_LEFT);
    }
    return $return;
}
$secondary_color = adjustBrightness($theme_color, -30);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= $page_title ?? $site_name_display ?> - <?= $site_name_display ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/animations.css">

    <style>
        :root {
            --primary: <?php echo $theme_color; ?> !important;
            --secondary: <?php echo $secondary_color; ?> !important;
            --success: <?php echo $theme_color; ?> !important;
        }
        .text-primary { color: var(--primary) !important; }
        .bg-primary { background-color: var(--primary) !important; }
        .btn-primary { background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%) !important; border: none !important; color: #fff !important; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 20px rgba(0,0,0,0.15); opacity: 0.9; }
        .btn-outline-primary { color: var(--primary) !important; border-color: var(--primary) !important; }
        .btn-outline-primary:hover { background-color: var(--primary) !important; color: #fff !important; }
    </style>
</head>
<body>

    <div class="top-bar bg-dark text-light py-2">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <span class="small">
                        <i class="bi bi-truck me-2"></i> Free Islandwide Delivery
                        <span class="mx-2">|</span>
                        <i class="bi bi-shield-check me-2"></i> 1 Year Warranty
                    </span>
                </div>
                <div class="col-md-6 text-md-end">
                    <span class="small">
                        <i class="bi bi-whatsapp me-2"></i> +94 71 234 5678
                        <span class="mx-2">|</span>
                        <i class="bi bi-geo-alt me-2"></i> Bambalapitiya, Colombo 04
                    </span>
                </div>
            </div>
        </div>
    </div>

    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div class="container-fluid">
           <a class="navbar-brand fw-bold p-0" href="<?php echo SITE_URL; ?>">
    <?php if (!empty($site_logo)): ?>
        <img src="<?php echo SITE_URL; ?>/uploads/settings/<?php echo $site_logo; ?>" alt="<?php echo htmlspecialchars($site_name_display); ?>" style="height: 75px; width: auto; max-width: 280px; object-fit: contain; margin-top: -15px; margin-bottom: -15px;">
    <?php else: ?>
        <i class="bi bi-bag-heart text-primary"></i> <?php echo htmlspecialchars($site_name_display); ?>
    <?php endif; ?>
</a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav ms-4 me-auto">
                    <li class="nav-item"><a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) === 'index.php') ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>">Home</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) === 'shop.php') ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/shop.php">Shop</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) === 'about.php') ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/about.php">About Us</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) === 'contact.php') ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/contact.php">Contact</a></li>
                </ul>

                <form class="d-flex mx-lg-4 position-relative" action="<?php echo SITE_URL; ?>/shop.php" method="GET" style="min-width: 250px;">
                    <input class="form-control rounded-pill ps-3 pe-5 bg-light border-0" type="search" name="q" placeholder="Search products..." value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>">
                    <button class="btn position-absolute end-0 top-0 bottom-0 text-muted" type="submit" style="border-radius: 0 50rem 50rem 0; z-index: 5;">
                        <i class="bi bi-search"></i>
                    </button>
                </form>

                <div class="d-flex align-items-center gap-2 mt-3 mt-lg-0">
                    <a href="<?php echo SITE_URL; ?>/cart.php" class="btn btn-outline-primary position-relative rounded-pill px-3">
                        <i class="bi bi-cart"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger cart-count"><?php echo isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0; ?></span>
                    </a>
                    <?php if (isset($_SESSION['customer_id']) && (string)$_SESSION['customer_id'] !== ''): ?>
                    <div class="dropdown">
                        <button class="btn btn-primary dropdown-toggle rounded-pill px-4" type="button" id="userMenu" data-bs-toggle="dropdown">
                            <i class="bi bi-person me-1"></i> <?php echo htmlspecialchars($_SESSION['customer_name'] ?? 'Account'); ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0" aria-labelledby="userMenu">
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/my-account.php"><i class="bi bi-person me-2"></i>My Account</a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/my-account.php?tab=addresses"><i class="bi bi-geo-alt me-2"></i>Addresses</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?php echo SITE_URL; ?>/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                    <?php else: ?>
                    <a href="<?php echo SITE_URL; ?>/login.php" class="btn btn-primary rounded-pill px-4"><i class="bi bi-person me-1"></i> Login</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>