<?php
include 'includes/config.php';
include 'includes/functions.php';

$page_title = "Contact Us";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $subject = sanitize($_POST['subject']);
    $message = sanitize($_POST['message']);
    
    // In a real application, you would send an email here
    $_SESSION['success'] = "Thank you for your message! We'll get back to you soon.";
    redirect('contact.php');
}

include 'includes/header.php';
?>

<div class="container">
    <div class="page-header text-center">
        <h1>Contact Us</h1>
        <p>Get in touch with our team</p>
    </div>

    <div class="contact-layout">
        <div class="contact-info">
            <h3>Get in Touch</h3>
            <div class="contact-item">
                <i class="fas fa-map-marker-alt"></i>
                <div>
                    <strong>Address</strong>
                    <p>123 Furniture Street, City, State 12345</p>
                </div>
            </div>
            <div class="contact-item">
                <i class="fas fa-phone"></i>
                <div>
                    <strong>Phone</strong>
                    <p>+1 (234) 567-8900</p>
                </div>
            </div>
            <div class="contact-item">
                <i class="fas fa-envelope"></i>
                <div>
                    <strong>Email</strong>
                    <p>info@homeclone.com</p>
                </div>
            </div>
            <div class="contact-item">
                <i class="fas fa-clock"></i>
                <div>
                    <strong>Business Hours</strong>
                    <p>Monday - Friday: 9:00 AM - 6:00 PM<br>
                       Saturday: 10:00 AM - 4:00 PM</p>
                </div>
            </div>
        </div>

        <div class="contact-form">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" class="form-control" name="name" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" name="email" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Subject</label>
                    <input type="text" class="form-control" name="subject" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Message</label>
                    <textarea class="form-control" name="message" rows="5" required></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">Send Message</button>
            </form>
        </div>
    </div>
</div>

<style>
.contact-layout {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 3rem;
    margin-top: 2rem;
}

.contact-info {
    background: var(--bg-white);
    padding: 2rem;
    border-radius: var(--radius);
    box-shadow: var(--shadow);
}

.contact-item {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
    align-items: flex-start;
}

.contact-item i {
    color: var(--primary-color);
    margin-top: 0.25rem;
}

.contact-form {
    background: var(--bg-white);
    padding: 2rem;
    border-radius: var(--radius);
    box-shadow: var(--shadow);
}

@media (max-width: 768px) {
    .contact-layout {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
$page_scripts = ['contact.js'];
include 'includes/footer.php';
?>