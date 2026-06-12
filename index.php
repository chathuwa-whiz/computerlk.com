<?php
$page_title = 'Home';
require_once 'config.php';

// අලුත් Banners & Videos සඳහා Table එක ඉබේම සෑදීම
$conn->query("CREATE TABLE IF NOT EXISTS promotional_banners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NULL,
    media_type ENUM('image', 'video') DEFAULT 'image',
    media_file VARCHAR(500) NOT NULL,
    link_url VARCHAR(255) NULL,
    display_position ENUM('hero', 'top_promo', 'middle', 'bottom') DEFAULT 'middle',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Active Banners ටික Database එකෙන් ලබා ගැනීම
$banners = ['hero' => [], 'top_promo' => [], 'middle' => [], 'bottom' => []];
$res = $conn->query("SELECT * FROM promotional_banners WHERE status = 'active' ORDER BY id DESC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $banners[$row['display_position']][] = $row;
    }
}

require_once 'includes/header.php';
$layout = $site_settings['layout_structure'] ?? 'default';

// --- පින්තූර/වීඩියෝ පෙන්වන විශේෂ Function එක (YouTube ඇතුළුව) ---
function renderPromoMedia($banner, $heightClass) {
    if ($banner['media_type'] === 'video') {
        if (strpos($banner['media_file'], 'youtube') !== false || strpos($banner['media_file'], 'youtu.be') !== false) {
            $yt_url = $banner['media_file'];
            if (strpos($yt_url, 'autoplay=1') === false) {
                $yt_url .= (strpos($yt_url, '?') !== false ? '&' : '?') . 'autoplay=1&mute=1&controls=0&showinfo=0&rel=0';
            }
            return '<iframe class="w-100 d-block" style="aspect-ratio: 16/9; border: none; pointer-events: none;" src="' . htmlspecialchars($yt_url) . '" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
        } else {
            return '<video class="w-100 d-block object-fit-cover" style="'.$heightClass.';" autoplay loop muted playsinline><source src="uploads/promotions/' . htmlspecialchars($banner['media_file']) . '" type="video/mp4"></video>';
        }
    } else {
        return '<img src="uploads/promotions/' . htmlspecialchars($banner['media_file']) . '" class="w-100 d-block object-fit-cover" style="'.$heightClass.';" alt="Promo">';
    }
}
?>

<?php if(file_exists('components/hero-slider.php')) require_once 'components/hero-slider.php'; ?>

<section class="features-bar-ugreen py-4">
    <div class="container-fluid">
        <div class="row text-center g-3">
            <div class="col-6 col-md-3">
                <i class="bi bi-shield-check text-primary display-6"></i>
                <h6 class="fw-bold mt-2 mb-0">1 Year Warranty</h6>
                <p class="small text-muted mb-0">On all electronics</p>
            </div>
            <div class="col-6 col-md-3">
                <i class="bi bi-shop text-primary display-6"></i>
                <h6 class="fw-bold mt-2 mb-0">Authorised Store</h6>
                <p class="small text-muted mb-0">Original products</p>
            </div>
            <div class="col-6 col-md-3">
                <i class="bi bi-truck text-primary display-6"></i>
                <h6 class="fw-bold mt-2 mb-0">Fast Delivery</h6>
                <p class="small text-muted mb-0">Islandwide shipping</p>
            </div>
            <div class="col-6 col-md-3">
                <i class="bi bi-wallet2 text-primary display-6"></i>
                <h6 class="fw-bold mt-2 mb-0">Flexible Payments</h6>
                <p class="small text-muted mb-0">KOKO, Mintpay & More</p>
            </div>
        </div>
    </div>
</section>

<?php if (!empty($banners['top_promo'])): ?>
<section class="pt-4 pb-2">
    <div class="container-fluid">
        <div class="row g-4 justify-content-center">
            <?php foreach ($banners['top_promo'] as $banner): ?>
            <div class="col-md-<?php echo count($banners['top_promo']) > 1 ? '6' : '12'; ?>" data-aos="fade-up">
                <div class="rounded-4 overflow-hidden shadow-sm position-relative">
                    <?php if ($banner['link_url'] && strpos($banner['media_file'], 'youtube') === false) echo '<a href="' . htmlspecialchars($banner['link_url']) . '">'; ?>
                    
                    <?php echo renderPromoMedia($banner, 'max-height: 400px; min-height: 250px;'); ?>
                    
                    <?php if ($banner['link_url'] && strpos($banner['media_file'], 'youtube') === false) echo '</a>'; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php 
$section_title = 'New Arrivals';
$section_subtitle = 'Discover our latest products';
$products_limit = 8;

