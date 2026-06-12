<?php
$customers = $conn->query("
    SELECT customer_name as name, customer_email as email, customer_phone as phone, COUNT(*) as order_count, SUM(total_amount) as total_spent
    FROM orders
    WHERE customer_email IS NOT NULL AND customer_email != ''
    GROUP BY customer_email, customer_name, customer_phone
    ORDER BY total_spent DESC
");
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-bold">Customers</h4>
</div>

<div class="admin-card">
    <div class="card-header">Customers (from orders)</div>
    <div class="card-body p-0">
        <?php if ($customers && $customers->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Orders</th>
                        <th>Total Spent</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $customers->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td><?php echo htmlspecialchars($row['phone'] ?? '-'); ?></td>
                        <td><?php echo (int)$row['order_count']; ?></td>
                        <td>LKR <?php echo number_format($row['total_spent'], 2); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <i class="bi bi-people"></i>
            <p class="mb-0">No customer data yet (customers appear after orders).</p>
        </div>
        <?php endif; ?>
    </div>
</div>
