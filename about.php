<?php
$page_title = 'About Us';
require_once 'config.php';
require_once 'includes/header.php';
?>

<section class="py-4 bg-light">
    <div class="container-fluid">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>">Home</a></li>
                <li class="breadcrumb-item active">About Us</li>
            </ol>
        </nav>
    </div>
</section>

<section class="py-5">
    <div class="container-fluid">
        <div class="row align-items-center g-5">
            <div class="col-lg-6" data-aos="fade-right">
                <h6 class="text-primary fw-bold text-uppercase">About Us</h6>
                <h1 class="display-6 fw-bold mb-4">Your Trusted Tech Accessories Store</h1>
                <p class="lead text-muted"> computerlk.com is the official authorised distributor for premium tech accessories in Sri Lanka. We bring you genuine products with 1-year warranty and islandwide delivery.</p>
                <p class="text-muted">From charging adapters and hubs to power banks, cables, and wireless chargers — we offer a wide range of quality products to power your devices and simplify your life.</p>
                <ul class="list-unstyled">
                    <li class="mb-2"><i class="bi bi-check-circle-fill text-primary me-2"></i> 1 Year warranty on all electronics</li>
                    <li class="mb-2"><i class="bi bi-check-circle-fill text-primary me-2"></i> UGREEN authorised distributor in Sri Lanka</li>
                    <li class="mb-2"><i class="bi bi-check-circle-fill text-primary me-2"></i> KOKO & Mintpay payment options</li>
                    <li class="mb-2"><i class="bi bi-check-circle-fill text-primary me-2"></i> Fast islandwide delivery</li>
                </ul>
            </div>
            <div class="col-lg-6 text-center" data-aos="fade-left">
                <div class="rounded-3 overflow-hidden bg-light d-inline-flex align-items-center justify-content-center" style="width:100%;max-width:400px;height:350px">
                    <i class="bi bi-shop display-1 text-primary opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5 bg-light">
    <div class="container-fluid">
        <div class="text-center mb-5">
            <h2 class="fw-bold">Why Shop With Us</h2>
            <p class="text-muted">Quality, warranty, and service you can trust</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4 text-center">
                <div class="p-4">
                    <i class="bi bi-shield-check display-4 text-primary mb-3"></i>
                    <h5 class="fw-bold">Genuine Products</h5>
                    <p class="text-muted small mb-0">100% authentic with manufacturer warranty</p>
                </div>
            </div>
            <div class="col-md-4 text-center">
                <div class="p-4">
                    <i class="bi bi-truck display-4 text-primary mb-3"></i>
                    <h5 class="fw-bold">Islandwide Delivery</h5>
                    <p class="text-muted small mb-0">Fast and reliable shipping across Sri Lanka</p>
                </div>
            </div>
            <div class="col-md-4 text-center">
                <div class="p-4">
                    <i class="bi bi-headset display-4 text-primary mb-3"></i>
                    <h5 class="fw-bold">Support</h5>
                    <p class="text-muted small mb-0">Friendly customer support when you need it</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
