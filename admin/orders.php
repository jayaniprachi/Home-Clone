<?php
include 'includes/config.php';
include 'includes/auth.php';

$page_title = "Manage Orders";

// Handle order status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $status = sanitize($_POST['status']);
    
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    if ($stmt->execute([$status, $order_id])) {
        $_SESSION['success'] = "Order status updated successfully!";
    } else {
        $_SESSION['error'] = "Failed to update order status.";
    }
    
    header('Location: orders.php');
    exit;
}

// Get orders with filters
$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

$sql = "
    SELECT o.*, u.username, u.email, 
           (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE 1=1
";

$params = [];

if ($status_filter) {
    $sql .= " AND o.status = ?";
    $params[] = $status_filter;
}

if ($search) {
    $sql .= " AND (o.order_number LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

$sql .= " ORDER BY o.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get order statistics
$total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$pending_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
$completed_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'delivered'")->fetchColumn();
$total_revenue = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE payment_status = 'paid'")->fetchColumn();
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
                <h1>Manage Orders</h1>
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

                <!-- Order Statistics -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #e3f2fd;">
                            <i class="fas fa-couch" style="color: #1976d2;"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $total_orders; ?></h3>
                            <p>Total Orders</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #fff3cd;">
                            <i class="fas fa-clock" style="color: #856404;"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $pending_orders; ?></h3>
                            <p>Pending Orders</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #d4edda;">
                            <i class="fas fa-check-circle" style="color: #155724;"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $completed_orders; ?></h3>
                            <p>Completed Orders</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #fff3e0;">
                            <i class="fas fa-dollar-sign" style="color: #f57c00;"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo number_format($total_revenue, 2); ?></h3>
                            <p>Total Revenue</p>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card">
                    <div class="card-body">
                        <form method="GET" action="" class="filter-form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Status Filter</label>
                                    <select class="form-control" name="status" onchange="this.form.submit()">
                                        <option value="">All Orders</option>
                                        <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="confirmed" <?php echo $status_filter == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                        <option value="shipped" <?php echo $status_filter == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                        <option value="delivered" <?php echo $status_filter == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                        <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Search</label>
                                    <div class="search-box">
                                        <input type="text" class="form-control" name="search" 
                                               value="<?php echo $search; ?>" placeholder="Search orders...">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Orders List -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Order </th>
                                        <th>Customer</th>
                                        <th>Items</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Payment</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo $order['order_number']; ?></strong>
                                        </td>
                                        <td>
                                            <div>
                                                <strong><?php echo $order['username']; ?></strong><br>
                                                <small><?php echo $order['email']; ?></small>
                                            </div>
                                        </td>
                                        <td><?php echo $order['item_count']; ?> items</td>
                                        <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
                                        <td>
                                            <form method="POST" action="" class="status-form">
                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                <input type="hidden" name="update_status" value="1">
                                                <select name="status" class="status-select" onchange="this.form.submit()">
                                                    <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="confirmed" <?php echo $order['status'] == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                                    <option value="shipped" <?php echo $order['status'] == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                                    <option value="delivered" <?php echo $order['status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                                    <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                </select>
                                            </form>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo $order['payment_status']; ?>">
                                                <?php echo ucfirst($order['payment_status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="order_details.php?id=<?php echo $order['id']; ?>" 
                                                   class="btn btn-sm btn-outline" title="View Details">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                                <a href="print_invoice.php?id=<?php echo $order['id']; ?>" 
                                                   class="btn btn-sm btn-outline" title="Print Invoice" 
                                                   target="_blank" style="margin-left: 5px;">
                                                    <i class="fas fa-print"></i> Print
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            
                            <?php if (empty($orders)): ?>
                                <div class="text-center p-4">
                                    <i class="fas fa-shopping-bag fa-2x text-muted"></i>
                                    <p class="text-muted mt-2">No orders found</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
    .search-box {
        display: flex;
        gap: 0.5rem;
    }
    
    .search-box .form-control {
        flex: 1;
    }
    
    .status-select {
        padding: 0.4rem 0.6rem;
        border: 1px solid var(--border-color);
        border-radius: var(--radius);
        background: white;
        font-size: 0.8rem;
        min-width: 120px;
        cursor: pointer;
    }
    
    .status-select:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 2px rgba(44, 85, 48, 0.1);
    }
    
    .status-form {
        margin: 0;
        display: inline-block;
    }

    .action-buttons {
        display: flex;
        gap: 0.5rem;
        flex-wrap: nowrap;
    }

    .action-buttons .btn {
        padding: 0.375rem 0.75rem;
        font-size: 0.8rem;
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
    }
    </style>

    <script src="../js/script.js"></script>
</body>
</html>