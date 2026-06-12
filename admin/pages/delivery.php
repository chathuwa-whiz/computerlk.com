<?php
// 1. අවශ්‍ය අලුත් Database Tables සහ Columns ස්වයංක්‍රීයව සාදා ගැනීම
$conn->query("CREATE TABLE IF NOT EXISTS delivery_methods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(100) NOT NULL,
    base_rate DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    extra_rate DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Products table එකට weight එකතු කිරීම (එය දැනටමත් නැත්නම් පමණක්)
$check_weight = $conn->query("SHOW COLUMNS FROM products LIKE 'weight'");
if ($check_weight->num_rows == 0) {
    $conn->query("ALTER TABLE products ADD COLUMN weight DECIMAL(10,3) NOT NULL DEFAULT 0.500 AFTER price");
}

$action = $_GET['action'] ?? 'list';
$edit_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = '';

// දත්ත Save කිරීම (Insert & Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_name = trim($_POST['company_name'] ?? '');
    $base_rate = (float)($_POST['base_rate'] ?? 0);
    $extra_rate = (float)($_POST['extra_rate'] ?? 0);
    $status = $_POST['status'] ?? 'active';

    if ($company_name && $base_rate > 0) {
        if (!empty($_POST['id'])) {
            $id = (int)$_POST['id'];
            $stmt = $conn->prepare("UPDATE delivery_methods SET company_name=?, base_rate=?, extra_rate=?, status=? WHERE id=?");
            $stmt->bind_param("sddsi", $company_name, $base_rate, $extra_rate, $status, $id);
            if ($stmt->execute()) $message = "Delivery method updated successfully!";
        } else {
            $stmt = $conn->prepare("INSERT INTO delivery_methods (company_name, base_rate, extra_rate, status) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sdds", $company_name, $base_rate, $extra_rate, $status);
            if ($stmt->execute()) $message = "Delivery method added successfully!";
        }
    } else {
        $message = "Company name and Base Rate are required!";
    }
}

// දත්ත මකා දැමීම (Delete)
if (isset($_GET['delete']) && (int)$_GET['delete'] > 0) {
    $conn->query("DELETE FROM delivery_methods WHERE id = " . (int)$_GET['delete']);
    $message = 'Delivery method deleted.';
}

$methods = $conn->query("SELECT * FROM delivery_methods ORDER BY id DESC");
$edit_row = $edit_id ? $conn->query("SELECT * FROM delivery_methods WHERE id = $edit_id")->fetch_assoc() : null;
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-bold">Delivery Methods & Rates</h4>
</div>

<?php if ($message): ?>
<div class="alert alert-success alert-dismissible fade show"><?php echo htmlspecialchars($message); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-md-4">
        <div class="admin-card">
            <div class="card-header"><?php echo $edit_row ? 'Edit Courier' : 'Add New Courier'; ?></div>
            <div class="card-body">
                <form method="POST" action="index.php?page=delivery">
                    <?php if ($edit_row): ?><input type="hidden" name="id" value="<?php echo $edit_row['id']; ?>"><?php endif; ?>
                    
                    <div class="mb-3">
                        <label class="form-label">Courier Company Name *</label>
                        <input type="text" name="company_name" class="form-control" required placeholder="e.g. Koombiyo, Prompt, Certis" value="<?php echo htmlspecialchars($edit_row['company_name'] ?? ''); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Base Rate (First 1KG) LKR *</label>
                        <input type="number" name="base_rate" class="form-control" step="0.01" required placeholder="e.g. 350.00" value="<?php echo $edit_row['base_rate'] ?? ''; ?>">
                        <small class="text-muted">මුල් කිලෝ 1 හෝ ඊට අඩු බරක් සඳහා අයකෙරෙන ගාස්තුව</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Extra Rate (Per Additional 1KG) LKR</label>
                        <input type="number" name="extra_rate" class="form-control" step="0.01" placeholder="e.g. 100.00" value="<?php echo $edit_row['extra_rate'] ?? '0'; ?>">
                        <small class="text-muted">කිලෝ 1ට වැඩිවන සෑම අමතර කිලෝවක් සඳහාම එකතුවන ගාස්තුව</small>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="active" <?php echo ($edit_row['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo ($edit_row['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-admin-primary w-100"><i class="bi bi-save me-1"></i> Save Delivery Method</button>
                    <?php if ($edit_row): ?>
                        <a href="index.php?page=delivery" class="btn btn-secondary w-100 mt-2">Cancel Edit</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="admin-card">
            <div class="card-header">Available Couriers</div>
            <div class="card-body p-0">
                <?php if ($methods && $methods->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="admin-table align-middle">
                        <thead>
                            <tr>
                                <th>Company</th>
                                <th>First 1KG Rate</th>
                                <th>Extra 1KG Rate</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $methods->fetch_assoc()): ?>
                            <tr>
                                <td><strong class="text-primary"><?php echo htmlspecialchars($row['company_name']); ?></strong></td>
                                <td class="fw-bold">LKR <?php echo number_format($row['base_rate'], 2); ?></td>
                                <td>LKR <?php echo number_format($row['extra_rate'], 2); ?></td>
                                <td><span class="badge bg-<?php echo $row['status'] === 'active' ? 'success' : 'secondary'; ?>"><?php echo ucfirst($row['status']); ?></span></td>
                                <td>
                                    <a href="index.php?page=delivery&action=edit&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-admin-outline me-1">Edit</a>
                                    <a href="index.php?page=delivery&delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this courier?');">Delete</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="bi bi-truck"></i>
                    <p class="mb-0">No delivery methods added yet.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>