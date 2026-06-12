<?php
$action = $_GET['action'] ?? 'list';
$edit_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = '';

// අලුත් තැන (top_promo) Database එකට හඳුන්වා දීම (කිසිම Error එකක් නොඑන්න)
$conn->query("ALTER TABLE promotional_banners MODIFY COLUMN display_position ENUM('hero', 'top_promo', 'middle', 'bottom') DEFAULT 'middle'");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $media_type_input = $_POST['media_type'] ?? 'image';
    $link_url = trim($_POST['link_url'] ?? '');
    $display_position = $_POST['display_position'] ?? 'middle';
    $status = $_POST['status'] ?? 'active';

    $media_file = null;
    $media_type = ($media_type_input === 'youtube') ? 'video' : $media_type_input; 

    // YouTube ලින්ක් එකක් නම්
    if ($media_type_input === 'youtube') {
        $yt_link = trim($_POST['youtube_link'] ?? '');
        if (preg_match('/(youtu\.be\/|v=)([^&\?]+)/', $yt_link, $matches)) {
            $vid_id = $matches[2];
            $media_file = 'https://www.youtube.com/embed/' . $vid_id . '?autoplay=1&mute=1&loop=1&playlist=' . $vid_id;
        } else {
            $media_file = $yt_link; 
        }
    } 
    // File එකක් Upload කරනවා නම්
    else {
        if (isset($_FILES['media_file']) && $_FILES['media_file']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['media_file']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'mp4', 'webm'];
            if (in_array($ext, $allowed)) {
                $new_name = time() . '_' . uniqid() . '.' . $ext;
                $upload_dir = '../uploads/promotions/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                if (move_uploaded_file($_FILES['media_file']['tmp_name'], $upload_dir . $new_name)) {
                    $media_file = $new_name;
                }
            } else {
                $message = "Invalid file type!";
            }
        }
    }

    if (empty($message)) {
        if (!empty($_POST['id'])) {
            $id = (int)$_POST['id'];
            if ($media_file) {
                $stmt = $conn->prepare("UPDATE promotional_banners SET title=?, media_type=?, media_file=?, link_url=?, display_position=?, status=? WHERE id=?");
                $stmt->bind_param("ssssssi", $title, $media_type, $media_file, $link_url, $display_position, $status, $id);
            } else {
                $stmt = $conn->prepare("UPDATE promotional_banners SET title=?, media_type=?, link_url=?, display_position=?, status=? WHERE id=?");
                $stmt->bind_param("sssssi", $title, $media_type, $link_url, $display_position, $status, $id);
            }
            if ($stmt->execute()) $message = "Promotion updated successfully!";
        } else {
            if ($media_file) {
                $stmt = $conn->prepare("INSERT INTO promotional_banners (title, media_type, media_file, link_url, display_position, status) VALUES (?,?,?,?,?,?)");
                $stmt->bind_param("ssssss", $title, $media_type, $media_file, $link_url, $display_position, $status);
                if ($stmt->execute()) $message = "Promotion added successfully!";
            } else {
                $message = "Please upload a file or enter a YouTube link!";
            }
        }
    }
}

if (isset($_GET['delete']) && (int)$_GET['delete'] > 0) {
    $conn->query("DELETE FROM promotional_banners WHERE id = " . (int)$_GET['delete']);
    $message = 'Promotion deleted.';
}

$promotions = $conn->query("SELECT * FROM promotional_banners ORDER BY id DESC");
$edit_row = $edit_id ? $conn->query("SELECT * FROM promotional_banners WHERE id = $edit_id")->fetch_assoc() : null;
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-bold">Manage Promotions (Banners & Videos)</h4>
    <a href="index.php?page=promotions&action=add" class="btn btn-primary rounded-pill"><i class="bi bi-plus-lg me-1"></i> Add Promotion</a>
</div>

