<?php
$page_title = 'Shop';
require_once 'config.php';
require_once 'includes/header.php';

$category_slug = isset($_GET['category']) ? trim($_GET['category']) : '';
$search_query = isset($_GET['q']) ? trim($_GET['q']) : ''; // Search පදය ලබාගැනීම
$category = null;

if ($category_slug) {
    $cat = $conn->prepare("SELECT * FROM categories WHERE slug = ? AND status = 'active' LIMIT 1");
    $cat->bind_param("s", $category_slug);
    $cat->execute();
    $category = $cat->get_result()->fetch_assoc();
}

$sql = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.status = 'active'";
$params = [];
$types = "";

if ($category) {
    $sql .= " AND p.category_id = ?";
    $params[] = $category['id'];
    $types .= "i";
}

// Search Query එක Database එකට සම්බන්ධ කිරීම
if ($search_query !== '') {
    $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $like_term = '%' . $search_query . '%';
    $params[] = $like_term;
    $params[] = $like_term;
    $types .= "ss";
}

$sql .= " ORDER BY p.created_at DESC";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $products = $stmt->get_result();
} else {
    $products = $conn->query($sql);
}

$products_list = [];
while ($row = $products->fetch_assoc()) $products_list[] = $row;
?>

<section class="py-4 bg-light">
    <div class="container-fluid">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>">Home</a></li>
                <li class="breadcrumb-item active">
                    <?php 
                    if ($search_query) echo 'Search Results for: "' . htmlspecialchars($search_query) . '"';
                    else echo $category ? htmlspecialchars($category['name']) : 'All Products'; 
                    ?>
                </li>
            </ol>
        </nav>
    </div>
</section>

<section class="py-4">
    <div class="container-fluid">
        <h1 class="h3 fw-bold mb-4">
            <?php 
            if ($search_query) echo 'Search Results for: "' . htmlspecialchars($search_query) . '"';
            else echo $category ? htmlspecialchars($category['name']) : 'All Products'; 
            ?>
        </h1>
        <?php if (empty($products_list)): ?>
        <div class="text-center py-5">
            <i class="bi bi-search display-1 text-muted opacity-50 mb-3"></i>
            <h4 class="text-muted">No products found.</h4>
            <p class="text-muted mb-4">Try searching with a different keyword or check out all products.</p>
            <a href="shop.php" class="btn btn-primary rounded-pill px-4">View All Products</a>
        </div>
        <?php else: ?>
        <div class="row g-4">
            <?php foreach ($products_list as $index => $product): 
                $slug = $product['slug'];
                $name = $product['name'];
                $price = (float)$product['price'];
                $old_price = $product['old_price'] ? (float)$product['old_price'] : null;
                $rating = (float)($product['rating'] ?? 4.5);
                $badge = $product['badge'] ?? '';
                $cat_name = $product['category_name'] ?? 'Shop';
                $img = !empty($product['image']) ? 'uploads/products/' . $product['image'] : null;
            ?>
            <div class="col-6 col-md-4 col-lg-3" data-aos="fade-up" data-aos-delay="<?php echo ($index % 4) * 50; ?>">
                <div class="card product-card-ugreen h-100 border-0 shadow-sm">
                    <?php if ($badge): ?>
                    <span class="product-badge-ugreen <?php echo $badge === 'NEW' ? 'bg-success' : ($badge === 'SALE' ? 'bg-danger' : 'bg-warning'); ?>"><?php echo htmlspecialchars($badge); ?></span>
                    <?php endif; ?>
                    <a href="product.php?slug=<?php echo urlencode($slug); ?>" class="text-decoration-none text-dark">
                        <div class="product-img-wrap position-relative overflow-hidden bg-light">
                            <?php if ($img && file_exists($img)): ?>
                            <img src="<?php echo SITE_URL . '/' . $img; ?>" alt="<?php echo htmlspecialchars($name); ?>" class="card-img-top product-img-ugreen" loading="lazy">
                            <?php else: ?>
                            <div class="d-flex align-items-center justify-content-center py-5">
                                <i class="bi bi-box-seam display-4 text-primary opacity-50"></i>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <small class="text-muted d-block mb-1"><?php echo htmlspecialchars($cat_name); ?></small>
                            <h5 class="card-title fw-bold mb-2 text-truncate-2"><?php echo htmlspecialchars($name); ?></h5>
                            <div class="rating-stars mb-2">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="bi bi-star<?php echo $i <= floor($rating) ? '-fill' : ($i - $rating < 1 ? '-half' : ''); ?> text-warning small"></i>
                                <?php endfor; ?>
                            </div>
                            <div class="d-flex align-items-center flex-wrap gap-2">
                                <span class="fw-bold text-primary">LKR <?php echo number_format($price, 2); ?></span>
                                <?php if ($old_price): ?>
                                <small class="text-muted text-decoration-line-through">LKR <?php echo number_format($old_price, 2); ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                    <div class="card-footer bg-white border-0 pt-0">
                        <a href="product.php?slug=<?php echo urlencode($slug); ?>" class="btn btn-outline-primary btn-sm w-100 rounded-pill me-1 mb-2">View</a>
                        <button type="button" class="btn btn-primary btn-sm w-100 rounded-pill mt-1 add-to-cart-btn" data-id="<?php echo (int)$product['id']; ?>"><i class="bi bi-cart-plus me-1"></i> Add to Cart</button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<style>
.product-card-ugreen { transition: all 0.35s ease; border-radius: 16px; }
.product-card-ugreen:hover { transform: translateY(-6px); box-shadow: 0 20px 40px rgba(0,0,0,0.1) !important; }
.product-badge-ugreen { position: absolute; top: 12px; right: 12px; z-index: 2; padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; }
.product-img-wrap { border-radius: 12px 12px 0 0; min-height: 200px; }
.product-img-ugreen { height: 200px; object-fit: cover; transition: transform 0.4s ease; }
.product-card-ugreen:hover .product-img-ugreen { transform: scale(1.05); }
.text-truncate-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
</style>

<?php require_once 'includes/footer.php'; ?>
