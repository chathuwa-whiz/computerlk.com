<?php
// අලුත් Settings Table එක Database එකේ නැත්නම් එය ඉබේම සාදා ගැනීම
$conn->query("CREATE TABLE IF NOT EXISTS site_settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value TEXT
)");

// මූලික (Default) සැකසුම් ඇතුළත් කිරීම (අකුරු සහ Banners 3ක් සඳහා)
$defaults = [
    'site_name' => 'Ecodez Store',
    'theme_color' => '#00a046',
    'logo' => '',
    'layout_structure' => 'default',
    
    'hero_banner_1' => '',
    'hero_1_title' => 'YOUR PERSONAL CLOUD STORAGE',
    'hero_1_subtitle' => 'Premium NAS',
    'hero_1_desc' => 'Store, share and access your data from anywhere. Professional-grade storage solutions.',
    'hero_1_btn_text' => 'Learn More',
    'hero_1_btn_link' => 'shop.php?category=nas',
    
    'hero_banner_2' => '',
    'hero_2_title' => 'UGREEN Magflow Series',
    'hero_2_subtitle' => 'Qi2 25W Wireless Charging',
    'hero_2_desc' => 'Fast wireless charging with MagSafe compatibility. Power up without the cable.',
    'hero_2_btn_text' => 'Shop Now',
    'hero_2_btn_link' => 'shop.php',
    
    'hero_banner_3' => '',
    'hero_3_title' => 'Nexode Retractable Series',
    'hero_3_subtitle' => 'Now Available',
    'hero_3_desc' => 'Compact, portable charging. Retractable cables for a clutter-free life.',
    'hero_3_btn_text' => 'Shop Now',
    'hero_3_btn_link' => 'shop.php'
];

foreach ($defaults as $key => $val) {
    $conn->query("INSERT IGNORE INTO site_settings (setting_key, setting_value) VALUES ('$key', '" . $conn->real_escape_string($val) . "')");
}

$message = '';
$error = '';

// --- මුරපදය (Password) වෙනස් කිරීම ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $uid = (int)$_SESSION['admin_id'];
    
    $r = $conn->query("SELECT password FROM admin_users WHERE id = $uid");
    if ($r && $row = $r->fetch_assoc() && password_verify($current, $row['password'])) {
        if ($new && $new === $confirm && strlen($new) >= 6) {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $conn->query("UPDATE admin_users SET password = '" . $conn->real_escape_string($hash) . "' WHERE id = $uid");
            $message = 'Password updated successfully.';
        } else {
            $error = 'New passwords do not match or are too short (min 6 characters).';
        }
    } else {
        $error = 'Current password is incorrect.';
    }
}

// --- පෙනුම සහ සැකසුම් (Appearance) වෙනස් කිරීම ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_appearance'])) {
    $site_name = $conn->real_escape_string($_POST['site_name'] ?? '');
    $theme_color = $conn->real_escape_string($_POST['theme_color'] ?? '#00a046');
    $layout_structure = $conn->real_escape_string($_POST['layout_structure'] ?? 'default');

    $conn->query("UPDATE site_settings SET setting_value = '$site_name' WHERE setting_key = 'site_name'");
    $conn->query("UPDATE site_settings SET setting_value = '$theme_color' WHERE setting_key = 'theme_color'");
    $conn->query("UPDATE site_settings SET setting_value = '$layout_structure' WHERE setting_key = 'layout_structure'");

    $upload_dir = '../uploads/settings/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

    // Logo Upload
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'svg'])) {
            $logo_name = 'logo_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_dir . $logo_name)) {
                $conn->query("UPDATE site_settings SET setting_value = '$logo_name' WHERE setting_key = 'logo'");
            }
        }
    }

    // Banners සහ අකුරු Upload කිරීම
    for ($i = 1; $i <= 3; $i++) {
        // අකුරු Save කිරීම
        $text_fields = ['title', 'subtitle', 'desc', 'btn_text', 'btn_link'];
        foreach ($text_fields as $f) {
            $field_key = "hero_{$i}_{$f}";
            if (isset($_POST[$field_key])) {
                $val = $conn->real_escape_string($_POST[$field_key]);
                $conn->query("INSERT IGNORE INTO site_settings (setting_key, setting_value) VALUES ('$field_key', '')");
                $conn->query("UPDATE site_settings SET setting_value = '$val' WHERE setting_key = '$field_key'");
            }
        }

        // Media File Save කිරීම
        $media_field = 'hero_banner_' . $i;
        if (isset($_FILES[$media_field]) && $_FILES[$media_field]['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES[$media_field]['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'mp4', 'webm'])) {
                $banner_name = 'banner_' . $i . '_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES[$media_field]['tmp_name'], $upload_dir . $banner_name)) {
                    $conn->query("INSERT IGNORE INTO site_settings (setting_key, setting_value) VALUES ('$media_field', '')");
                    $conn->query("UPDATE site_settings SET setting_value = '$banner_name' WHERE setting_key = '$media_field'");
                }
            }
        }
    }
    
    $message = 'Site Appearance & Banners updated successfully.';
}

