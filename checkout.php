<?php
$page_title = 'Checkout';
require_once 'config.php';

// --- අලුතින් එකතු කළ කොටස: ලොග් වීම අනිවාර්ය කිරීම ---
if (empty($_SESSION['customer_id'])) {
    header('Location: login.php'); 
    exit;
}
// --------------------------------------------------------

// --- PayHere Sandbox Details (මෙතැනට ඔයාගේ Sandbox කේත දාන්න) ---
$payhere_merchant_id = '1234387'; // ඔයාගේ PayHere Sandbox Merchant ID එක
$payhere_secret = 'Mjg1MjI5ODU1OTk0NDU3Nzk2OTc4MDMzNTA1NzQ0NjcwMDgyMA=='; // ඔයාගේ PayHere Sandbox Secret එක
$payhere_currency = 'LKR';
// --------------------------------------------------------

$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS courier_id INT NULL AFTER shipping_address");
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS delivery_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER total_amount");

$cart = $_SESSION['cart'] ?? [];
// Cart එක හිස් නම් සහ Success/Cancel මැසේජ් එකක් නැත්නම් Cart එකට යවයි
if (empty($cart) && !isset($_GET['success']) && !isset($_GET['cancel'])) { 
    header('Location: cart.php'); 
    exit; 
}

$cart_items = [];
$subtotal = 0;
$total_weight = 0;
$ids = array_keys($cart);
$valid_ids = [];
foreach ($ids as $id) {
    if ((int)$id > 0) $valid_ids[] = (int)$id;
}

if (!empty($valid_ids)) {
    $placeholders = implode(',', array_fill(0, count($valid_ids), '?'));
    
    $has_weight = false;
    $chk_weight = $conn->query("SHOW COLUMNS FROM products LIKE 'weight'");
    if ($chk_weight && $chk_weight->num_rows > 0) {
        $has_weight = true;
    }

    $query = "SELECT id, name, price" . ($has_weight ? ", weight" : "") . " FROM products WHERE id IN ($placeholders)";
    $stmt = $conn->prepare($query);
    
    if ($stmt) {
        $types = str_repeat('i', count($valid_ids));
        $stmt->bind_param($types, ...$valid_ids);
        $stmt->execute();
        $rows = $stmt->get_result();
        while ($row = $rows->fetch_assoc()) {
            $qty = (int)$cart[$row['id']];
            $cart_items[] = array_merge($row, ['quantity' => $qty, 'subtotal' => $row['price'] * $qty]);
            $subtotal += $row['price'] * $qty;
            $weight = $has_weight ? (float)($row['weight'] ?? 0.500) : 0.500;
            $total_weight += ($weight * $qty);
        }
    }
}

// Couriers සහ ගාස්තු ගණනය කිරීම
$couriers = [];
$res = $conn->query("SELECT * FROM delivery_methods WHERE status = 'active' ORDER BY base_rate ASC");
if ($res) {
    while ($c = $res->fetch_assoc()) {
        $fee = (float)$c['base_rate'];
        if ($total_weight > 1) {
            $extra_kilos = ceil($total_weight - 1);
            $fee += ($extra_kilos * (float)$c['extra_rate']);
        }
        $c['calculated_fee'] = $fee;
        $couriers[] = $c;
    }
}

$order_placed = false;
$redirect_to_payment = false;
$order_number = '';

