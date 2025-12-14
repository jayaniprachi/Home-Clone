<?php
include 'includes/config.php';
include 'includes/auth.php';

$page_title = "Manage Users";

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_user'])) {
        $user_id = intval($_POST['user_id']);
        $first_name = sanitize($_POST['first_name']);
        $last_name = sanitize($_POST['last_name']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        $address = sanitize($_POST['address']);
        $is_admin = isset($_POST['is_admin']) ? 1 : 0;
        
        $stmt = $pdo->prepare("
            UPDATE users 
            SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ?, is_admin = ?
            WHERE id = ?
        ");
        
        if ($stmt->execute([$first_name, $last_name, $email, $phone, $address, $is_admin, $user_id])) {
            $_SESSION['success'] = "User updated successfully!";
        } else {
            $_SESSION['error'] = "Failed to update user.";
        }
    }
    elseif (isset($_POST['delete_user'])) {
        $user_id = intval($_POST['user_id']);
        
        // Get current admin user ID from session
        $current_admin_id = $_SESSION['user_id'] ?? null;
        
        // Prevent admin from deleting themselves
        if ($user_id == $current_admin_id) {
            $_SESSION['error'] = "You cannot delete your own account.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            if ($stmt->execute([$user_id])) {
                $_SESSION['success'] = "User deleted successfully!";
            } else {
                $_SESSION['error'] = "Failed to delete user.";
            }
        }
    }
    
    header('Location: users.php');
    exit;
}

// Get all users
$users = $pdo->query("
    SELECT u.*, 
           (SELECT COUNT(*) FROM orders WHERE user_id = u.id) as order_count,
           (SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE user_id = u.id AND payment_status = 'paid') as total_spent
    FROM users u 
    ORDER BY u.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Check if we're editing
$editing = isset($_GET['edit']);
$user_to_edit = null;

if ($editing && isset($_GET['id'])) {
    $user_id = intval($_GET['id']);
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_to_edit = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user_to_edit) {
        $_SESSION['error'] = "User not found.";
        header('Location: users.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - HomeClone Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="css/admin.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="admin-layout">
        <?php include 'includes/sidebar.php'; ?>

        <div class="admin-main">
            <div class="admin-header">
                <h1>Manage Users</h1>
            </div>

            <div class="admin-content">
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-error">
                        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <?php if ($editing): ?>
                    <!-- Edit User Form -->
                    <div class="card">
                        <div class="card-body">
                            <h2>Edit User: <?php echo $user_to_edit['username']; ?></h2>
                            
                            <form method="POST" action="">
                                <input type="hidden" name="user_id" value="<?php echo $user_to_edit['id']; ?>">
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">First Name</label>
                                        <input type="text" class="form-control" name="first_name" 
                                               value="<?php echo $user_to_edit['first_name'] ?? ''; ?>">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Last Name</label>
                                        <input type="text" class="form-control" name="last_name" 
                                               value="<?php echo $user_to_edit['last_name'] ?? ''; ?>">
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Username</label>
                                    <input type="text" class="form-control" value="<?php echo $user_to_edit['username']; ?>" disabled>
                                    <small class="text-muted">Username cannot be changed</small>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email" 
                                           value="<?php echo $user_to_edit['email']; ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Phone</label>
                                    <input type="tel" class="form-control" name="phone" 
                                           value="<?php echo $user_to_edit['phone'] ?? ''; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Address</label>
                                    <textarea class="form-control" name="address" rows="3"><?php echo $user_to_edit['address'] ?? ''; ?></textarea>
                                </div>
                                
                                <div class="form-check mb-3">
                                    <input type="checkbox" class="form-check-input" name="is_admin" value="1" 
                                           <?php echo $user_to_edit['is_admin'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label">Administrator</label>
                                </div>
                                
                                <div class="user-stats mb-3">
                                    <h4>User Statistics</h4>
                                    <?php
                                    $stmt = $pdo->prepare("
                                        SELECT COUNT(*) as order_count, 
                                               COALESCE(SUM(total_amount), 0) as total_spent 
                                        FROM orders 
                                        WHERE user_id = ?
                                    ");
                                    $stmt->execute([$user_to_edit['id']]);
                                    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
                                    ?>
                                    <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));">
                                        <div class="stat-card">
                                            <div class="stat-info">
                                                <h3><?php echo $stats['order_count']; ?></h3>
                                                <p>Total Orders</p>
                                            </div>
                                        </div>
                                        <div class="stat-card">
                                            <div class="stat-info">
                                                <h3>₹<?php echo number_format($stats['total_spent'], 2); ?></h3>
                                                <p>Total Spent</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-actions">
                                    <a href="users.php" class="btn btn-outline">Cancel</a>
                                    <button type="submit" name="update_user" class="btn btn-primary">
                                        Update User
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Users List -->
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>User</th>
                                            <th>Contact</th>
                                            <th>Orders</th>
                                            <th>Total Spent</th>
                                            <th>Role</th>
                                            <th>Joined</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        // Get current admin user ID
                                        $current_user_id = $_SESSION['user_id'] ?? null;
                                        ?>
                                        <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td>
                                                <div>
                                                    <strong><?php echo $user['username']; ?></strong>
                                                    <?php if ($user['first_name'] || $user['last_name']): ?>
                                                        <br>
                                                        <small><?php echo $user['first_name'] . ' ' . $user['last_name']; ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <small><?php echo $user['email']; ?></small>
                                                    <?php if ($user['phone']): ?>
                                                        <br>
                                                        <small><?php echo $user['phone']; ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td><?php echo $user['order_count']; ?> orders</td>
                                            <td>₹<?php echo number_format($user['total_spent'], 2); ?></td>
                                            <td>
                                                <?php if ($user['is_admin']): ?>
                                                    <span class="status-badge status-delivered">Admin</span>
                                                <?php else: ?>
                                                    <span class="status-badge status-pending">Customer</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="users.php?edit&id=<?php echo $user['id']; ?>" 
                                                       class="btn btn-sm btn-outline" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <?php if (!$user['is_admin'] || $user['id'] != $current_user_id): ?>
                                                    <form method="POST" action="" style="display: inline;">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <button type="submit" name="delete_user" 
                                                                class="btn btn-sm btn-outline" 
                                                                onclick="return confirm('Are you sure you want to delete this user?')"
                                                                title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="../js/script.js"></script>
</body>
</html>