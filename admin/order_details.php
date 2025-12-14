<?php
include 'includes/config.php';
include 'includes/auth.php';

$page_title = "Order Details";

if (!isset($_GET['id'])) {
    header('Location: orders.php');
    exit;
}

$order_id = intval($_GET['id']);

// Get order details
$stmt = $pdo->prepare("
    SELECT o.*, u.username, u.email, u.phone, u.address as user_address
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE o.id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    $_SESSION['error'] = "Order not found.";
    header('Location: orders.php');
    exit;
}

// Get order items
$stmt = $pdo->prepare("
    SELECT oi.*, p.name, p.image, p.category_id, cat.name as category_name
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    LEFT JOIN categories cat ON p.category_id = cat.id
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                <h1>Order Details - <?php echo $order['order_number']; ?></h1>
                <div class="admin-actions">
                    <a href="orders.php" class="btn btn-outline">
                        <i class="fas fa-arrow-left"></i> Back to Orders
                    </a>
                </div>
            </div>

            <div class="admin-content">
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-error">
                        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <div class="order-details-grid">
                    <!-- Order Information -->
                    <div class="card">
                        <div class="card-body">
                            <h3>Order Information</h3>
                            <div class="info-grid">
                                <div class="info-item">
                                    <strong>Order Number:</strong>
                                    <span><?php echo $order['order_number']; ?></span>
                                </div>
                                <div class="info-item">
                                    <strong>Order Date:</strong>
                                    <span><?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?></span>
                                </div>
                                <div class="info-item">
                                    <strong>Status:</strong>
                                    <span class="status-badge status-<?php echo $order['status']; ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </div>
                                <div class="info-item">
                                    <strong>Payment Status:</strong>
                                    <span class="status-badge status-<?php echo $order['payment_status']; ?>">
                                        <?php echo ucfirst($order['payment_status']); ?>
                                    </span>
                                </div>
                                <div class="info-item">
                                    <strong>Payment Method:</strong>
                                    <span><?php echo ucwords(str_replace('_', ' ', $order['payment_method'])); ?></span>
                                </div>
                                <div class="info-item">
                                    <strong>Total Amount:</strong>
                                    <span style="font-weight: bold; color: var(--primary-color);">
                                        <?php echo number_format($order['total_amount'], 2); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Customer Information -->
                    <div class="card">
                        <div class="card-body">
                            <h3>Customer Information</h3>
                            <div class="info-grid">
                                <div class="info-item">
                                    <strong>Username:</strong>
                                    <span><?php echo $order['username']; ?></span>
                                </div>
                                <div class="info-item">
                                    <strong>Email:</strong>
                                    <span><?php echo $order['email']; ?></span>
                                </div>
                                <?php if ($order['phone']): ?>
                                <div class="info-item">
                                    <strong>Phone:</strong>
                                    <span><?php echo $order['phone']; ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Shipping Address -->
                    <div class="card">
                        <div class="card-body">
                            <h3>Shipping Address</h3>
                            <p style="white-space: pre-line;"><?php echo $order['shipping_address']; ?></p>
                        </div>
                    </div>

                    <!-- Billing Address -->
                    <div class="card">
                        <div class="card-body">
                            <h3>Billing Address</h3>
                            <p style="white-space: pre-line;"><?php echo $order['billing_address']; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="card">
                    <div class="card-body">
                        <h3>Order Items (<?php echo count($order_items); ?>)</h3>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Category</th>
                                        <th>Quantity</th>
                                        <th>Price</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($order_items as $item): ?>
                                    <tr>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 10px;">
                                                <img src="../images/<?php echo $item['image'] ?: 'placeholder.jpg'; ?>" 
                                                     alt="<?php echo $item['name']; ?>" 
                                                     style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                                <span><?php echo $item['name']; ?></span>
                                            </div>
                                        </td>
                                        <td><?php echo $item['category_name']; ?></td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td><?php echo number_format($item['price'], 2); ?></td>
                                        <td><?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
    .order-details-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .info-grid {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .info-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem 0;
        border-bottom: 1px solid var(--border-color);
    }

    .info-item:last-child {
        border-bottom: none;
    }
    </style>

    <script src="../js/script.js"></script>
</body>
</html>