<?php if ($message): ?>
<div class="alert alert-success border-0 shadow-sm"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if ($action === 'add' || $edit_row): ?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <form method="POST" action="index.php?page=promotions" enctype="multipart/form-data" id="promoForm">
            <?php if ($edit_row): ?><input type="hidden" name="id" value="<?php echo $edit_row['id']; ?>"><?php endif; ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Internal Title</label>
                    <input type="text" name="title" class="form-control" placeholder="E.g. Summer Sale Video" value="<?php echo htmlspecialchars($edit_row['title'] ?? ''); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold text-primary">Display Position *</label>
                    <select name="display_position" class="form-select border-primary" required>
                        <option value="top_promo" <?php echo ($edit_row['display_position'] ?? '') === 'top_promo' ? 'selected' : ''; ?>>Below Hero Slider (Top Promo)</option>
                        <option value="middle" <?php echo ($edit_row['display_position'] ?? '') === 'middle' ? 'selected' : ''; ?>>Middle of Page</option>
                        <option value="bottom" <?php echo ($edit_row['display_position'] ?? '') === 'bottom' ? 'selected' : ''; ?>>Bottom of Page</option>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label fw-bold">Media Type *</label>
                    <select name="media_type" id="mediaTypeSelect" class="form-select" required>
                        <option value="image" <?php echo ($edit_row['media_type'] ?? '') === 'image' ? 'selected' : ''; ?>>Image (JPG, PNG)</option>
                        <option value="youtube" <?php echo (isset($edit_row['media_file']) && strpos($edit_row['media_file'], 'youtube') !== false) ? 'selected' : ''; ?>>YouTube Video Link</option>
                        <option value="video" <?php echo ($edit_row['media_type'] ?? '') === 'video' && (!isset($edit_row['media_file']) || strpos($edit_row['media_file'], 'youtube') === false) ? 'selected' : ''; ?>>Video (MP4 Upload)</option>
                    </select>
                </div>

                <div class="col-md-8" id="fileUploadDiv">
                    <label class="form-label fw-bold">Upload Media File</label>
                    <input type="file" name="media_file" id="mediaFileInput" class="form-control" accept="image/*,video/mp4">
                    <?php if(!empty($edit_row['media_file']) && strpos($edit_row['media_file'], 'youtube') === false): ?>
                        <small class="text-success mt-1 d-block">Current file: <?php echo htmlspecialchars($edit_row['media_file']); ?></small>
                    <?php endif; ?>
                </div>

                <div class="col-md-8" id="youtubeLinkDiv" style="display: none;">
                    <label class="form-label fw-bold text-danger"><i class="bi bi-youtube me-1"></i>YouTube Link *</label>
                    <input type="url" name="youtube_link" id="youtubeLinkInput" class="form-control" placeholder="https://www.youtube.com/watch?v=..." value="<?php echo (isset($edit_row['media_file']) && strpos($edit_row['media_file'], 'youtube') !== false) ? htmlspecialchars($edit_row['media_file']) : ''; ?>">
                </div>

                <div class="col-md-8">
                    <label class="form-label fw-bold">Link URL (Optional) - <small class="text-muted">Where should it go when clicked?</small></label>
                    <input type="text" name="link_url" class="form-control" placeholder="https://computerlk.com/shop.php?category=cables" value="<?php echo htmlspecialchars($edit_row['link_url'] ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Status</label>
                    <select name="status" class="form-select">
                        <option value="active" <?php echo ($edit_row['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo ($edit_row['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-12 mt-4">
                    <button type="submit" class="btn btn-primary px-5 rounded-pill shadow-sm">Save Promotion</button>
                    <a href="index.php?page=promotions" class="btn btn-light ms-2 px-4 rounded-pill">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var typeSelect = document.getElementById('mediaTypeSelect');
    var fileDiv = document.getElementById('fileUploadDiv');
    var ytDiv = document.getElementById('youtubeLinkDiv');
    var fileInput = document.getElementById('mediaFileInput');
    var ytInput = document.getElementById('youtubeLinkInput');

    function toggleFields() {
        if (typeSelect.value === 'youtube') {
            fileDiv.style.display = 'none';
            ytDiv.style.display = 'block';
            fileInput.removeAttribute('required');
            <?php if(!$edit_row): ?> ytInput.setAttribute('required', 'required'); <?php endif; ?>
        } else {
            fileDiv.style.display = 'block';
            ytDiv.style.display = 'none';
            ytInput.removeAttribute('required');
            <?php if(!$edit_row): ?> fileInput.setAttribute('required', 'required'); <?php endif; ?>
        }
    }
    
    typeSelect.addEventListener('change', toggleFields);
    toggleFields(); 

    document.getElementById('promoForm').addEventListener('submit', function() {
        var btn = this.querySelector('button[type="submit"]');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Saving...';
        }
    });
});
</script>
<?php endif; ?>

<div class="card border-0 shadow-sm overflow-hidden">
    <div class="card-body p-0">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
                <tr>
                    <th class="ps-4">Media</th>
                    <th>Details</th>
                    <th>Position</th>
                    <th>Status</th>
                    <th class="pe-4 text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if($promotions && $promotions->num_rows > 0): while ($row = $promotions->fetch_assoc()): ?>
                <tr>
                    <td class="ps-4">
                        <?php if (strpos($row['media_file'], 'youtube') !== false): ?>
                            <span class="badge bg-danger"><i class="bi bi-youtube me-1"></i> YouTube</span>
                        <?php elseif ($row['media_type'] === 'video'): ?>
                            <span class="badge bg-dark"><i class="bi bi-play-circle-fill me-1"></i> Video</span>
                        <?php else: ?>
                            <img src="../uploads/promotions/<?php echo htmlspecialchars($row['media_file']); ?>" class="rounded shadow-sm" style="width: 60px; height: 40px; object-fit: cover;">
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong class="d-block text-dark"><?php echo htmlspecialchars($row['title'] ?: 'Untitled'); ?></strong>
                        <?php if($row['link_url']) echo '<span class="small text-muted"><i class="bi bi-link-45deg"></i> Clickable</span>'; ?>
                    </td>
                    <td>
                        <span class="badge bg-info text-dark text-uppercase"><?php echo $row['display_position'] === 'top_promo' ? 'BELOW HERO' : $row['display_position']; ?></span>
                    </td>
                    <td><span class="badge bg-<?php echo $row['status'] === 'active' ? 'success' : 'secondary'; ?>"><?php echo ucfirst($row['status']); ?></span></td>
                    
                    <td class="pe-4 text-end">
                        <a href="index.php?page=promotions&action=edit&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary shadow-sm"><i class="bi bi-pencil"></i></a>
                        <a href="index.php?page=promotions&delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger ms-1" onclick="return confirm('Delete this promotion?');"><i class="bi bi-trash"></i></a>
                    </td>
                    
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="5" class="text-center py-4 text-muted">No promotions added yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
