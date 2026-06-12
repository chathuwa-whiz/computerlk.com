<?php
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
if (!$slug) { header('Location: shop.php'); exit; }

require_once 'config.php';

// --- අලුත් Reviews Table එක ඉබේම සෑදීම ---
$conn->query("CREATE TABLE IF NOT EXISTS product_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    customer_id INT NOT NULL,
    rating INT NOT NULL CHECK(rating >= 1 AND rating <= 5),
    review_text TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// භාණ්ඩයේ විස්තර ලබාගැනීම
$stmt = $conn->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.slug = ? AND p.status = 'active' ORDER BY p.id DESC LIMIT 1");
$stmt->bind_param("s", $slug);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) { header('Location: shop.php'); exit; }
$product_id = (int)$product['id'];

// --- Review එකක් Submit කිරීමේදී ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!empty($_SESSION['customer_id'])) { // ලොග් වී ඇත්නම් පමණක්
        $rating = (int)$_POST['rating'];
        $review_text = trim($_POST['review_text']);
        $customer_id = (int)$_SESSION['customer_id'];
        
        $rev_stmt = $conn->prepare("INSERT INTO product_reviews (product_id, customer_id, rating, review_text) VALUES (?, ?, ?, ?)");
        $rev_stmt->bind_param("iiis", $product_id, $customer_id, $rating, $review_text);
        if ($rev_stmt->execute()) {
            // පිටුව රීෆ්‍රෙශ් කර පණිවිඩයක් පෙන්වීම
            header("Location: product.php?slug=" . urlencode($slug) . "&reviewed=1");
            exit;
        }
    }
}

// --- Ratings සහ Reviews ගණනය කිරීම ---
$rev_stats = $conn->query("SELECT AVG(rating) as avg_rating, COUNT(id) as rev_count FROM product_reviews WHERE product_id = $product_id")->fetch_assoc();
$avg_rating = round($rev_stats['avg_rating'] ?? 0, 1); // සාමාන්‍ය Rating අගය
$rev_count = (int)($rev_stats['rev_count'] ?? 0); // මුළු Reviews ගණන

$page_title = $product['name'] ?? 'Product';
require_once 'includes/header.php';

$img = !empty($product['image']) ? 'uploads/products/' . $product['image'] : null;
?>

<section class="py-4 bg-light">
    <div class="container-fluid">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>">Home</a></li>
                <li class="breadcrumb-item"><a href="shop.php">Shop</a></li>
                <li class="breadcrumb-item active"><?php echo htmlspecialchars($product['name'] ?? ''); ?></li>
            </ol>
        </nav>
    </div>
</section>

