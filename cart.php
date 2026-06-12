<?php
$page_title = 'Cart';
require_once 'config.php';
require_once 'includes/header.php';

$cart = $_SESSION['cart'] ?? [];
$cart_items = [];
$total = 0;
$total_weight = 0; // මුළු බර එකතු කිරීමට

if (!empty($cart) && isset($conn)) {
    $ids = array_keys($cart);
    $valid_ids = [];
    foreach ($ids as $id) {
        if ((int)$id > 0) $valid_ids[] = (int)$id;
    }
    
    if (!empty($valid_ids)) {
        $placeholders = implode(',', array_fill(0, count($valid_ids), '?'));
        
        // Database එකේ weight තීරුව ඇත්දැයි ආරක්ෂිතව පරීක්ෂා කිරීම (Crash වීම වැළැක්වීමට)
        $has_weight = false;
        $chk_weight = $conn->query("SHOW COLUMNS FROM products LIKE 'weight'");
        if ($chk_weight && $chk_weight->num_rows > 0) {
            $has_weight = true;
        }

        $query = "SELECT id, name, slug, price, image" . ($has_weight ? ", weight" : "") . " FROM products WHERE id IN ($placeholders)";
        $stmt = $conn->prepare($query);
        
        if ($stmt) {
            $types = str_repeat('i', count($valid_ids));
            $stmt->bind_param($types, ...$valid_ids);
            $stmt->execute();
            $rows = $stmt->get_result();
            
            while ($row = $rows->fetch_assoc()) {
                $qty = (int)$cart[$row['id']];
                $subtotal = $row['price'] * $qty;
                
                $total += $subtotal;
                $weight = $has_weight ? (float)($row['weight'] ?? 0.500) : 0.500; // පෙරනිමිය 500g
                $total_weight += ($weight * $qty);
                
                $cart_items[] = array_merge($row, ['quantity' => $qty, 'subtotal' => $subtotal]);
            }
        } else {
            echo "<div class='container mt-4'><div class='alert alert-danger'>System Update in Progress. Please refresh the page.</div></div>";
        }
    }
}
?>

<section class="py-4 bg-light">
    <div class="container-fluid">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>">Home</a></li>
                <li class="breadcrumb-item active">Cart</li>
            </ol>
        </nav>
    </div>
</section>

<section class="py-5">
    <div class="container-fluid">
        <h1 class="h3 fw-bold mb-4">Shopping Cart</h1>
        <?php if (empty($cart_items)): ?>
        <div class="text-center py-5">
            <i class="bi bi-cart-x display-1 text-muted"></i>
            <p class="mt-3 text-muted">Your cart is empty.</p>
            <a href="shop.php" class="btn btn-primary rounded-pill">Continue Shopping</a>
        </div>
        <?php else: ?>
        <div class="row">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Quantity</th>
                                        <th>Subtotal</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cart_items as $item): ?>
                                    <tr data-id="<?php echo $item['id']; ?>">
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if (!empty($item['image'])): ?>
                                                <img src="<?php echo SITE_URL; ?>/uploads/products/<?php echo htmlspecialchars($item['image']); ?>" alt="" class="rounded me-3" style="width:60px;height:60px;object-fit:cover">
                                                <?php else: ?>
                                                <div class="rounded bg-light d-flex align-items-center justify-content-center me-3" style="width:60px;height:60px"><i class="bi bi-box text-primary"></i></div>
                                                <?php endif; ?>
                                                <div>
                                                    <a href="product.php?slug=<?php echo urlencode($item['slug'] ?? ''); ?>" class="fw-bold text-dark text-decoration-none"><?php echo htmlspecialchars($item['name']); ?></a>
                                                    <div class="small text-muted">Weight: <?php echo number_format($item['weight'] ?? 0.5, 3); ?> kg</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>LKR <?php echo number_format($item['price'], 2); ?></td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm cart-qty" value="<?php echo $item['quantity']; ?>" min="1" style="width:70px" data-id="<?php echo $item['id']; ?>">
                                        </td>
                                        <td class="cart-subtotal fw-bold">LKR <?php echo number_format($item['subtotal'], 2); ?></td>
                                        <td>
                                            <button type="button" class="btn btn-outline-danger btn-sm cart-remove" data-id="<?php echo $item['id']; ?>"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3">Cart Summary</h5>
                        <hr>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal</span>
                            <strong class="cart-total">LKR <?php echo number_format($total, 2); ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-3 text-muted">
                            <span>Estimated Total Weight</span>
                            <span><?php echo number_format($total_weight, 2); ?> kg</span>
                        </div>
                        <div class="alert alert-info py-2 small mb-4 border-0 bg-light text-primary">
                            <i class="bi bi-truck me-1"></i> Delivery fee will be calculated at checkout based on the total weight.
                        </div>
                        <a href="checkout.php" class="btn btn-primary w-100 rounded-pill btn-lg mt-1">Proceed to Checkout</a>
                        <a href="shop.php" class="btn btn-outline-secondary w-100 rounded-pill mt-2">Continue Shopping</a>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function updateCart() {
        window.location.reload(); 
    }
    document.querySelectorAll('.cart-qty').forEach(function(inp) {
        inp.addEventListener('change', function() { 
            var id = this.getAttribute('data-id');
            var qty = parseInt(this.value) || 0;
            fetch('api/cart.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'action=update&product_id=' + id + '&quantity=' + qty })
                .then(function() { updateCart(); });
        });
    });
    document.querySelectorAll('.cart-remove').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.getAttribute('data-id');
            fetch('api/cart.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'action=remove&product_id=' + id })
                .then(function() { updateCart(); });
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
