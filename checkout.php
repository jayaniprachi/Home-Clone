<?php
include 'includes/config.php';
include 'includes/functions.php';

$page_title = "Checkout";

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$user = getUserData($user_id);

// Get cart items
$stmt = $pdo->prepare("
    SELECT c.*, p.name, p.price, p.image, p.stock_quantity,
           (c.quantity * p.price) as item_total
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check if cart is empty
if (empty($cart_items)) {
    $_SESSION['error'] = "Your cart is empty. Please add items before checkout.";
    redirect('cart.php');
}

// Check stock availability
foreach ($cart_items as $item) {
    if ($item['stock_quantity'] < $item['quantity']) {
        $_SESSION['error'] = "Insufficient stock for {$item['name']}. Only {$item['stock_quantity']} items available.";
        redirect('cart.php');
    }
}

// Calculate totals
$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['item_total'];
}
$shipping = $subtotal > 499 ? 0 : 49.99;
$tax = $subtotal * 0.08;
$total = $subtotal + $shipping + $tax;

// Handle order placement
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shipping_address = sanitize($_POST['shipping_address']);
    $billing_address = sanitize($_POST['billing_address']) ?: $shipping_address;
    $payment_method = sanitize($_POST['payment_method']);
    
    // Generate order number
    $order_number = 'HC' . date('Ymd') . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
    
    try {
        $pdo->beginTransaction();
        
        // Create order
        $stmt = $pdo->prepare("
            INSERT INTO orders (user_id, order_number, total_amount, shipping_address, billing_address, payment_method)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$user_id, $order_number, $total, $shipping_address, $billing_address, $payment_method]);
        $order_id = $pdo->lastInsertId();
        
        // Create order items and update stock
        foreach ($cart_items as $item) {
            // Add order item
            $stmt = $pdo->prepare("
                INSERT INTO order_items (order_id, product_id, quantity, price)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
            
            // Update product stock
            $stmt = $pdo->prepare("
                UPDATE products 
                SET stock_quantity = stock_quantity - ? 
                WHERE id = ?
            ");
            $stmt->execute([$item['quantity'], $item['product_id']]);
        }
        
        // Clear cart
        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        $pdo->commit();
        
        $_SESSION['order_success'] = $order_number;
        redirect('order_success.php?order_id=' . $order_id);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Order failed: " . $e->getMessage();
    }
}

