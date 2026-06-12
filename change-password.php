<?php
$page_title = 'Change Password';
require_once 'config.php';

if (!isset($_SESSION['customer_id'])) {
    header('Location: login.php?redirect=' . urlencode('change-password.php'));
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $uid = (int)$_SESSION['customer_id'];

    $stmt = $conn->prepare("SELECT password FROM customers WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $has_pass = $row && !empty($row['password']);

    if (empty($new) || empty($confirm)) {
        $error = 'Please fill new password and confirm.';
    } elseif (strlen($new) < 6) {
        $error = 'New password must be at least 6 characters.';
    } elseif ($new !== $confirm) {
        $error = 'New passwords do not match.';
    } elseif ($has_pass && empty($current)) {
        $error = 'Please enter your current password.';
    } elseif ($has_pass && !password_verify($current, $row['password'])) {
        $error = 'Current password is incorrect.';
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $conn->query("UPDATE customers SET password = '" . $conn->real_escape_string($hash) . "' WHERE id = " . $uid);
        unset($_SESSION['customer_id'], $_SESSION['customer_email'], $_SESSION['customer_name']);
        header('Location: login.php?changed=1');
        exit;
    }
}

$has_password = false;
if (isset($_SESSION['customer_id'])) {
    $st = $conn->prepare("SELECT password FROM customers WHERE id = ?");
    $st->bind_param("i", $_SESSION['customer_id']);
    $st->execute();
    $r = $st->get_result()->fetch_assoc();
    $has_password = $r && trim($r['password'] ?? '') !== '';
}

require_once 'includes/header.php';
?>

<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-body p-4 p-md-5">
                        <h2 class="h4 fw-bold mb-4 text-center"><?php echo $has_password ? 'Change Password' : 'Set Password'; ?></h2>
                        <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                        <?php endif; ?>
                        <?php if ($error): ?>
                        <div class="alert alert-warning"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>

                        <form method="POST" action="change-password.php">
                            <?php if ($has_password): ?>
                            <div class="mb-3">
                                <label class="form-label">Current Password</label>
                                <input type="password" name="current_password" class="form-control" required>
                            </div>
                            <?php endif; ?>
                            <div class="mb-3">
                                <label class="form-label"><?php echo $has_password ? 'New' : ''; ?> Password (min 6 characters)</label>
                                <input type="password" name="new_password" class="form-control" required minlength="6">
                            </div>
                            <div class="mb-4">
                                <label class="form-label">Confirm <?php echo $has_password ? 'New' : ''; ?> Password</label>
                                <input type="password" name="confirm_password" class="form-control" required minlength="6">
                            </div>
                            <button type="submit" class="btn btn-primary w-100 rounded-pill py-2"><?php echo $has_password ? 'Update Password' : 'Set Password'; ?></button>
                        </form>
                        <p class="text-center mt-4 mb-0 small"><a href="<?php echo SITE_URL; ?>" class="text-decoration-none">Back to Home</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
