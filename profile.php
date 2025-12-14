<?php
include 'includes/config.php';
include 'includes/functions.php';

$page_title = "My Profile";

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$user = getUserData($user_id);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $first_name = sanitize($_POST['first_name']);
    $last_name = sanitize($_POST['last_name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    
    $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ? WHERE id = ?");
    if ($stmt->execute([$first_name, $last_name, $email, $phone, $address, $user_id])) {
        $_SESSION['success'] = "Profile updated successfully!";
        redirect('profile.php');
    } else {
        $error = "Failed to update profile. Please try again.";
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (password_verify($current_password, $user['password'])) {
        if ($new_password === $confirm_password) {
            if (strlen($new_password) >= 6) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                if ($stmt->execute([$hashed_password, $user_id])) {
                    $_SESSION['success'] = "Password changed successfully!";
                    redirect('profile.php');
                } else {
                    $error = "Failed to change password. Please try again.";
                }
            } else {
                $error = "New password must be at least 6 characters long.";
            }
        } else {
            $error = "New passwords do not match.";
        }
    } else {
        $error = "Current password is incorrect.";
    }
}

include 'includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>My Profile</h1>
        <p>Manage your account information</p>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-error">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <div class="profile-layout">
        <!-- Profile Information -->
        <div class="profile-section">
            <div class="card">
                <div class="card-body">
                    <h3>Profile Information</h3>
                    <form method="POST" action="">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">First Name</label>
                                <input type="text" class="form-control" name="first_name" 
                                       value="<?php echo $user['first_name'] ?? ''; ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Last Name</label>
                                <input type="text" class="form-control" name="last_name" 
                                       value="<?php echo $user['last_name'] ?? ''; ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" value="<?php echo $user['username']; ?>" disabled>
                            <small class="text-muted">Username cannot be changed</small>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" 
                                   value="<?php echo $user['email']; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Phone</label>
                            <input type="tel" class="form-control" name="phone" 
                                   value="<?php echo $user['phone'] ?? ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" name="address" rows="3"><?php echo $user['address'] ?? ''; ?></textarea>
                        </div>
                        
                        <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Change Password -->
        <div class="profile-section">
            <div class="card">
                <div class="card-body">
                    <h3>Change Password</h3>
                    <form method="POST" action="">
                        <div class="form-group">
                            <label class="form-label">Current Password</label>
                            <input type="password" class="form-control" name="current_password" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">New Password</label>
                            <input type="password" class="form-control" name="new_password" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" name="confirm_password" required>
                        </div>
                        
                        <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Order History -->
        <div class="profile-section">
            <div class="card">
                <div class="card-body">
                    <h3>Order History</h3>
                    <?php
                    $orders = $pdo->prepare("
                        SELECT * FROM orders 
                        WHERE user_id = ? 
                        ORDER BY created_at DESC 
                        LIMIT 5
                    ");
                    $orders->execute([$user_id]);
                    $recent_orders = $orders->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    
                    <?php if (empty($recent_orders)): ?>
                        <p class="text-muted">No orders found.</p>
                    <?php else: ?>
                        <div class="order-list">
                            <?php foreach ($recent_orders as $order): ?>
                            <div class="order-item">
                                <div class="order-header">
                                    <div>
                                        <strong>Order #<?php echo $order['order_number']; ?></strong>
                                        <span class="order-date"><?php echo date('M j, Y', strtotime($order['created_at'])); ?></span>
                                    </div>
                                    <div class="order-status status-<?php echo $order['status']; ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </div>
                                </div>
                                <div class="order-details">
                                    <span>Total: <?php echo $order['total_amount']; ?></span>
                                    <a href="orders.php?order_id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline">View Details</a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="text-center mt-3">
                            <a href="orders.php" class="btn btn-outline">View All Orders</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.profile-layout {
    display: grid;
    grid-template-columns: 1fr;
    gap: 2rem;
    margin-top: 2rem;
}

.profile-section {
    background: var(--bg-white);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.order-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.order-item {
    padding: 1rem;
    border: 1px solid var(--border-color);
    border-radius: var(--radius);
    background: var(--bg-light);
}

.order-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.order-date {
    color: var(--text-light);
    font-size: 0.9rem;
    margin-left: 1rem;
}

.order-details {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.order-status {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: capitalize;
}

.status-pending { background: #fff3cd; color: #856404; }
.status-confirmed { background: #d1ecf1; color: #0c5460; }
.status-shipped { background: #d1ecf1; color: #0c5460; }
.status-delivered { background: #d4edda; color: #155724; }
.status-cancelled { background: #f8d7da; color: #721c24; }

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .order-header,
    .order-details {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
}
</style>

<?php include 'includes/footer.php'; ?>