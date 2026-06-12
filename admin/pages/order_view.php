<?php
$id = (int)($_GET['id'] ?? 0);
if (!$id) { echo '<p>Invalid order.</p>'; return; }

$message = '';

// Handle Order Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    $old_row = $conn->query("SELECT status FROM orders WHERE id = $id")->fetch_assoc();
    $old_status = $old_row['status'] ?? '';
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $id);
    if ($stmt->execute()) {
        $message = 'Order status updated successfully!';
        // POS sync: cancellation restocks website + queues restock events for the POS
        require_once dirname(__DIR__, 2) . '/api/pos/hooks.php';
        pos_sync_order_status_changed($conn, $id, $old_status, $new_status);
    }
}

$order = $conn->query("SELECT * FROM orders WHERE id = $id")->fetch_assoc();
if (!$order) { echo '<p>Order not found.</p>'; return; }
$items = $conn->query("SELECT oi.*, p.name as product_name FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id = $id");
?>

<div class="mb-4">
    <a href="index.php?page=orders" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back to Orders</a>
</div>

<?php if ($message): ?>
<div class="alert alert-success alert-dismissible fade show"><?php echo htmlspecialchars($message); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="admin-card mb-4">
    <div class="card-header">Order #<?php echo htmlspecialchars($order['order_number']); ?></div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p class="mb-1"><strong>Customer:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($order['customer_email']); ?></p>
                <p class="mb-1"><strong>Phone:</strong> <?php echo htmlspecialchars($order['customer_phone'] ?? '-'); ?></p>
                <p class="mb-0"><strong>Address:</strong> <?php echo nl2br(htmlspecialchars($order['shipping_address'] ?? '-')); ?></p>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <strong>Status:</strong> 
                    <span class="badge badge-status bg-<?php echo $order['status'] === 'delivered' ? 'success' : ($order['status'] === 'cancelled' ? 'danger' : ($order['status'] === 'shipped' ? 'info' : 'warning')); ?> ms-1">
                        <?php echo ucfirst($order['status']); ?>
                    </span>
                    
                    <form method="POST" class="d-flex align-items-center mt-2">
                        <input type="hidden" name="update_status" value="1">
                        <select name="status" class="form-select form-select-sm w-auto me-2">
                            <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="pending_payment" <?php echo $order['status'] == 'pending_payment' ? 'selected' : ''; ?>>Pending Payment</option>
                            <option value="processing" <?php echo $order['status'] == 'processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="shipped" <?php echo $order['status'] == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                            <option value="delivered" <?php echo $order['status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                            <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                        <button type="submit" class="btn btn-sm btn-admin-primary">Update</button>
                    </form>
                </div>
                
                <p class="mb-1"><strong>Payment:</strong> <?php echo htmlspecialchars($order['payment_method'] ?? '-'); ?></p>
                <p class="mb-0"><strong>Date:</strong> <?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></p>
            </div>
        </div>
    </div>
</div>

<div class="admin-card">
    <div class="card-header">Order Items</div>
    <div class="card-body p-0">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Qty</th>
                    <th>Price</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($item = $items->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['product_name'] ?? 'Product #' . $item['product_id']); ?></td>
                    <td><?php echo $item['quantity']; ?></td>
                    <td>LKR <?php echo number_format($item['price'], 2); ?></td>
                    <td>LKR <?php echo number_format($item['quantity'] * $item['price'], 2); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <div class="p-3 text-end border-top">
            <strong>Total: LKR <?php echo number_format($order['total_amount'], 2); ?></strong>
        </div>
    </div>
</div>
