<?php
$action = $_GET['action'] ?? 'list';
$edit_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id = (int)($_POST['category_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    
    // --- Auto Unique Slug Generator ---
    $base_slug = trim($_POST['slug'] ?? '') ?: trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($name)), '-');
    $slug = $base_slug;
    $counter = 1;
    $current_id = !empty($_POST['id']) ? (int)$_POST['id'] : 0;
    
    while(true) {
        $chk = $conn->prepare("SELECT id FROM products WHERE slug = ? AND id != ?");
        $chk->bind_param("si", $slug, $current_id);
        $chk->execute();
        if($chk->get_result()->num_rows === 0) break;
        $slug = $base_slug . '-' . $counter;
        $counter++;
    }

    $description = trim($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $old_price = (!empty($_POST['old_price'])) ? (float)$_POST['old_price'] : null;
    $weight = (float)($_POST['weight'] ?? 0.500); 
    $stock = (int)($_POST['stock'] ?? 10);
    $badge = trim($_POST['badge'] ?? '');
    $status = $_POST['status'] ?? 'active';

    $image_val = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $new_name = time() . '_' . uniqid() . '.' . $ext;
        if (move_uploaded_file($_FILES['image']['tmp_name'], '../uploads/products/' . $new_name)) {
            $image_val = $new_name;
        }
    }

    if ($name && $price > 0) {
        if (!empty($_POST['id'])) {
            $id = (int)$_POST['id'];
            if ($image_val) {
                $stmt = $conn->prepare("UPDATE products SET category_id=?, name=?, slug=?, description=?, price=?, old_price=?, weight=?, stock=?, badge=?, status=?, image=? WHERE id=?");
                $stmt->bind_param("isssdddiissi", $category_id, $name, $slug, $description, $price, $old_price, $weight, $stock, $badge, $status, $image_val, $id);
            } else {
                $stmt = $conn->prepare("UPDATE products SET category_id=?, name=?, slug=?, description=?, price=?, old_price=?, weight=?, stock=?, badge=?, status=? WHERE id=?");
                $stmt->bind_param("isssdddiisi", $category_id, $name, $slug, $description, $price, $old_price, $weight, $stock, $badge, $status, $id);
            }
            $stmt->execute();
            $message = "Product updated successfully!";
        } else {
            $stmt = $conn->prepare("INSERT INTO products (category_id, name, slug, description, price, old_price, weight, stock, badge, status, image) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("isssdddiiss", $category_id, $name, $slug, $description, $price, $old_price, $weight, $stock, $badge, $status, $image_val);
            $stmt->execute();
            $message = "Product added successfully!";
        }
    }
}

if (isset($_GET['delete']) && (int)$_GET['delete'] > 0) {
    $conn->query("DELETE FROM products WHERE id = " . (int)$_GET['delete']);
    $message = 'Product deleted successfully.';
}

$products = $conn->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC");
$categories_list = $conn->query("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name");
$edit_row = $edit_id ? $conn->query("SELECT * FROM products WHERE id = $edit_id")->fetch_assoc() : null;
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-bold">Products</h4>
    <a href="index.php?page=products&action=add" class="btn btn-admin-primary"><i class="bi bi-plus-lg me-1"></i> Add Product</a>
</div>

