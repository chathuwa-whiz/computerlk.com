<?php
$page_title = 'Reset Password';
require_once 'config.php';

if (isset($_SESSION['customer_id'])) {
    header('Location: ' . SITE_URL);
    exit;
}

$token = trim($_GET['token'] ?? '');
$error = '';
$success = '';

if (empty($token)) {
    $error = 'Invalid or missing reset link.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    if (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $stmt = $conn->prepare("SELECT email FROM password_resets WHERE token = ? AND expires_at > NOW() LIMIT 1");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!$row) {
            $error = 'This link has expired or is invalid. Please request a new reset link.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $email = $row['email'];
            $conn->query("UPDATE customers SET password = '" . $conn->real_escape_string($hash) . "' WHERE email = '" . $conn->real_escape_string($email) . "'");
            $conn->query("DELETE FROM password_resets WHERE token = '" . $conn->real_escape_string($token) . "'");
            header('Location: login.php?reset=1');
            exit;
        }
    }
} else {
    $stmt = $conn->prepare("SELECT email FROM password_resets WHERE token = ? AND expires_at > NOW() LIMIT 1");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    if (!$stmt->get_result()->fetch_assoc()) {
        $error = 'This link has expired or is invalid. Please request a new reset link from the forgot password page.';
    }
}

require_once 'includes/header.php';
?>

<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-body p-4 p-md-5">
                        <h2 class="h4 fw-bold mb-4 text-center">Set New Password</h2>
                        <?php if ($error && $_SERVER['REQUEST_METHOD'] !== 'POST'): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <p class="text-center"><a href="forgot-password.php" class="btn btn-outline-primary rounded-pill">Request new link</a></p>
                        <?php else: ?>
                        <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        <form method="POST" action="reset-password.php?token=<?php echo htmlspecialchars($token); ?>">
                            <div class="mb-3">
                                <label class="form-label">New Password (min 6 characters)</label>
                                <input type="password" name="password" class="form-control" required minlength="6">
                            </div>
                            <div class="mb-4">
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" name="confirm_password" class="form-control" required minlength="6">
                            </div>
                            <button type="submit" class="btn btn-primary w-100 rounded-pill py-2">Reset Password</button>
                        </form>
                        <?php endif; ?>
                        <p class="text-center mt-4 mb-0 small"><a href="login.php" class="text-decoration-none">Back to Login</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
