<?php
$page_title = 'Contact';
require_once 'config.php';
require_once 'includes/header.php';

$sent = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['name']) && !empty($_POST['email']) && !empty($_POST['message'])) {
    $sent = true; // In production: send email or save to DB
}
?>

<section class="py-4 bg-light">
    <div class="container-fluid">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>">Home</a></li>
                <li class="breadcrumb-item active">Contact</li>
            </ol>
        </nav>
    </div>
</section>

<section class="py-5">
    <div class="container-fluid">
        <h1 class="h3 fw-bold mb-4">Contact Us</h1>
        <?php if ($sent): ?>
        <div class="alert alert-success">Thank you! Your message has been sent. We will get back to you soon.</div>
        <?php endif; ?>
        <div class="row g-5">
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-4">Get in Touch</h5>
                        <p class="text-muted mb-4">We're here to help. Reach out for orders, warranty, or any questions.</p>
                        <ul class="list-unstyled">
                            <li class="mb-3 d-flex align-items-start">
                                <i class="bi bi-geo-alt text-primary me-3 mt-1"></i>
                                <span>Bambalapitiya, Colombo 04, Sri Lanka</span>
                            </li>
                            <li class="mb-3 d-flex align-items-center">
                                <i class="bi bi-telephone text-primary me-3"></i>
                                <a href="tel:+94712345678" class="text-dark text-decoration-none">+94 71 234 5678</a>
                            </li>
                            <li class="mb-3 d-flex align-items-center">
                                <i class="bi bi-envelope text-primary me-3"></i>
                                <a href="mailto:info@ecodestore.lk" class="text-dark text-decoration-none">info@ecodestore.lk</a>
                            </li>
                            <li class="mb-0 d-flex align-items-center">
                                <i class="bi bi-clock text-primary me-3"></i>
                                <span>Mon - Sat: 9:00 AM - 7:00 PM</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-4">Send a Message</h5>
                        <form method="POST" action="contact.php">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Name *</label>
                                    <input type="text" name="name" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email *</label>
                                    <input type="email" name="email" class="form-control" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Subject</label>
                                    <input type="text" name="subject" class="form-control">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Message *</label>
                                    <textarea name="message" class="form-control" rows="5" required></textarea>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary rounded-pill px-4">Send Message</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
