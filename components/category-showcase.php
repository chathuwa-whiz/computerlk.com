<?php
$categories = isset($conn) ? $conn->query("SELECT * FROM categories WHERE status = 'active' ORDER BY name") : null;
$cat_list = [];
if ($categories) {
    while ($r = $categories->fetch_assoc()) {
        $r['count'] = 0;
        $cat_list[] = $r;
    }
}
// Default categories (Fallback)
if (empty($cat_list)) {
    $cat_list = [
        ['id' => 1, 'name' => 'Charging Adapters', 'slug' => 'charging-adapters', 'icon' => 'lightning-charge', 'description' => 'Fast & reliable'],
        ['id' => 2, 'name' => 'Hubs & Docks', 'slug' => 'hubs-docks', 'icon' => 'usb', 'description' => 'Expand connectivity'],
        ['id' => 3, 'name' => 'Cables', 'slug' => 'cables', 'icon' => 'cable-car', 'description' => 'Durable & fast'],
        ['id' => 4, 'name' => 'Power Banks', 'slug' => 'power-banks', 'icon' => 'battery-full', 'description' => 'Stay powered'],
    ];
}
// නිෂ්පාදන ගණන ලබා ගැනීම
if (isset($conn)) {
    foreach ($cat_list as $i => $c) {
        $id = $c['id'] ?? 0;
        if ($id) {
            $cnt = $conn->query("SELECT COUNT(*) as n FROM products WHERE category_id = $id AND status = 'active'");
            if ($cnt) $cat_list[$i]['count'] = (int)$cnt->fetch_assoc()['n'];
        }
    }
}
?>

<section class="category-showcase-ugreen py-5">
    <div class="container-fluid">
        <div class="section-header text-center mb-5" data-aos="fade-down">
            <h6 class="text-primary fw-bold text-uppercase">Browse</h6>
            <h2 class="display-6 fw-bold">Shop by Category</h2>
            <p class="text-muted">Explore our wide range of premium tech accessories</p>
        </div>

        <div class="row g-4">
            <?php foreach ($cat_list as $index => $c): 
                $icon = $c['icon'] ?? 'box';
                if (strpos($icon, 'bi-') === 0) $icon = str_replace('bi-', '', $icon);
                
                // ඇනිමේෂන් එක ප්‍රමාද කරන කාලය ගණනය කිරීම (එකින් එක ඒමට)
                $delay = ($index % 4) * 150; 
            ?>
            <div class="col-6 col-md-4 col-lg-3" data-aos="zoom-in-up" data-aos-delay="<?php echo $delay; ?>">
                <a href="shop.php?category=<?php echo urlencode($c['slug'] ?? '#'); ?>" class="card category-card-ugreen text-decoration-none h-100 border-0 shadow-sm">
                    <div class="card-body text-center py-4">
                        <div class="category-icon-wrap mb-3 mx-auto">
                            <i class="bi bi-<?php echo htmlspecialchars($icon); ?> text-primary"></i>
                        </div>
                        <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($c['name']); ?></h5>
                        <p class="small text-muted mb-0"><?php echo htmlspecialchars($c['description'] ?? ''); ?></p>
                        <?php if (!empty($c['count'])): ?>
                        <span class="badge bg-primary mt-2"><?php echo (int)$c['count']; ?> products</span>
                        <?php endif; ?>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<style>
.category-card-ugreen { border-radius: 20px; transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1); border: 1px solid rgba(0,0,0,0.03); }
.category-card-ugreen:hover { transform: translateY(-10px); box-shadow: 0 20px 40px rgba(0,160,70,0.15) !important; border-color: var(--primary); }
.category-icon-wrap { width: 70px; height: 70px; border-radius: 50%; background: rgba(0,160,70,0.08); display: flex; align-items: center; justify-content: center; font-size: 1.85rem; transition: all 0.3s ease; }
.category-card-ugreen:hover .category-icon-wrap { transform: rotateY(180deg); background: var(--primary); color: white !important; }
.category-card-ugreen:hover .category-icon-wrap i { color: white !important; }
</style>
