<?php
$stats = [
    'products' => $conn->query("SELECT COUNT(*) as c FROM products WHERE status = 'active'")->fetch_assoc()['c'] ?? 0,
    'categories' => $conn->query("SELECT COUNT(*) as c FROM categories WHERE status = 'active'")->fetch_assoc()['c'] ?? 0,
    'orders' => $conn->query("SELECT COUNT(*) as c FROM orders")->fetch_assoc()['c'] ?? 0,
    'revenue' => $conn->query("SELECT COALESCE(SUM(total_amount), 0) as t FROM orders WHERE status IN ('shipped','delivered')")->fetch_assoc()['t'] ?? 0,
];
$recent_orders = $conn->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 5");
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-bold">Dashboard</h4>
    <span class="text-muted small">Welcome, <?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?></span>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-primary"><i class="bi bi-box"></i></div>
            <div class="stat-value"><?php echo (int)$stats['products']; ?></div>
            <div class="stat-label">Total Products</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-info"><i class="bi bi-grid"></i></div>
            <div class="stat-value"><?php echo (int)$stats['categories']; ?></div>
            <div class="stat-label">Categories</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-warning"><i class="bi bi-cart"></i></div>
            <div class="stat-value"><?php echo (int)$stats['orders']; ?></div>
            <div class="stat-label">Total Orders</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-danger"><i class="bi bi-currency-exchange"></i></div>
            <div class="stat-value">LKR <?php echo number_format($stats['revenue'], 0); ?></div>
            <div class="stat-label">Revenue</div>
        </div>
    </div>
</div>

<div class="admin-card">
    <div class="card-header">Recent Orders</div>
    <div class="card-body p-0">
        <?php if ($recent_orders && $recent_orders->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $recent_orders->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($row['order_number']); ?></strong></td>
                        <td><?php echo htmlspecialchars($row['customer_name'] ?: $row['customer_email']); ?></td>
                        <td>LKR <?php echo number_format($row['total_amount'], 2); ?></td>
                        <td><span class="badge badge-status bg-<?php echo $row['status'] === 'pending' ? 'warning' : ($row['status'] === 'delivered' ? 'success' : 'info'); ?>"><?php echo ucfirst($row['status']); ?></span></td>
                        <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
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