include 'includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Checkout</h1>
        <p>Complete your purchase</p>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-error">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <div class="checkout-layout">
        <!-- Checkout Form -->
        <div class="checkout-form">
            <form method="POST" action="">
                <!-- Shipping Address -->
                <div class="checkout-section">
                    <h3>Shipping Address</h3>
                    <div class="form-group">
                        <label class="form-label">Shipping Address *</label>
                        <textarea class="form-control" name="shipping_address" rows="3" required><?php echo $user['address'] ?? ''; ?></textarea>
                    </div>
                </div>

                <!-- Billing Address -->
                <div class="checkout-section">
                    <h3>Billing Address</h3>
                    <div class="form-check mb-2">
                        <input type="checkbox" class="form-check-input" id="same_as_shipping" checked>
                        <label class="form-check-label" for="same_as_shipping">Same as shipping address</label>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Billing Address</label>
                        <textarea class="form-control" name="billing_address" id="billing_address" rows="3" disabled><?php echo $user['address'] ?? ''; ?></textarea>
                    </div>
                </div>

                <!-- Payment Method -->
                <div class="checkout-section">
                    <h3>Payment Method</h3>
                    <div class="payment-methods">
                        <div class="form-check">
                            <input type="radio" class="form-check-input" name="payment_method" value="credit_card" id="credit_card" checked required>
                            <label class="form-check-label" for="credit_card">
                                <i class="fas fa-credit-card"></i> Credit Card
                            </label>
                        </div>
                        <div class="form-check">
                            <input type="radio" class="form-check-input" name="payment_method" value="paypal" id="paypal">
                            <label class="form-check-label" for="paypal">
                                <i class="fab fa-paypal"></i> PayPal
                            </label>
                        </div>
                        <div class="form-check">
                            <input type="radio" class="form-check-input" name="payment_method" value="cash_on_delivery" id="cod">
                            <label class="form-check-label" for="cod">
                                <i class="fas fa-money-bill-wave"></i> Cash on Delivery
                            </label>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg">Place Order</button>
            </form>
        </div>

        <!-- Order Summary -->
        <div class="order-summary">
            <div class="card">
                <div class="card-body">
                    <h3>Order Summary</h3>
                    
                    <div class="order-items">
                        <?php foreach ($cart_items as $item): ?>
                        <div class="order-item">
                            <div class="item-image">
                                <img src="images/<?php echo $item['image'] ?: 'placeholder.jpg'; ?>" 
                                     alt="<?php echo $item['name']; ?>">
                            </div>
                            <div class="item-details">
                                <h5><?php echo $item['name']; ?></h5>
                                <p>Qty: <?php echo $item['quantity']; ?></p>
                            </div>
                            <div class="item-price">
                                $<?php echo number_format($item['item_total'], 2); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="order-totals">
                        <div class="total-row">
                            <span>Subtotal:</span>
                            <span>$<?php echo number_format($subtotal, 2); ?></span>
                        </div>
                        <div class="total-row">
                            <span>Shipping:</span>
                            <span>
                                <?php if ($shipping > 0): ?>
                                    $<?php echo number_format($shipping, 2); ?>
                                <?php else: ?>
                                    <span style="color: var(--primary-color);">FREE</span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="total-row">
                            <span>Tax (8%):</span>
                            <span>$<?php echo number_format($tax, 2); ?></span>
                        </div>
                        <div class="total-row grand-total">
                            <span><strong>Total:</strong></span>
                            <span><strong>$<?php echo number_format($total, 2); ?></strong></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.checkout-layout {
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: 3rem;
    margin-top: 2rem;
}

.checkout-section {
    background: var(--bg-white);
    padding: 1.5rem;
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    margin-bottom: 1.5rem;
}

.checkout-section h3 {
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid var(--border-color);
}

.payment-methods .form-check {
    margin-bottom: 1rem;
    padding: 1rem;
    border: 1px solid var(--border-color);
    border-radius: var(--radius);
}

.payment-methods .form-check-input:checked + label {
    color: var(--primary-color);
    font-weight: 600;
}

.order-summary {
    position: sticky;
    top: 100px;
    height: fit-content;
}

.order-items {
    max-height: 300px;
    overflow-y: auto;
    margin-bottom: 1.5rem;
}

.order-item {
    display: flex;
    gap: 1rem;
    padding: 1rem 0;
    border-bottom: 1px solid var(--border-color);
}

.order-item:last-child {
    border-bottom: none;
}

.item-image img {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: var(--radius);
}

.item-details {
    flex: 1;
}

.item-details h5 {
    margin: 0 0 0.25rem 0;
    font-size: 0.9rem;
}

.item-details p {
    margin: 0;
    color: var(--text-light);
    font-size: 0.8rem;
}

.item-price {
    font-weight: 600;
    color: var(--primary-color);
}

.order-totals {
    border-top: 1px solid var(--border-color);
    padding-top: 1rem;
}

.total-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
}

.grand-total {
    border-top: 2px solid var(--border-color);
    padding-top: 0.5rem;
    margin-top: 0.5rem;
    font-size: 1.1rem;
}

@media (max-width: 768px) {
    .checkout-layout {
        grid-template-columns: 1fr;
    }
    
    .order-summary {
        position: static;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sameAsShipping = document.getElementById('same_as_shipping');
    const billingAddress = document.getElementById('billing_address');
    
    sameAsShipping.addEventListener('change', function() {
        if (this.checked) {
            billingAddress.disabled = true;
            billingAddress.value = document.querySelector('[name="shipping_address"]').value;
        } else {
            billingAddress.disabled = false;
        }
    });
    
    // Copy shipping address to billing address when shipping address changes
    document.querySelector('[name="shipping_address"]').addEventListener('input', function() {
        if (sameAsShipping.checked) {
            billingAddress.value = this.value;
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>