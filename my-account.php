<?php
$page_title = 'My Account';
require_once 'config.php';

if (!isset($_SESSION['customer_id'])) {
    header('Location: login.php?redirect=' . urlencode('my-account.php'));
    exit;
}

$customer_id = (int) $_SESSION['customer_id'];
$tab = $_GET['tab'] ?? 'orders'; // දැන් මුලින්ම ලෝඩ් වෙන්නේ Orders ටැබ් එකයි

// Load customer
$stmt = $conn->prepare("SELECT id, name, email, phone FROM customers WHERE id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();
if (!$customer) {
    session_destroy();
    header('Location: login.php');
    exit;
}

$msg = '';
$err = '';

// ——— Profile update ———
if ($tab === 'profile' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'profile') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    if (empty($name)) {
        $err = 'Name is required.';
    } else {
        $st = $conn->prepare("UPDATE customers SET name = ?, phone = ?, updated_at = NOW() WHERE id = ?");
        $st->bind_param("ssi", $name, $phone, $customer_id);
        if ($st->execute()) {
            $_SESSION['customer_name'] = $name;
            $msg = 'Profile updated successfully.';
            $customer['name'] = $name;
            $customer['phone'] = $phone;
        } else {
            $err = 'Update failed.';
        }
    }
}

// ——— Add address ———
if ($tab === 'addresses' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $label = trim($_POST['label'] ?? '');
    $address_line1 = trim($_POST['address_line1'] ?? '');
    $address_line2 = trim($_POST['address_line2'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $postal_code = trim($_POST['postal_code'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $is_default = isset($_POST['is_default']) ? 1 : 0;
    if (empty($label) || empty($address_line1)) {
        $err = 'Label and address are required.';
    } else {
        if ($is_default) {
            $conn->query("UPDATE customer_addresses SET is_default = 0 WHERE customer_id = " . $customer_id);
        }
        $st = $conn->prepare("INSERT INTO customer_addresses (customer_id, label, address_line1, address_line2, city, postal_code, phone, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $st->bind_param("issssssi", $customer_id, $label, $address_line1, $address_line2, $city, $postal_code, $phone, $is_default);
        if ($st->execute()) {
            $msg = 'Address added.';
            $tab = 'addresses';
        } else {
            $err = 'Could not add address.';
        }
    }
}

// ——— Set default address ———
if ($tab === 'addresses' && isset($_GET['set_default'])) {
    $aid = (int) $_GET['set_default'];
    $chk = $conn->query("SELECT id FROM customer_addresses WHERE customer_id = $customer_id AND id = $aid");
    if ($chk && $chk->num_rows) {
        $conn->query("UPDATE customer_addresses SET is_default = 0 WHERE customer_id = $customer_id");
        $conn->query("UPDATE customer_addresses SET is_default = 1 WHERE id = $aid AND customer_id = $customer_id");
        $msg = 'Default address updated.';
    }
}

// ——— Delete address ———
if ($tab === 'addresses' && isset($_GET['delete'])) {
    $aid = (int) $_GET['delete'];
    $conn->query("DELETE FROM customer_addresses WHERE customer_id = $customer_id AND id = $aid");
    $msg = 'Address removed.';
}

$addresses = [];
$res = $conn->query("SELECT id, label, address_line1, address_line2, city, postal_code, phone, is_default FROM customer_addresses WHERE customer_id = $customer_id ORDER BY is_default DESC, id ASC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $addresses[] = $row;
    }
}

require_once 'includes/header.php';
?>

<section class="py-4 bg-light">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>">Home</a></li>
                <li class="breadcrumb-item active">My Account</li>
            </ol>
        </nav>
    </div>
</section>

<section class="py-4">
    <div class="container">
        <h1 class="h4 fw-bold mb-4">My Account</h1>
        <?php if ($msg): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div>
        <?php endif; ?>
        <?php if ($err): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($err); ?></div>
        <?php endif; ?>

        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link <?php echo $tab === 'orders' ? 'active fw-bold' : ''; ?>" href="my-account.php?tab=orders">My Orders</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $tab === 'profile' ? 'active fw-bold' : ''; ?>" href="my-account.php?tab=profile">Profile</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $tab === 'addresses' ? 'active fw-bold' : ''; ?>" href="my-account.php?tab=addresses">Delivery Addresses</a>
            </li>
        </ul>

        <?php if ($tab === 'orders'): ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h5 class="card-title fw-bold mb-4">My Order History</h5>
                <?php
                $orders_query = $conn->query("SELECT * FROM orders WHERE customer_id = $customer_id ORDER BY id DESC");
                if ($orders_query && $orders_query->num_rows > 0):
                ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="bg-light text-muted small text-uppercase">
                            <tr>
                                <th>Order #</th>
                                <th>Date</th>
                                <th>Total Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($o = $orders_query->fetch_assoc()): ?>
                            <tr>
                                <td class="fw-bold text-primary"><?php echo htmlspecialchars($o['order_number'] ?? ''); ?></td>
                                <td><?php echo date('M d, Y', strtotime($o['created_at'])); ?></td>
                                <td class="fw-bold">LKR <?php echo number_format($o['total_amount'] ?? 0, 2); ?></td>
                                <td>
                                    <?php 
                                    $status = strtolower($o['status'] ?? 'pending');
                                    $badge_class = 'bg-secondary';
                                    if ($status === 'pending' || $status === 'pending_payment') $badge_class = 'bg-warning text-dark';
                                    elseif ($status === 'processing') $badge_class = 'bg-info text-dark';
                                    elseif ($status === 'delivered' || $status === 'completed') $badge_class = 'bg-success';
                                    elseif ($status === 'cancelled') $badge_class = 'bg-danger';
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?> px-3 py-2 rounded-pill"><?php echo ucfirst(str_replace('_', ' ', $status)); ?></span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-bag-x display-1 text-muted opacity-25 d-block mb-3"></i>
                    <p class="mt-3 text-muted fs-5">You haven't placed any orders yet.</p>
                    <a href="shop.php" class="btn btn-primary rounded-pill px-4 mt-2">Start Shopping</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($tab === 'profile'): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Profile</h5>
                <form method="POST" action="my-account.php?tab=profile">
                    <input type="hidden" name="action" value="profile">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($customer['name'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($customer['email']); ?>" disabled>
                        <small class="text-muted">Email cannot be changed here.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>">
                    </div>
                    <button type="submit" class="btn btn-primary rounded-pill">Save Profile</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($tab === 'addresses'): ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h5 class="card-title">Saved delivery addresses</h5>
                <p class="text-muted small">Use these at checkout. Add and manage your delivery addresses below.</p>
                <?php if (empty($addresses)): ?>
                <p class="text-muted mb-0">No addresses yet. Add one below.</p>
                <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($addresses as $a): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-start px-0">
                        <div>
                            <strong><?php echo htmlspecialchars($a['label']); ?></strong>
                            <?php if ($a['is_default']): ?><span class="badge bg-primary ms-2">Default</span><?php endif; ?>
                            <p class="mb-0 small text-muted mt-1">
                                <?php echo htmlspecialchars($a['address_line1']); ?>
                                <?php if ($a['address_line2']): ?>, <?php echo htmlspecialchars($a['address_line2']); ?><?php endif; ?>
                                <?php if ($a['city']): ?>, <?php echo htmlspecialchars($a['city']); ?><?php endif; ?>
                                <?php if ($a['postal_code']): ?> <?php echo htmlspecialchars($a['postal_code']); ?><?php endif; ?>
                                <?php if ($a['phone']): ?> · <?php echo htmlspecialchars($a['phone']); ?><?php endif; ?>
                            </p>
                        </div>
                        <div class="d-flex gap-2">
                            <?php if (!$a['is_default']): ?>
                            <a href="my-account.php?tab=addresses&set_default=<?php echo (int)$a['id']; ?>" class="btn btn-sm btn-outline-primary">Set default</a>
                            <?php endif; ?>
                            <a href="my-account.php?tab=addresses&delete=<?php echo (int)$a['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this address?');">Remove</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Add new address</h5>
                <form method="POST" action="my-account.php?tab=addresses">
                    <input type="hidden" name="action" value="add">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Label (e.g. Home, Office)</label>
                            <input type="text" name="label" class="form-control" required placeholder="Home">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone for this address</label>
                            <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address line 1 *</label>
                        <input type="text" name="address_line1" class="form-control" required placeholder="Street, building, floor">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address line 2 (optional)</label>
                        <input type="text" name="address_line2" class="form-control" placeholder="Landmark, etc.">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">City</label>
                            <input type="text" name="city" class="form-control" placeholder="Colombo">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Postal code</label>
                            <input type="text" name="postal_code" class="form-control" placeholder="10100">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-check">
                            <input type="checkbox" name="is_default" class="form-check-input" value="1" <?php echo empty($addresses) ? 'checked' : ''; ?>>
                            <span class="form-check-label">Set as default delivery address</span>
                        </label>
                    </div>
                    <button type="submit" class="btn btn-primary rounded-pill">Add Address</button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
