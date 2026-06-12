<?php
$action = $_GET['action'] ?? 'list';
$edit_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '') ?: preg_replace('/[^a-z0-9]+/', '-', strtolower($name));
    $icon = trim($_POST['icon'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'active';

    if ($name) {
        if (!empty($_POST['id'])) {
            $id = (int)$_POST['id'];
            $stmt = $conn->prepare("UPDATE categories SET name=?, slug=?, icon=?, description=?, status=? WHERE id=?");
            $stmt->bind_param("sssssi", $name, $slug, $icon, $description, $status, $id);
            if ($stmt->execute()) $message = 'Category updated successfully.';
        } else {
            $stmt = $conn->prepare("INSERT INTO categories (name, slug, icon, description, status) VALUES (?,?,?,?,?)");
            $stmt->bind_param("sssss", $name, $slug, $icon, $description, $status);
            if ($stmt->execute()) $message = 'Category added successfully.';
        }
    }
}

if (isset($_GET['delete']) && (int)$_GET['delete'] > 0) {
    $conn->query("DELETE FROM categories WHERE id = " . (int)$_GET['delete']);
    $message = 'Category deleted.';
}

$categories = $conn->query("SELECT * FROM categories ORDER BY name");
$edit_row = $edit_id ? $conn->query("SELECT * FROM categories WHERE id = $edit_id")->fetch_assoc() : null;
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-bold">Categories</h4>
    <a href="index.php?page=categories&action=add" class="btn btn-admin-primary"><i class="bi bi-plus-lg me-1"></i> Add Category</a>
</div>

<?php if ($message): ?>
<div class="alert alert-success alert-dismissible fade show"><?php echo htmlspecialchars($message); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($action === 'add' || $edit_row): ?>
<div class="admin-card mb-4">
    <div class="card-header"><?php echo $edit_row ? 'Edit Category' : 'Add Category'; ?></div>
    <div class="card-body">
        <form method="POST" action="index.php?page=categories">
            <?php if ($edit_row): ?><input type="hidden" name="id" value="<?php echo $edit_row['id']; ?>"><?php endif; ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($edit_row['name'] ?? ''); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Slug</label>
                    <input type="text" name="slug" class="form-control" value="<?php echo htmlspecialchars($edit_row['slug'] ?? ''); ?>" placeholder="auto-generated if empty">
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Category Icon</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-primary" id="iconPreview" style="min-width: 45px; justify-content: center;">
                            <i class="bi <?php echo htmlspecialchars($edit_row['icon'] ?? 'bi-box'); ?> fs-5"></i>
                        </span>
                        <input type="text" name="icon" id="iconInput" class="form-control" value="<?php echo htmlspecialchars($edit_row['icon'] ?? ''); ?>" placeholder="e.g. bi-plug" readonly>
                        <button class="btn btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#iconPickerModal">
                            <i class="bi bi-search me-1"></i> Choose Icon
                        </button>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active" <?php echo ($edit_row['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo ($edit_row['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="2"><?php echo htmlspecialchars($edit_row['description'] ?? ''); ?></textarea>
                </div>
                <div class="col-12 mt-4">
                    <button type="submit" class="btn btn-admin-primary px-4">Save Category</button>
                    <a href="index.php?page=categories" class="btn btn-light ms-2">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="admin-card">
    <div class="card-header">All Categories</div>
    <div class="card-body p-0">
        <?php if ($categories && $categories->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Icon</th>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $categories->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td>
                            <div class="bg-light rounded d-inline-flex align-items-center justify-content-center text-primary" style="width: 36px; height: 36px;">
                                <?php echo $row['icon'] ? '<i class="bi ' . htmlspecialchars($row['icon']) . ' fs-5"></i>' : '<i class="bi bi-box fs-5"></i>'; ?>
                            </div>
                        </td>
                        <td class="fw-bold"><?php echo htmlspecialchars($row['name']); ?></td>
                        <td class="text-muted"><?php echo htmlspecialchars($row['slug']); ?></td>
                        <td><span class="badge bg-<?php echo $row['status'] === 'active' ? 'success' : 'secondary'; ?>"><?php echo ucfirst($row['status']); ?></span></td>
                        <td class="text-end pe-4">
                            <a href="index.php?page=categories&action=edit&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary me-1"><i class="bi bi-pencil"></i></a>
                            <a href="index.php?page=categories&delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this category?');"><i class="bi bi-trash"></i></a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state text-center py-5">
            <i class="bi bi-grid display-4 text-muted mb-3 d-block"></i>
            <h5 class="text-muted">No categories found</h5>
            <p class="text-muted mb-0">Start by adding a new category above.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="iconPickerModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable modal-lg">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold">Choose an Icon</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-4 position-relative">
            <i class="bi bi-search position-absolute" style="left: 15px; top: 12px; color: #aaa;"></i>
            <input type="text" id="iconSearchInput" class="form-control form-control-lg bg-light border-0 ps-5" placeholder="Search icons (e.g. phone, plug, laptop)...">
        </div>
        
        <div class="row g-2" id="iconGridContainer">
            </div>
      </div>
    </div>
  </div>
</div>

<style>
.icon-option-box {
    border: 2px solid transparent;
    transition: all 0.2s ease;
    background-color: #f8f9fa;
    color: #495057;
}
.icon-option-box:hover {
    background-color: #e9ecef;
    border-color: var(--bs-primary);
    color: var(--bs-primary);
    transform: translateY(-2px);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Electronics වෙළඳසැලකට ගැලපෙන ජනප්‍රිය අයිකන් ලැයිස්තුව
    const iconsList = [
        'bi-laptop', 'bi-phone', 'bi-tablet', 'bi-pc-display', 'bi-display', 'bi-mouse', 
        'bi-keyboard', 'bi-headphones', 'bi-speaker', 'bi-earbuds', 'bi-boombox',
        'bi-plug', 'bi-usb-plug', 'bi-usb-c', 'bi-usb-drive', 'bi-lightning-charge', 'bi-lightning', 
        'bi-battery-charging', 'bi-battery-full', 'bi-router', 'bi-modem',
        'bi-camera', 'bi-webcam', 'bi-smartwatch', 'bi-watch', 'bi-cpu', 'bi-memory', 
        'bi-motherboard', 'bi-gpu-card', 'bi-hdd', 'bi-sd-card', 'bi-disc', 
        'bi-joystick', 'bi-controller', 'bi-tv', 'bi-printer', 'bi-projector',
        'bi-car-front', 'bi-tools', 'bi-gear', 'bi-box', 'bi-box-seam', 'bi-bag', 
        'bi-cart', 'bi-shop', 'bi-tag', 'bi-award', 'bi-star', 'bi-shield-check',
        'bi-grid', 'bi-mic', 'bi-cable', 'bi-wifi'
    ];

    const iconGrid = document.getElementById('iconGridContainer');
    const searchInput = document.getElementById('iconSearchInput');
    const inputField = document.getElementById('iconInput');
    const previewSpan = document.getElementById('iconPreview');

    // අයිකන් Grid එක හැදීම
    function renderIcons(filterText = '') {
        iconGrid.innerHTML = '';
        const filtered = iconsList.filter(icon => icon.includes(filterText));
        
        if(filtered.length === 0) {
            iconGrid.innerHTML = '<div class="col-12 text-center py-4 text-muted">No icons found.</div>';
            return;
        }

        filtered.forEach(icon => {
            const col = document.createElement('div');
            col.className = 'col-3 col-sm-2 text-center mb-2';
            col.innerHTML = `
                <div class="icon-option-box rounded p-3 cursor-pointer" data-icon="${icon}" title="${icon.replace('bi-', '')}" style="cursor: pointer;">
                    <i class="bi ${icon} fs-3"></i>
                    <div class="small text-muted mt-1" style="font-size: 11px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${icon.replace('bi-', '')}</div>
                </div>
            `;
            iconGrid.appendChild(col);
        });

        // අයිකන් එකක් Select කළාම වෙන දේ
        document.querySelectorAll('.icon-option-box').forEach(box => {
            box.addEventListener('click', function() {
                const selected = this.getAttribute('data-icon');
                
                // Form එකේ අගයන් Update කිරීම
                inputField.value = selected;
                previewSpan.innerHTML = `<i class="bi ${selected} fs-5"></i>`;
                
                // Modal එක වැසීම
                const modalEl = document.getElementById('iconPickerModal');
                const modalInstance = bootstrap.Modal.getInstance(modalEl);
                if (modalInstance) {
                    modalInstance.hide();
                }
            });
        });
    }

    // Search කරද්දී ඉබේම Filter වීම
    if(searchInput) {
        searchInput.addEventListener('input', (e) => {
            renderIcons(e.target.value.toLowerCase().trim());
        });
    }

    // මුලින්ම අයිකන් ටික Load කිරීම
    renderIcons();
});
</script>