if ($layout === 'products_first') {
    if(file_exists('components/product-grid.php')) require_once 'components/product-grid.php';
} else {
    if(file_exists('components/category-showcase.php')) require_once 'components/category-showcase.php';
}
?>

<?php if (!empty($banners['middle'])): ?>
<section class="py-4">
    <div class="container-fluid">
        <div class="row g-4 justify-content-center">
            <?php foreach ($banners['middle'] as $banner): ?>
            <div class="col-md-<?php echo count($banners['middle']) > 1 ? '6' : '12'; ?>" data-aos="fade-up">
                <div class="rounded-4 overflow-hidden shadow-sm position-relative">
                    <?php if ($banner['link_url'] && strpos($banner['media_file'], 'youtube') === false) echo '<a href="' . htmlspecialchars($banner['link_url']) . '">'; ?>
                    
                    <?php echo renderPromoMedia($banner, 'max-height: 400px; min-height: 300px;'); ?>
                    
                    <?php if ($banner['link_url'] && strpos($banner['media_file'], 'youtube') === false) echo '</a>'; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php 
if ($layout === 'products_first') {
    if(file_exists('components/category-showcase.php')) require_once 'components/category-showcase.php';
} else {
    if(file_exists('components/product-grid.php')) require_once 'components/product-grid.php';
}
?>
<?php if (!empty($banners['bottom'])): ?>
<section class="py-5">
    <div class="container">
        <div class="row">
            <?php foreach ($banners['bottom'] as $banner): ?>
            <div class="col-12" data-aos="fade-up">
                <div class="rounded-4 overflow-hidden bottom-promo-card">
                    <?php if ($banner['link_url'] && strpos($banner['media_file'], 'youtube') === false) echo '<a href="' . htmlspecialchars($banner['link_url']) . '">'; ?>
                    
                    <?php echo renderPromoMedia($banner, 'width: 100%; height: auto; display: block;'); ?>
                    
                    <?php if ($banner['link_url'] && strpos($banner['media_file'], 'youtube') === false) echo '</a>'; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<style>
.bottom-promo-card {
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15); /* මූලික ශැඩෝ එක */
    transition: all 0.4s ease;
    border: 1px solid rgba(0,0,0,0.05);
}
.bottom-promo-card:hover {
    transform: translateY(-8px); /* මවුස් එක ගෙනිච්චම උඩට ඉස්සීම */
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25); /* මවුස් එක ගෙනිච්චම ශැඩෝ එක තවත් තද වීම */
}
</style>
<?php endif; ?>

<section class="py-5 why-choose-ugreen">
    <div class="container-fluid">
        <div class="text-center mb-5" data-aos="fade-up">
            <h2 class="fw-bold">Why Choose <?php echo htmlspecialchars($site_name_display); ?></h2>
            <p class="text-muted">We're committed to the best shopping experience</p>
        </div>
        <div class="row g-4">
            <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="0">
                <div class="text-center p-4 h-100 feature-box-ugreen">
                    <div class="feature-icon-circle mx-auto mb-3"><i class="bi bi-shield-check text-white"></i></div>
                    <h5 class="fw-bold mb-2">1 Year Warranty</h5>
                    <p class="text-muted small mb-0">Quality and peace of mind with every purchase.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="100">
                <div class="text-center p-4 h-100 feature-box-ugreen">
                    <div class="feature-icon-circle mx-auto mb-3"><i class="bi bi-shop text-white"></i></div>
                    <h5 class="fw-bold mb-2">Authorised Store</h5>
                    <p class="text-muted small mb-0">100% genuine products.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="200">
                <div class="text-center p-4 h-100 feature-box-ugreen">
                    <div class="feature-icon-circle mx-auto mb-3"><i class="bi bi-truck text-white"></i></div>
                    <h5 class="fw-bold mb-2">Fast Delivery</h5>
                    <p class="text-muted small mb-0">Orders delivered on time, every time.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="300">
                <div class="text-center p-4 h-100 feature-box-ugreen">
                    <div class="feature-icon-circle mx-auto mb-3"><i class="bi bi-wallet2 text-white"></i></div>
                    <h5 class="fw-bold mb-2">Secured Payment</h5>
                    <p class="text-muted small mb-0">Card, bank transfer, COD, KOKO options.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.features-bar-ugreen { background: linear-gradient(to right, #f8f9fa, #f0f2f5); }
.feature-icon-circle { width: 70px; height: 70px; border-radius: 50%; background: linear-gradient(135deg, var(--primary), var(--secondary)); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; transition: transform 0.3s ease; }
.feature-box-ugreen:hover .feature-icon-circle { transform: scale(1.08); }
</style>

<?php require_once 'includes/footer.php'; ?>