$customer = null;
$saved_addresses = [];
if (!empty($_SESSION['customer_id'])) {
    $cid = (int) $_SESSION['customer_id'];
    $st = $conn->prepare("SELECT id, name, email, phone FROM customers WHERE id = ?");
    $st->bind_param("i", $cid);
    $st->execute();
    $customer = $st->get_result()->fetch_assoc();
    $res = $conn->query("SELECT id, label, address_line1, address_line2, city, postal_code, phone FROM customer_addresses WHERE customer_id = $cid ORDER BY is_default DESC, id ASC");
    if ($res) while ($row = $res->fetch_assoc()) { $saved_addresses[] = $row; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['customer_name'] ?? '');
    $email = trim($_POST['customer_email'] ?? '');
    $phone = trim($_POST['customer_phone'] ?? '');
    $address = trim($_POST['shipping_address'] ?? '');
    $payment = trim($_POST['payment_method'] ?? 'cod');
    $courier_id = isset($_POST['courier_id']) ? (int)$_POST['courier_id'] : null;
    
    $delivery_fee = 0;
    if ($courier_id) {
        $c_chk = $conn->query("SELECT base_rate, extra_rate FROM delivery_methods WHERE id = $courier_id");
        if ($c_chk && $c_row = $c_chk->fetch_assoc()) {
            $delivery_fee = (float)$c_row['base_rate'];
            if ($total_weight > 1) {
                $delivery_fee += (ceil($total_weight - 1) * (float)$c_row['extra_rate']);
            }
        }
    }
    
    $grand_total = $subtotal + $delivery_fee;

    if ($name && $email && $address) {
        $order_number = 'EC' . date('Ymd') . rand(1000, 9999);
        $customer_id_val = !empty($_SESSION['customer_id']) ? (int)$_SESSION['customer_id'] : null;
        
        $stmt = $conn->prepare("INSERT INTO orders (order_number, customer_id, customer_name, customer_email, customer_phone, shipping_address, courier_id, delivery_fee, total_amount, payment_method, status) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
        $status = in_array($payment, ['payhere', 'koko', 'card'], true) ? 'pending_payment' : 'pending';
        
        $stmt->bind_param("sissssiddds", $order_number, $customer_id_val, $name, $email, $phone, $address, $courier_id, $delivery_fee, $grand_total, $payment, $status);
        
        if ($stmt->execute()) {
            $order_id = $conn->insert_id;
            foreach ($cart_items as $item) {
                $conn->query("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES ($order_id, " . (int)$item['id'] . ", " . (int)$item['quantity'] . ", " . (float)$item['price'] . ")");
            }
            // POS sync: reserve website stock + queue deduction events for the POS
            require_once __DIR__ . '/api/pos/hooks.php';
            pos_sync_order_placed($conn, $order_id);
            $_SESSION['cart'] = []; // Cart එක හිස් කිරීම
            
            // Payment Gateway තෝරා ඇත්නම්
            if (in_array($payment, ['payhere', 'koko', 'card'], true)) {
                $redirect_to_payment = true;
                
                // Security Hash එක සෑදීම
                $amount_formatted = number_format($grand_total, 2, '.', '');
                $hash = strtoupper(
                    md5(
                        $payhere_merchant_id . 
                        $order_number . 
                        $amount_formatted . 
                        $payhere_currency . 
                        strtoupper(md5($payhere_secret))
                    )
                );
            } else {
                $order_placed = true;
            }
        }
    }
}

require_once 'includes/header.php';
?>

<section class="py-4 bg-light">
    <div class="container-fluid">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>">Home</a></li>
                <li class="breadcrumb-item"><a href="cart.php">Cart</a></li>
                <li class="breadcrumb-item active">Checkout</li>
            </ol>
        </nav>
    </div>
</section>

<?php if ($redirect_to_payment): ?>
    <section class="py-5 text-center" style="min-height: 50vh; display: flex; flex-direction: column; justify-content: center;">
        <div class="container-fluid">
            <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                <span class="visually-hidden">Loading...</span>
            </div>
            <h3 class="fw-bold">Redirecting to Secure Payment...</h3>
            <p class="text-muted mb-4">Please wait while we transfer you to the payment gateway. Do not close or refresh this page.</p>
            
            <form id="payhere-form" method="post" action="https://sandbox.payhere.lk/pay/checkout">   
                <input type="hidden" name="merchant_id" value="<?php echo $payhere_merchant_id; ?>">
                <input type="hidden" name="return_url" value="<?php echo SITE_URL; ?>/checkout.php?success=1">
                <input type="hidden" name="cancel_url" value="<?php echo SITE_URL; ?>/checkout.php?cancel=1">
                <input type="hidden" name="notify_url" value="<?php echo SITE_URL; ?>/payhere_notify.php">  
                <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order_number); ?>">
                <input type="hidden" name="items" value="Order <?php echo htmlspecialchars($order_number); ?>">
                <input type="hidden" name="currency" value="<?php echo $payhere_currency; ?>">
                <input type="hidden" name="amount" value="<?php echo $amount_formatted; ?>">  
                <input type="hidden" name="first_name" value="<?php echo htmlspecialchars($name); ?>">
                <input type="hidden" name="last_name" value="">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                <input type="hidden" name="phone" value="<?php echo htmlspecialchars($phone); ?>">
                <input type="hidden" name="address" value="<?php echo htmlspecialchars($address); ?>">
                <input type="hidden" name="city" value="Sri Lanka">
                <input type="hidden" name="country" value="Sri Lanka">
                <input type="hidden" name="hash" value="<?php echo $hash; ?>">
            </form>
            <script>
                setTimeout(function() {
                    document.getElementById('payhere-form').submit();
                }, 2000); // තත්පර 2කින් ඉබේම Submit වේ
            </script>
        </div>
    </section>

