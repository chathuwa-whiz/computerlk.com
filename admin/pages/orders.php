<?php
$orders = $conn->query("SELECT * FROM orders ORDER BY created_at DESC");
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-bold">Orders</h4>
</div>

<div class="admin-card">
    <div class="card-header">All Orders</div>
    <div class="card-body p-0">
        <?php if ($orders && $orders->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Phone</th>
                        <th>Amount</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $orders->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($row['order_number']); ?></strong></td>
                        <td><?php echo htmlspecialchars($row['customer_name']); ?><br><small class="text-muted"><?php echo htmlspecialchars($row['customer_email']); ?></small></td>
                        <td><?php echo htmlspecialchars($row['customer_phone'] ?? '-'); ?></td>
                        <td>LKR <?php echo number_format($row['total_amount'], 2); ?></td>
                        <td><?php echo htmlspecialchars($row['payment_method'] ?? '-'); ?></td>
                        <td>
                            <span class="badge badge-status bg-<?php
                                echo $row['status'] === 'delivered' ? 'success' : ($row['status'] === 'cancelled' ? 'danger' : ($row['status'] === 'shipped' ? 'info' : 'warning'));
                            ?>"><?php echo ucfirst($row['status']); ?></span>
                        </td>
                        <td><?php echo date('M d, Y H:i', strtotime($row['created_at'])); ?></td>
                        <td>
                            <a href="index.php?page=order_view&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-admin-outline">View</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <i class="bi bi-cart-x"></i>
            <p class="mb-0">No orders yet.</p>
        </div>
        <?php endif; ?>
    </div>
</div>