// දැනට ඇති සැකසුම් Database එකෙන් ලබා ගැනීම
$settings = [];
$res = $conn->query("SELECT * FROM site_settings");
while ($row = $res->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-bold">Settings & Personalization</h4>
</div>

<?php if ($message): ?>
<div class="alert alert-success alert-dismissible fade show border-0 shadow-sm"><?php echo htmlspecialchars($message); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm"><?php echo htmlspecialchars($error); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<ul class="nav nav-tabs mb-4" id="settingsTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active fw-bold" id="appearance-tab" data-bs-toggle="tab" data-bs-target="#appearance" type="button" role="tab"><i class="bi bi-palette me-2"></i>Appearance & Banners</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-bold" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab"><i class="bi bi-shield-lock me-2"></i>Security</button>
    </li>
</ul>

<div class="tab-content" id="settingsTabsContent">
    
    <div class="tab-pane fade show active" id="appearance" role="tabpanel">
        <form method="POST" action="index.php?page=settings" enctype="multipart/form-data" id="settingsForm">
            <input type="hidden" name="update_appearance" value="1">
            
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 fw-bold text-primary">Website Branding</div>
                <div class="card-body p-4">
                    <div class="row g-4">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Website Name</label>
                            <input type="text" name="site_name" class="form-control" value="<?php echo htmlspecialchars($settings['site_name'] ?? ''); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Theme Primary Color</label>
                            <input type="color" name="theme_color" class="form-control form-control-color w-100" value="<?php echo htmlspecialchars($settings['theme_color'] ?? '#00a046'); ?>" title="Choose your color">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Website Logo</label>
                            <input type="file" name="logo" class="form-control" accept="image/*">
                            <?php if (!empty($settings['logo'])): ?>
                                <div class="mt-2 p-2 bg-light rounded d-inline-block">
                                    <img src="../uploads/settings/<?php echo $settings['logo']; ?>" alt="Logo" style="max-height: 40px;">
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Homepage Structure</label>
                            <select name="layout_structure" class="form-select">
                                <option value="default" <?php echo ($settings['layout_structure'] ?? '') === 'default' ? 'selected' : ''; ?>>Default (Banner + Categories + Products)</option>
                                <option value="products_first" <?php echo ($settings['layout_structure'] ?? '') === 'products_first' ? 'selected' : ''; ?>>Products First (Banner + Latest Products + Categories)</option>
                                <option value="no_banner" <?php echo ($settings['layout_structure'] ?? '') === 'no_banner' ? 'selected' : ''; ?>>No Banner (Categories + Products)</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <h5 class="fw-bold mb-3"><i class="bi bi-images me-2"></i> Hero Slider Banners (Text & Media)</h5>
            <p class="text-muted small mb-4">මෙතැනින් ඔබට Home Page එකේ මුලින්ම පෙන්වන පින්තූර/වීඩියෝ 3 සහ ඒ මත පෙන්වන අකුරු වෙනස් කළ හැක.</p>

            <div class="row g-4">
                <?php for ($i = 1; $i <= 3; $i++): $media_key = 'hero_banner_' . $i; ?>
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm h-100 bg-light">
                        <div class="card-body p-3">
                            <h6 class="fw-bold mb-3 border-bottom pb-2">Slider <?php echo $i; ?></h6>
                            
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Main Title</label>
                                <input type="text" name="hero_<?php echo $i; ?>_title" class="form-control form-control-sm fw-bold" value="<?php echo htmlspecialchars($settings["hero_{$i}_title"] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Subtitle</label>
                                <input type="text" name="hero_<?php echo $i; ?>_subtitle" class="form-control form-control-sm" value="<?php echo htmlspecialchars($settings["hero_{$i}_subtitle"] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Description</label>
                                <textarea name="hero_<?php echo $i; ?>_desc" class="form-control form-control-sm" rows="2"><?php echo htmlspecialchars($settings["hero_{$i}_desc"] ?? ''); ?></textarea>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label small fw-bold">Button Text</label>
                                    <input type="text" name="hero_<?php echo $i; ?>_btn_text" class="form-control form-control-sm" value="<?php echo htmlspecialchars($settings["hero_{$i}_btn_text"] ?? ''); ?>">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-bold">Button Link</label>
                                    <input type="text" name="hero_<?php echo $i; ?>_btn_link" class="form-control form-control-sm" placeholder="shop.php" value="<?php echo htmlspecialchars($settings["hero_{$i}_btn_link"] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="mb-2">
                                <label class="form-label small fw-bold text-primary">Upload Background (Image/Video)</label>
                                <input type="file" name="<?php echo $media_key; ?>" class="form-control form-control-sm border-primary" accept="image/*,video/mp4">
                                <?php if (!empty($settings[$media_key])): ?>
                                    <div class="mt-2 rounded overflow-hidden shadow-sm position-relative">
                                        <?php if (strpos($settings[$media_key], '.mp4') !== false): ?>
                                            <video src="../uploads/settings/<?php echo $settings[$media_key]; ?>" class="w-100" style="height: 100px; object-fit: cover;" autoplay loop muted></video>
                                        <?php else: ?>
                                            <img src="../uploads/settings/<?php echo $settings[$media_key]; ?>" class="w-100" style="height: 100px; object-fit: cover;">
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endfor; ?>
            </div>

            <div class="mt-4 mb-5">
                <button type="submit" class="btn btn-primary btn-lg px-5 rounded-pill shadow-sm"><i class="bi bi-save me-2"></i> Save All Settings</button>
            </div>
        </form>
    </div>

    <div class="tab-pane fade" id="security" role="tabpanel">
        <div class="card border-0 shadow-sm mw-500">
            <div class="card-header bg-white py-3 fw-bold text-danger">Change Admin Password</div>
            <div class="card-body p-4">
                <form method="POST" action="index.php?page=settings">
                    <input type="hidden" name="change_password" value="1">
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-control" required minlength="6">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" required minlength="6">
                    </div>
                    <button type="submit" class="btn btn-danger rounded-pill px-4">Update Password</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('settingsForm').addEventListener('submit', function() {
        var btn = this.querySelector('button[type="submit"]');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Saving...';
        }
    });
});
</script>