<section class="py-5">
    <div class="container-fluid">
        <?php if(isset($_GET['reviewed'])): ?>
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm"><i class="bi bi-check-circle-fill me-2"></i>Thank you! Your review has been submitted. <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <div class="row g-5 mb-5">
            <div class="col-lg-5" data-aos="fade-right">
                <div class="product-image-main rounded-3 overflow-hidden bg-light position-relative border shadow-sm">
                    <?php if ($img): ?>
                    <img src="<?php echo SITE_URL . '/' . $img; ?>" alt="<?php echo htmlspecialchars($product['name'] ?? ''); ?>" class="img-fluid w-100" style="object-fit:cover;">
                    <?php else: ?>
                    <div class="d-flex align-items-center justify-content-center py-5" style="min-height: 400px;"><i class="bi bi-image display-1 text-muted opacity-50"></i></div>
                    <?php endif; ?>
                    <?php if (!empty($product['badge'])): ?>
                    <span class="badge bg-warning position-absolute top-0 start-0 m-3 fs-6 px-3 py-2 text-dark shadow-sm"><?php echo htmlspecialchars($product['badge']); ?></span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="col-lg-7" data-aos="fade-left">
                <small class="text-muted d-block mb-2 text-uppercase fw-bold" style="letter-spacing: 1px;"><i class="bi bi-tag-fill text-primary me-1"></i> <?php echo htmlspecialchars($product['category_name'] ?? 'Product'); ?></small>
                <h1 class="h2 fw-bold mb-3"><?php echo htmlspecialchars($product['name'] ?? ''); ?></h1>
                
                <div class="rating-stars mb-3 d-flex align-items-center">
                    <div class="text-warning fs-5 me-2">
                        <?php 
                        for ($i = 1; $i <= 5; $i++): 
                        ?>
                        <i class="bi bi-star<?php echo $i <= floor($avg_rating) ? '-fill' : ($i - $avg_rating < 1 && $i - $avg_rating > 0 ? '-half' : ''); ?>"></i>
                        <?php endfor; ?>
                    </div>
                    <span class="fw-bold fs-5 me-1"><?php echo $avg_rating > 0 ? $avg_rating : '0'; ?></span>
                    <span class="text-muted small">(<?php echo $rev_count; ?> customer reviews)</span>
                </div>
                
                <div class="mb-4 bg-light p-3 rounded-3 border">
                    <span class="h2 fw-bold text-primary mb-0">LKR <?php echo number_format((float)($product['price'] ?? 0), 2); ?></span>
                    <?php if (!empty($product['old_price'])): ?>
                    <span class="text-muted text-decoration-line-through ms-3 fs-5">LKR <?php echo number_format((float)$product['old_price'], 2); ?></span>
                    <span class="badge bg-danger ms-2">Save LKR <?php echo number_format((float)($product['old_price'] - $product['price']), 2); ?>!</span>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($product['description'])): ?>
                <p class="text-muted mb-4" style="line-height: 1.8; font-size: 1.1rem;"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                <?php endif; ?>
                
                <p class="mb-3">
                    <strong>Availability:</strong> 
                    <?php if((int)($product['stock'] ?? 0) > 0): ?>
                        <span class="text-success fw-bold"><i class="bi bi-check-circle-fill me-1"></i> In Stock (<?php echo $product['stock']; ?> available)</span>
                    <?php else: ?>
                        <span class="text-danger fw-bold"><i class="bi bi-x-circle-fill me-1"></i> Out of Stock</span>
                    <?php endif; ?>
                </p>
                <p class="mb-4"><strong>Weight:</strong> <span class="badge bg-secondary"><?php echo number_format($product['weight'] ?? 0.5, 3); ?> KG</span></p>
                
                <div class="d-flex flex-wrap gap-3 align-items-center mt-4 pt-4 border-top">
                    <div class="input-group quantity-group shadow-sm" style="width: 140px; height: 50px;">
                        <button type="button" class="btn btn-light border qty-minus fs-5 px-3">−</button>
                        <input type="number" class="form-control text-center qty-input border fw-bold fs-5" value="1" min="1" max="<?php echo max(1, (int)($product['stock'] ?? 1)); ?>" readonly>
                        <button type="button" class="btn btn-light border qty-plus fs-5 px-3">+</button>
                    </div>
                    <button type="button" class="btn btn-primary btn-lg rounded-pill px-5 shadow add-to-cart-btn" data-id="<?php echo $product_id; ?>" style="height: 50px;">
                        <i class="bi bi-cart-plus me-2 fs-5"></i> <span class="fw-bold">Add to Cart</span>
                    </button>
                </div>
            </div>
        </div>

        <hr class="my-5">
        <div class="row mt-5" id="reviews">
            <div class="col-12">
                <h3 class="fw-bold mb-4">Customer Reviews</h3>
            </div>
            
            <div class="col-lg-7 mb-4">
                <?php 
                $reviews_query = $conn->query("SELECT r.*, c.name as customer_name FROM product_reviews r LEFT JOIN customers c ON r.customer_id = c.id WHERE r.product_id = $product_id ORDER BY r.id DESC");
                
                if ($reviews_query && $reviews_query->num_rows > 0): 
                    while ($review = $reviews_query->fetch_assoc()):
                ?>
                    <div class="card border-0 border-bottom mb-3 pb-3 bg-transparent rounded-0">
                        <div class="card-body p-0">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="fw-bold mb-0"><i class="bi bi-person-circle text-muted me-2"></i><?php echo htmlspecialchars($review['customer_name'] ?? 'Guest'); ?></h6>
                                <small class="text-muted"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></small>
                            </div>
                            <div class="text-warning small mb-2">
                                <?php for($i=1; $i<=5; $i++): ?>
                                    <i class="bi bi-star<?php echo $i <= $review['rating'] ? '-fill' : ''; ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <p class="text-muted mb-0"><?php echo nl2br(htmlspecialchars($review['review_text'])); ?></p>
                        </div>
                    </div>
                <?php 
                    endwhile; 
                else: 
                ?>
                    <div class="alert alert-light border">No reviews yet. Be the first to review this product!</div>
                <?php endif; ?>
            </div>

            <div class="col-lg-5">
                <div class="card border-0 shadow-sm bg-light rounded-4">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3">Write a Review</h5>
                        <?php if(!empty($_SESSION['customer_id'])): ?>
                            <form method="POST" action="product.php?slug=<?php echo urlencode($slug); ?>#reviews">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Your Rating *</label>
                                    <select name="rating" class="form-select form-select-lg text-warning fw-bold" required>
                                        <option value="5">★★★★★ - Excellent</option>
                                        <option value="4">★★★★☆ - Very Good</option>
                                        <option value="3">★★★☆☆ - Average</option>
                                        <option value="2">★★☆☆☆ - Poor</option>
                                        <option value="1">★☆☆☆☆ - Terrible</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Your Review *</label>
                                    <textarea name="review_text" class="form-control" rows="4" required placeholder="What did you like or dislike?"></textarea>
                                </div>
                                <button type="submit" name="submit_review" class="btn btn-dark w-100 rounded-pill btn-lg">Submit Review</button>
                            </form>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="bi bi-lock fs-1 text-muted mb-3 d-block"></i>
                                <p class="mb-3">You must be logged in to post a review.</p>
                                <a href="login.php" class="btn btn-outline-primary rounded-pill px-4">Login to Review</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var qty = document.querySelector('.qty-input');
    if (qty) {
        document.querySelector('.qty-plus').addEventListener('click', function() { 
            var max = parseInt(qty.getAttribute('max')) || 10;
            if (parseInt(qty.value) < max) qty.value = parseInt(qty.value) + 1; 
        });
        document.querySelector('.qty-minus').addEventListener('click', function() { 
            if (parseInt(qty.value) > 1) qty.value = parseInt(qty.value) - 1; 
        });
    }
    
    var addBtn = document.querySelector('.add-to-cart-btn');
    if (addBtn) {
        addBtn.addEventListener('click', function() {
            var id = this.getAttribute('data-id');
            var qtyEl = document.querySelector('.qty-input');
            var quantity = qtyEl ? parseInt(qtyEl.value) || 1 : 1;
            fetch('api/cart.php', { 
                method: 'POST', 
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, 
                body: 'action=add&product_id=' + id + '&quantity=' + quantity 
            })
            .then(r => r.json()).then(function(data) {
                if (data.success) { 
                    window.location.href = 'cart.php'; 
                } else {
                    alert(data.message || 'Error'); 
                }
            }).catch(function() { alert('Error adding to cart'); });
        });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