<?php elseif ($order_placed || isset($_GET['success'])): ?>
    <section class="py-5">
        <div class="container-fluid text-center">
            <i class="bi bi-check-circle-fill text-success display-1"></i>
            <h2 class="mt-3 fw-bold">Order Received!</h2>
            <p class="text-muted">Your order has been placed successfully. We will process it shortly.</p>
            <a href="shop.php" class="btn btn-primary rounded-pill mt-3 px-5">Continue Shopping</a>
        </div>
    </section>

<?php elseif (isset($_GET['cancel'])): ?>
    <section class="py-5">
        <div class="container-fluid text-center">
            <i class="bi bi-x-circle-fill text-danger display-1"></i>
            <h2 class="mt-3 fw-bold">Payment Cancelled</h2>
            <p class="text-muted">Your payment was not completed. Your order is on hold.</p>
            <a href="shop.php" class="btn btn-primary rounded-pill mt-3 px-5">Go to Shop</a>
        </div>
    </section>

<?php else: ?>
    <section class="py-5">
        <div class="container-fluid">
            <h1 class="h3 fw-bold mb-4">Checkout</h1>
            <form method="POST" action="checkout.php">
                <div class="row">
                    <div class="col-lg-7 mb-4">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white py-3"><h5 class="mb-0 fw-bold">Contact & Shipping Details</h5></div>
                            <div class="card-body">
                                <?php
                                $pre_name = $_POST['customer_name'] ?? ($customer['name'] ?? '');
                                $pre_email = $_POST['customer_email'] ?? ($customer['email'] ?? '');
                                $pre_phone = $_POST['customer_phone'] ?? ($customer['phone'] ?? '');
                                $pre_address = $_POST['shipping_address'] ?? '';
                                ?>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Full Name *</label>
                                        <input type="text" name="customer_name" class="form-control" required value="<?php echo htmlspecialchars($pre_name); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Phone Number *</label>
                                        <input type="text" name="customer_phone" class="form-control" required value="<?php echo htmlspecialchars($pre_phone); ?>">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Email Address *</label>
                                        <input type="email" name="customer_email" class="form-control" required value="<?php echo htmlspecialchars($pre_email); ?>">
                                    </div>
                                    <div class="col-12 mt-4">
                                        <label class="form-label">Delivery Address *</label>
                                        <?php if (!empty($saved_addresses)): ?>
                                        <select id="savedAddressSelect" class="form-select mb-2 bg-light">
                                            <option value="">— Choose a saved address —</option>
                                            <?php foreach ($saved_addresses as $a):
                                                $full = $a['address_line1'];
                                                if (!empty($a['address_line2'])) $full .= ', ' . $a['address_line2'];
                                                if (!empty($a['city'])) $full .= ', ' . $a['city'];
                                                if (!empty($a['postal_code'])) $full .= ' ' . $a['postal_code'];
                                            ?>
                                            <option value="<?php echo htmlspecialchars($full); ?>" data-phone="<?php echo htmlspecialchars($a['phone'] ?? ''); ?>"><?php echo htmlspecialchars($a['label']); ?>: <?php echo htmlspecialchars($full); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php endif; ?>
                                        <textarea name="shipping_address" id="shipping_address" class="form-control" rows="3" required placeholder="Street, area, city, postal code"><?php echo htmlspecialchars($pre_address); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white py-3">
                                <h5 class="mb-0 fw-bold">Delivery Method</h5>
                                <small class="text-muted">Total parcel weight: <span class="badge bg-secondary"><?php echo number_format($total_weight, 2); ?> kg</span></small>
                            </div>
                            <div class="card-body">
                                <?php if (empty($couriers)): ?>
                                    <div class="alert alert-warning mb-0">No delivery methods available right now. Please contact support.</div>
                                <?php else: ?>
                                    <div class="row g-3">
                                        <?php foreach ($couriers as $i => $c): ?>
                                        <div class="col-12">
                                            <div class="form-check border rounded-3 p-3 courier-card <?php echo $i === 0 ? 'border-primary bg-light' : ''; ?>">
                                                <input class="form-check-input ms-1 mt-2 courier-radio" type="radio" name="courier_id" id="courier_<?php echo $c['id']; ?>" value="<?php echo $c['id']; ?>" data-fee="<?php echo $c['calculated_fee']; ?>" <?php echo $i === 0 ? 'checked' : ''; ?> required>
                                                <label class="form-check-label w-100 ms-3 cursor-pointer" for="courier_<?php echo $c['id']; ?>">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-truck text-primary me-2"></i> <?php echo htmlspecialchars($c['company_name']); ?></h6>
                                                            <small class="text-muted">Standard Delivery</small>
                                                        </div>
                                                        <h5 class="mb-0 fw-bold text-primary">LKR <?php echo number_format($c['calculated_fee'], 2); ?></h5>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white"><h5 class="mb-0 fw-bold">Payment Method</h5></div>
                            <div class="card-body">
                                <select name="payment_method" class="form-select form-select-lg border-primary shadow-sm">
                                    <option value="cod">Cash on Delivery (COD)</option>
                                    <option value="bank">Bank Transfer</option>
                                    <option value="payhere">PayHere (Visa, MasterCard, Frimi, EZ Cash...)</option>
                                    <option value="koko">Koko (Pay in 3 Installments)</option>
                                    <option value="card">Credit / Debit Card</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="card border-0 shadow-sm sticky-top" style="top: 100px;">
                            <div class="card-header bg-white"><h5 class="mb-0 fw-bold">Order Summary</h5></div>
                            <div class="card-body">
                                <ul class="list-unstyled mb-4">
                                    <?php foreach ($cart_items as $item): ?>
                                    <li class="d-flex justify-content-between py-2 border-bottom">
                                        <div class="text-truncate pe-3" style="max-width: 70%;">
                                            <span class="text-muted"><?php echo $item['quantity']; ?> ×</span> <?php echo htmlspecialchars($item['name']); ?>
                                        </div>
                                        <span class="fw-medium">LKR <?php echo number_format($item['subtotal'], 2); ?></span>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                                
                                <div class="d-flex justify-content-between text-muted mb-2">
                                    <span>Subtotal</span>
                                    <span>LKR <?php echo number_format($subtotal, 2); ?></span>
                                </div>
                                <div class="d-flex justify-content-between text-muted mb-3">
                                    <span>Delivery Fee</span>
                                    <span id="display-delivery-fee" class="fw-bold text-dark">LKR 0.00</span>
                                </div>
                                
                                <hr class="my-3">
                                
                                <div class="d-flex justify-content-between align-items-center fw-bold">
                                    <span>Grand Total</span>
                                    <span id="display-grand-total" class="h4 mb-0 fw-bold text-primary">LKR <?php echo number_format($subtotal, 2); ?></span>
                                </div>
                                
                                <button type="submit" class="btn btn-primary w-100 rounded-pill btn-lg mt-4 shadow-sm fw-bold">Place Order <i class="bi bi-shield-lock ms-2"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const courierRadios = document.querySelectorAll('.courier-radio');
        const courierCards = document.querySelectorAll('.courier-card');
        const displayFee = document.getElementById('display-delivery-fee');
        const displayGrandTotal = document.getElementById('display-grand-total');
        const subtotal = <?php echo $subtotal; ?>;

        function updateTotals() {
            let selectedRadio = document.querySelector('.courier-radio:checked');
            let fee = 0;
            
            courierCards.forEach(card => {
                card.classList.remove('border-primary', 'bg-light');
            });

            if (selectedRadio) {
                fee = parseFloat(selectedRadio.getAttribute('data-fee')) || 0;
                selectedRadio.closest('.courier-card').classList.add('border-primary', 'bg-light');
            }
            
            let grandTotal = subtotal + fee;

            if(displayFee) displayFee.textContent = 'LKR ' + fee.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            if(displayGrandTotal) displayGrandTotal.textContent = 'LKR ' + grandTotal.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }

        courierRadios.forEach(radio => {
            radio.addEventListener('change', updateTotals);
        });

        const addressSelect = document.getElementById('savedAddressSelect');
        if(addressSelect) {
            addressSelect.addEventListener('change', function() {
                var opt = this.options[this.selectedIndex];
                if (opt.value) {
                    document.getElementById('shipping_address').value = opt.value;
                    var phone = opt.getAttribute('data-phone');
                    if (phone) document.querySelector('input[name="customer_phone"]').value = phone;
                }
            });
        }

        updateTotals();
    });
    </script>

    <style>
    .cursor-pointer { cursor: pointer; }
    .courier-card { transition: all 0.2s ease; border: 2px solid #dee2e6; }
    .courier-card:hover { border-color: var(--primary) !important; }
    </style>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
