<?php
include 'includes/config.php';
include 'includes/functions.php';

$page_title = "My Orders";

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Get specific order if order_id is provided
if (isset($_GET['order_id'])) {
    $order_id = intval($_GET['order_id']);
    
    $stmt = $pdo->prepare("
        SELECT o.*, 
               (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
        FROM orders o 
        WHERE o.id = ? AND o.user_id = ?
    ");
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        $_SESSION['error'] = "Order not found.";
        redirect('orders.php');
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
}

// Get all orders for the user
$stmt = $pdo->prepare("
    SELECT o.*, 
           (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count,
           (SELECT SUM(quantity) FROM order_items WHERE order_id = o.id) as total_items
    FROM orders o 
    WHERE o.user_id = ? 
    ORDER BY o.created_at DESC
");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>My Orders</h1>
        <p>Track and manage your orders</p>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($order)): ?>
        <!-- Order Details -->
        <div class="order-details-page">
            <div class="order-header">
                <div class="order-info">
                    <h2>Order #<?php echo $order['order_number']; ?></h2>
                    <p>Placed on <?php echo date('F j, Y \a\t g:i A', strtotime($order['created_at'])); ?></p>
                </div>
                <div class="order-status">
                    <span class="status-badge status-<?php echo $order['status']; ?>">
                        <?php echo ucfirst($order['status']); ?>
                    </span>
                </div>
            </div>

            <div class="order-layout">
                <!-- Order Items -->
                <div class="order-items-section">
                    <h3>Order Items (<?php echo count($order_items); ?>)</h3>
                    <div class="order-items-list">
                        <?php foreach ($order_items as $item): ?>
                        <div class="order-item-card">
                            <div class="item-image">
                                <img src="images/<?php echo $item['image'] ?: 'placeholder.jpg'; ?>" 
                                     alt="<?php echo $item['name']; ?>">
                            </div>
                            <div class="item-details">
                                <h4><?php echo $item['name']; ?></h4>
                                <p class="item-category"><?php echo $item['category_name']; ?></p>
                                <p class="item-quantity">Quantity: <?php echo $item['quantity']; ?></p>
                            </div>
                            <div class="item-price">
                                <div class="price"><?php echo number_format($item['price'], 2); ?></div>
                                <div class="total"><?php echo number_format($item['price'] * $item['quantity'], 2); ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="order-summary-section">
                    <div class="card">
                        <div class="card-body">
                            <h3>Order Summary</h3>
                            
                            <div class="summary-group">
                                <h4>Shipping Address</h4>
                                <p><?php echo nl2br($order['shipping_address']); ?></p>
                            </div>
                            
                            <div class="summary-group">
                                <h4>Billing Address</h4>
                                <p><?php echo nl2br($order['billing_address']); ?></p>
                            </div>
                            
                            <div class="summary-group">
                                <h4>Payment Method</h4>
                                <p><?php echo ucwords(str_replace('_', ' ', $order['payment_method'])); ?></p>
                            </div>
                            
                            <div class="order-totals">
                                <div class="total-row">
                                    <span>Items (<?php echo $order['item_count']; ?>):</span>
                                    <span><?php echo number_format($order['total_amount'] - ($order['total_amount'] * 0.08) - ($order['total_amount'] > 499 ? 0 : 49.99), 2); ?></span>
                                </div>
                                <div class="total-row">
                                    <span>Shipping:</span>
                                    <span>
                                        <?php if ($order['total_amount'] > 499): ?>
                                            <span style="color: var(--primary-color);">FREE</span>
                                        <?php else: ?>
                                            49.99
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <div class="total-row">
                                    <span>Tax:</span>
                                    <span><?php echo number_format($order['total_amount'] * 0.08, 2); ?></span>
                                </div>
                                <div class="total-row grand-total">
                                    <span><strong>Total:</strong></span>
                                    <span><strong><?php echo number_format($order['total_amount'], 2); ?></strong></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="order-actions">
                        <a href="orders.php" class="btn btn-outline">Back to Orders</a>
                        <button class="btn btn-primary" onclick="window.print()">Print Receipt</button>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Orders List -->
        <div class="orders-list">
            <?php if (empty($orders)): ?>
                <div class="empty-orders text-center">
                    <i class="fas fa-shopping-bag fa-3x"></i>
                    <h3>No orders yet</h3>
                    <p>Start shopping to see your orders here</p>
                    <a href="products.php" class="btn btn-primary">Start Shopping</a>
                </div>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-card-header">
                        <div class="order-info">
                            <h3>Order #<?php echo $order['order_number']; ?></h3>
                            <p>Placed on <?php echo date('M j, Y', strtotime($order['created_at'])); ?></p>
                            <p><?php echo $order['total_items']; ?> items • <?php echo number_format($order['total_amount'], 2); ?></p>
                        </div>
                        <div class="order-status">
                            <span class="status-badge status-<?php echo $order['status']; ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="order-card-actions">
                        <a href="orders.php?order_id=<?php echo $order['id']; ?>" class="btn btn-outline btn-sm">View Details</a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.order-details-page {
    margin-top: 2rem;
}

.order-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: var(--bg-white);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
}

.order-layout {
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: 2rem;
}

.order-items-section {
    background: var(--bg-white);
    padding: 1.5rem;
    border-radius: var(--radius);
    box-shadow: var(--shadow);
}

.order-items-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin-top: 1rem;
}

.order-item-card {
    display: flex;
    gap: 1rem;
    padding: 1rem;
    border: 1px solid var(--border-color);
    border-radius: var(--radius);
    background: var(--bg-light);
}

.item-image img {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: var(--radius);
}

.item-details {
    flex: 1;
}

.item-details h4 {
    margin: 0 0 0.5rem 0;
}

.item-category {
    color: var(--text-light);
    font-size: 0.9rem;
    margin: 0 0 0.25rem 0;
}

.item-quantity {
    color: var(--text-light);
    font-size: 0.9rem;
    margin: 0;
}

.item-price {
    text-align: right;
}

.item-price .price {
    color: var(--text-light);
    text-decoration: line-through;
    font-size: 0.9rem;
}

.item-price .total {
    font-weight: 600;
    color: var(--primary-color);
    font-size: 1.1rem;
}

.order-summary-section {
    position: sticky;
    top: 100px;
    height: fit-content;
}

.summary-group {
    margin-bottom: 1.5rem;
}

.summary-group h4 {
    margin-bottom: 0.5rem;
    color: var(--text-dark);
}

.summary-group p {
    margin: 0;
    color: var(--text-light);
}

.order-totals {
    border-top: 1px solid var(--border-color);
    padding-top: 1rem;
    margin-top: 1rem;
}

.order-actions {
    display: flex;
    gap: 1rem;
    margin-top: 1.5rem;
}

.order-actions .btn {
    flex: 1;
}

/* Orders List */
.orders-list {
    margin-top: 2rem;
}

.order-card {
    background: var(--bg-white);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    margin-bottom: 1rem;
    overflow: hidden;
}

.order-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 1.5rem;
}

.order-card-actions {
    padding: 1rem 1.5rem;
    background: var(--bg-light);
    border-top: 1px solid var(--border-color);
}

.empty-orders {
    padding: 3rem;
    background: var(--bg-white);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
}

.empty-orders i {
    color: var(--border-color);
    margin-bottom: 1rem;
}

.status-badge {
    padding: 0.5rem 1rem;
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
    .order-header {
        flex-direction: column;
        gap: 1rem;
    }
    
    .order-layout {
        grid-template-columns: 1fr;
    }
    
    .order-summary-section {
        position: static;
    }
    
    .order-card-header {
        flex-direction: column;
        gap: 1rem;
    }
    
    .order-actions {
        flex-direction: column;
    }
}
</style>

<?php include 'includes/footer.php'; ?>