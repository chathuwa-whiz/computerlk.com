<?php
$section_title = $section_title ?? 'New Arrivals';
$section_subtitle = $section_subtitle ?? 'Discover our latest products';
$products_limit = $products_limit ?? 8;
$products = isset($products) ? $products : null;

if (!$products && isset($conn)) {
    $products = $conn->query("
        SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.status = 'active' 
        ORDER BY p.created_at DESC 
        LIMIT " . (int)$products_limit
    );
}

if (!$products) {
    $products = [];
} elseif (!is_array($products)) {
    $list = [];
    while ($row = $products->fetch_assoc()) $list[] = $row;
    $products = $list;
}
?>

<section class="product-grid-section py-5 bg-light overflow-hidden">
    <div class="container-fluid">
        <div class="section-header text-center mb-5" data-aos="fade-up">
            <h6 class="text-primary fw-bold text-uppercase">Our Store</h6>
            <h2 class="display-6 fw-bold"><?php echo htmlspecialchars($section_title); ?></h2>
            <p class="text-muted"><?php echo htmlspecialchars($section_subtitle); ?></p>
        </div>

        <div class="row g-4">
            <?php foreach ($products as $index => $product): 
                $cat_name = $product['category_name'] ?? 'Shop';
                $price = (float)($product['price'] ?? 0);
                $old_price = isset($product['old_price']) && $product['old_price'] ? (float)$product['old_price'] : null;
                $rating = (float)($product['rating'] ?? 4.5);
                $badge = $product['badge'] ?? '';
                $slug = $product['slug'] ?? '';
                $name = $product['name'] ?? '';
                $img = !empty($product['image']) ? 'uploads/products/' . $product['image'] : null;
                
                // පේළියක ඇති අයිටම් 4ක් සඳහා වෙන වෙනම Delay කාලයන් ලබා දීම
                $product_delay = ($index % 4) * 100;
            ?>
            <div class="col-lg-3 col-md-4 col-sm-6" data-aos="fade-up" data-aos-delay="<?php echo $product_delay; ?>">
                <div class="card product-card-ugreen h-100 border-0 shadow-sm">
                    <?php if ($badge): ?>
                    <span class="product-badge-ugreen <?php echo $badge === 'NEW' ? 'bg-success' : ($badge === 'SALE' ? 'bg-danger' : 'bg-warning'); ?>"><?php echo htmlspecialchars($badge); ?></span>
                    <?php endif; ?>
                    
                    <a href="product.php?slug=<?php echo urlencode($slug); ?>" class="text-decoration-none text-dark">
                        <div class="product-img-wrap position-relative overflow-hidden bg-white">
                            <?php if ($img && file_exists($img)): ?>
                            <img src="<?php echo SITE_URL . '/' . $img; ?>" alt="<?php echo htmlspecialchars($name); ?>" class="card-img-top product-img-ugreen" loading="lazy">
                            <?php else: ?>
                            <div class="d-flex align-items-center justify-content-center py-5">
                                <i class="bi bi-box-seam display-4 text-primary opacity-25"></i>
                            </div>
                            <?php endif; ?>
                            
                            <div class="product-actions-ugreen">
                                <span class="btn btn-primary btn-sm rounded-pill px-3 shadow">View Details</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <small class="text-muted d-block mb-1"><?php echo htmlspecialchars($cat_name); ?></small>
                            <h6 class="card-title fw-bold mb-2 text-truncate-2"><?php echo htmlspecialchars($name); ?></h6>
                            <div class="rating-stars mb-2">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="bi bi-star<?php echo $i <= floor($rating) ? '-fill' : ($i - $rating < 1 ? '-half' : ''); ?> text-warning" style="font-size: 0.75rem;"></i>
                                <?php endfor; ?>
                            </div>
                            <div class="d-flex align-items-center flex-wrap gap-2">
                                <span class="fw-bold text-dark">LKR <?php echo number_format($price, 2); ?></span>
                                <?php if ($old_price): ?>
                                <small class="text-muted text-decoration-line-through small">LKR <?php echo number_format($old_price, 2); ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                    <div class="card-footer bg-white border-0 pt-0 pb-3">
                        <button type="button" class="btn btn-outline-primary w-100 rounded-pill btn-sm add-to-cart-btn" data-id="<?php echo (int)($product['id'] ?? 0); ?>">
                            <i class="bi bi-cart-plus me-1"></i> Add to Cart
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if (($show_view_all ?? true) && count($products) >= $products_limit): ?>
        <div class="text-center mt-5" data-aos="fade-up">
            <a href="shop.php" class="btn btn-primary btn-lg rounded-pill px-5 shadow-sm">View All Products <i class="bi bi-arrow-right ms-2"></i></a>
        </div>
        <?php endif; ?>
    </div>
</section>

<style>
.product-card-ugreen { transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1); border-radius: 16px; overflow: hidden; }
.product-card-ugreen:hover { transform: translateY(-10px); box-shadow: 0 30px 60px rgba(0,0,0,0.12) !important; }
.product-badge-ugreen { position: absolute; top: 12px; left: 12px; z-index: 2; padding: 4px 10px; border-radius: 6px; font-size: 10px; font-weight: 700; letter-spacing: 0.5px; }
.product-img-wrap { min-height: 200px; display: flex; align-items: center; justify-content: center; }
.product-img-ugreen { max-height: 220px; object-fit: contain; padding: 15px; transition: transform 0.6s ease; }
.product-card-ugreen:hover .product-img-ugreen { transform: scale(1.1); }
.product-actions-ugreen { position: absolute; inset: 0; background: rgba(255,255,255,0.1); backdrop-filter: blur(2px); display: flex; align-items: center; justify-content: center; opacity: 0; transition: all 0.3s ease; }
.product-card-ugreen:hover .product-actions-ugreen { opacity: 1; }
.text-truncate-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; height: 2.5rem; }
</style>
