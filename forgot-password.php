<?php
$page_title = 'Forgot Password';
require_once 'config.php';

if (isset($_SESSION['customer_id'])) {
    header('Location: ' . SITE_URL);
    exit;
}

$error = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $stmt = $conn->prepare("SELECT id FROM customers WHERE email = ? AND password IS NOT NULL AND password != ''");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        if (!$user) {
            $error = 'No account found with this email, or this account uses Google login.';
        } else {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour
            $conn->query("DELETE FROM password_resets WHERE email = '" . $conn->real_escape_string($email) . "'");
            $ins = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
            $ins->bind_param("sss", $email, $token, $expires);
            if ($ins->execute()) {
                $reset_link = SITE_URL . '/reset-password.php?token=' . $token;
                // In production: send email with $reset_link. For now show link on page.
                $message = 'If an account exists with this email, you can reset your password using the link below (valid for 1 hour):<br><br><a href="' . htmlspecialchars($reset_link) . '" class="text-break">' . htmlspecialchars($reset_link) . '</a><br><br>Save this link or open it in the same browser to set a new password.';
            } else {
                $error = 'Could not create reset link. Please try again.';
            }
        }
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
                        <h2 class="h4 fw-bold mb-4 text-center">Forgot Password</h2>
                        <p class="text-muted small text-center mb-4">Enter your email and we'll send you a link to reset your password.</p>
                        <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        <?php if ($message): ?>
                        <div class="alert alert-success"><?php echo $message; ?></div>
                        <?php else: ?>
                        <form method="POST" action="forgot-password.php">
                            <div class="mb-4">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                            </div>
                            <button type="submit" class="btn btn-primary w-100 rounded-pill py-2">Send Reset Link</button>
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