<?php if ($message): ?>
<div class="alert alert-success alert-dismissible fade show border-0 shadow-sm"><?php echo htmlspecialchars($message); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($action === 'add' || $edit_row): ?>
<div class="admin-card mb-4 border-0 shadow-sm">
    <div class="card-header bg-white py-3 fw-bold text-primary"><?php echo $edit_row ? 'Edit Product' : 'Add Product'; ?></div>
    <div class="card-body p-4">
        <form method="POST" action="index.php?page=products" enctype="multipart/form-data">
            <?php if ($edit_row): ?><input type="hidden" name="id" value="<?php echo $edit_row['id']; ?>"><?php endif; ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($edit_row['name'] ?? ''); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Slug (URL) - <small class="text-success">Auto Generated</small></label>
                    <input type="text" name="slug" class="form-control" placeholder="Leave empty to auto-generate" value="<?php echo htmlspecialchars($edit_row['slug'] ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Category</label>
                    <select name="category_id" class="form-select">
                        <option value="0">-- Select --</option>
                        <?php $categories_list->data_seek(0); while ($c = $categories_list->fetch_assoc()): ?>
                        <option value="<?php echo $c['id']; ?>" <?php echo ($edit_row['category_id'] ?? 0) == $c['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold text-primary">Weight (in KG) *</label>
                    <input type="number" name="weight" class="form-control border-primary" step="0.001" required value="<?php echo $edit_row['weight'] ?? '0.500'; ?>">
                    <small class="text-muted">Ex: 0.500 for 500g</small>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Product Image</label>
                    <input type="file" name="image" class="form-control" accept="image/*">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Price (LKR)</label>
                    <input type="number" name="price" class="form-control" step="0.01" required value="<?php echo $edit_row['price'] ?? ''; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Old Price (LKR)</label>
                    <input type="number" name="old_price" class="form-control" step="0.01" value="<?php echo $edit_row['old_price'] ?? ''; ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Badge</label>
                    <input type="text" name="badge" class="form-control" placeholder="HOT, NEW" value="<?php echo htmlspecialchars($edit_row['badge'] ?? ''); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Stock</label>
                    <input type="number" name="stock" class="form-control" value="<?php echo (int)($edit_row['stock'] ?? 10); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active" <?php echo ($edit_row['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo ($edit_row['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($edit_row['description'] ?? ''); ?></textarea>
                </div>
                <div class="col-12 mt-4">
                    <button type="submit" class="btn btn-admin-primary px-5 shadow-sm"><i class="bi bi-save me-1"></i> Save Product</button>
                    <a href="index.php?page=products" class="btn btn-secondary px-4 ms-2">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="admin-card border-0 shadow-sm">
    <div class="card-header bg-white py-3 fw-bold text-dark">All Products</div>
    <div class="card-body p-0">
        <?php if ($products && $products->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="admin-table align-middle">
                <thead>
                    <tr>
                        <th class="ps-4">Image</th>
                        <th>Name</th>
                        <th>Weight</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th class="pe-4 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $products->fetch_assoc()): ?>
                    <tr>
                        <td class="ps-4">
                            <?php if(!empty($row['image'])): ?>
                                <img src="../uploads/products/<?php echo htmlspecialchars($row['image']); ?>" alt="img" class="rounded shadow-sm" style="width: 45px; height: 45px; object-fit: cover;">
                            <?php else: ?>
                                <div class="bg-light d-flex justify-content-center align-items-center rounded shadow-sm" style="width: 45px; height: 45px;">
                                    <i class="bi bi-image text-muted"></i>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong class="d-block text-dark"><?php echo htmlspecialchars($row['name']); ?></strong>
                            <small class="text-muted"><?php echo htmlspecialchars($row['category_name'] ?? 'Uncategorized'); ?></small>
                        </td>
                        <td><span class="badge bg-info-light text-info border border-info-subtle"><?php echo number_format($row['weight'] ?? 0, 3); ?> KG</span></td>
                        <td>
                            <div class="fw-bold">LKR <?php echo number_format($row['price'], 2); ?></div>
                            <?php if($row['old_price']): ?>
                                <small class="text-muted text-decoration-line-through small">LKR <?php echo number_format($row['old_price'], 2); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $row['stock']; ?></td>
                        <td><span class="badge badge-status bg-<?php echo $row['status'] === 'active' ? 'success' : 'secondary'; ?>"><?php echo ucfirst($row['status']); ?></span></td>
                        <td class="pe-4 text-end">
                            <a href="index.php?page=products&action=edit&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-admin-outline me-1"><i class="bi bi-pencil"></i></a>
                            <a href="index.php?page=products&delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this product?');"><i class="bi bi-trash"></i></a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state text-center py-5">
            <i class="bi bi-box-seam display-1 text-muted opacity-25"></i>
            <p class="mt-3 text-muted">No products. Add one above.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.bg-info-light { background: rgba(13, 202, 240, 0.1); }
</style>
