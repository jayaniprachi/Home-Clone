<?php
include 'includes/config.php';
include 'includes/functions.php';

$page_title = "Shopping Cart";

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Handle quantity updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_quantity'])) {
        $cart_id = intval($_POST['cart_id']);
        $quantity = intval($_POST['quantity']);
        
        if ($quantity > 0) {
            $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$quantity, $cart_id, $user_id]);
        } else {
            $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
            $stmt->execute([$cart_id, $user_id]);
        }
        $_SESSION['success'] = "Cart updated successfully";
    } elseif (isset($_POST['remove_item'])) {
        $cart_id = intval($_POST['cart_id']);
        $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
        $stmt->execute([$cart_id, $user_id]);
        $_SESSION['success'] = "Item removed from cart";
    }
    
    redirect('cart.php');
}

// Get cart items
$stmt = $pdo->prepare("
    SELECT c.*, p.name, p.price, p.image, p.stock_quantity, 
           (c.quantity * p.price) as item_total,
           cat.name as category_name
    FROM cart c
    JOIN products p ON c.product_id = p.id
    LEFT JOIN categories cat ON p.category_id = cat.id
    WHERE c.user_id = ?
");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['item_total'];
}
$shipping = $subtotal > 0 ? ($subtotal > 499 ? 0 : 49.99) : 0;
$tax = $subtotal * 0.08; // 8% tax
$total = $subtotal + $shipping + $tax;

include 'includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Shopping Cart</h1>
        <p>Review your items and proceed to checkout</p>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <div class="cart-layout">
        <?php if (empty($cart_items)): ?>
            <div class="empty-cart text-center">
                <i class="fas fa-shopping-cart fa-3x"></i>
                <h3>Your cart is empty</h3>
                <p>Browse our products and add items to your cart</p>
                <a href="products.php" class="btn btn-primary">Continue Shopping</a>
            </div>
        <?php else: ?>
            <!-- Cart Items -->
            <div class="cart-items">
                <?php foreach ($cart_items as $item): ?>
                <div class="cart-item card">
                    <div class="cart-item-content">
                        <div class="cart-item-image">
                            <img src="images/<?php echo $item['image'] ?: 'placeholder.jpg'; ?>" 
                                 alt="<?php echo $item['name']; ?>">
                        </div>
                        
                        <div class="cart-item-details">
                            <h4 class="cart-item-title"><?php echo $item['name']; ?></h4>
                            <p class="cart-item-category"><?php echo $item['category_name']; ?></p>
                            <p class="cart-item-price"><?php echo $item['price']; ?></p>
                            
                            <?php if ($item['stock_quantity'] < $item['quantity']): ?>
                                <div class="alert alert-warning">
                                    Only <?php echo $item['stock_quantity']; ?> items available
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="cart-item-actions">
                            <form method="POST" class="quantity-form">
                                <div class="quantity-controls">
                                    <button type="button" class="quantity-btn quantity-minus" 
                                            onclick="updateQuantity(<?php echo $item['id']; ?>, <?php echo $item['quantity'] - 1; ?>)">-</button>
                                    <input type="number" class="quantity-input" 
                                           value="<?php echo $item['quantity']; ?>" 
                                           min="1" max="<?php echo $item['stock_quantity']; ?>"
                                           onchange="updateQuantity(<?php echo $item['id']; ?>, this.value)">
                                    <button type="button" class="quantity-btn quantity-plus" 
                                            onclick="updateQuantity(<?php echo $item['id']; ?>, <?php echo $item['quantity'] + 1; ?>)">+</button>
                                </div>
                            </form>
                            
                            <div class="cart-item-total">
                                <?php echo number_format($item['item_total'], 2); ?>
                            </div>
                            
                            <form method="POST" class="remove-form">
                                <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" name="remove_item" class="btn-icon btn-remove">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Cart Summary -->
            <div class="cart-summary">
                <div class="card">
                    <div class="card-body">
                        <h3>Order Summary</h3>
                        
                        <div class="summary-row">
                            <span>Subtotal:</span>
                            <span><?php echo number_format($subtotal, 2); ?></span>
                        </div>
                        
                        <div class="summary-row">
                            <span>Shipping:</span>
                            <span>
                                <?php if ($shipping > 0): ?>
                                    <?php echo number_format($shipping, 2); ?>
                                <?php else: ?>
                                    <span style="color: var(--primary-color);">FREE</span>
                                <?php endif; ?>
                            </span>
                        </div>
                        
                        <div class="summary-row">
                            <span>Tax (8%):</span>
                            <span><?php echo number_format($tax, 2); ?></span>
                        </div>
                        
                        <div class="summary-row total">
                            <span><strong>Total:</strong></span>
                            <span><strong><?php echo number_format($total, 2); ?></strong></span>
                        </div>
                        
                        <?php if ($subtotal < 499): ?>
                            <div class="free-shipping-notice">
                                <i class="fas fa-shipping-fast"></i>
                                Add <?php echo number_format(499 - $subtotal, 2); ?> more for free shipping!
                            </div>
                        <?php endif; ?>
                        
                        <div class="cart-actions">
                            <a href="products.php" class="btn btn-outline btn-block">Continue Shopping</a>
                            <a href="checkout.php" class="btn btn-primary btn-block">Proceed to Checkout</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.cart-layout {
    display: grid;
    grid-template-columns: 1fr 350px;
    gap: 2rem;
    margin-top: 2rem;
}

.cart-item {
    margin-bottom: 1rem;
}

.cart-item-content {
    display: grid;
    grid-template-columns: 100px 1fr auto;
    gap: 1.5rem;
    align-items: center;
    padding: 1rem;
}

.cart-item-image img {
    width: 100px;
    height: 100px;
    object-fit: cover;
    border-radius: var(--radius);
}

.cart-item-title {
    margin-bottom: 0.5rem;
    color: var(--text-dark);
}

.cart-item-category {
    color: var(--text-light);
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
}

.cart-item-price {
    font-weight: 600;
    color: var(--primary-color);
    font-size: 1.1rem;
}

.cart-item-actions {
    display: flex;
    align-items: center;
    gap: 1.5rem;
}

.quantity-controls {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.quantity-btn {
    width: 35px;
    height: 35px;
    border: 1px solid var(--border-color);
    background: var(--bg-white);
    border-radius: var(--radius);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

.quantity-input {
    width: 60px;
    height: 35px;
    border: 1px solid var(--border-color);
    border-radius: var(--radius);
    text-align: center;
    font-weight: 600;
}

.cart-item-total {
    font-weight: 700;
    font-size: 1.1rem;
    color: var(--primary-color);
    min-width: 80px;
    text-align: right;
}

.btn-remove {
    color: #e74c3c;
    background: none;
    border: none;
    font-size: 1.1rem;
    cursor: pointer;
    padding: 5px;
}

.btn-remove:hover {
    color: #c0392b;
}

.cart-summary {
    position: sticky;
    top: 100px;
    height: fit-content;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--border-color);
}

.summary-row.total {
    border-top: 2px solid var(--border-color);
    border-bottom: none;
    font-size: 1.2rem;
    margin-top: 1rem;
}

.free-shipping-notice {
    background: #e8f5e8;
    color: var(--primary-color);
    padding: 0.75rem;
    border-radius: var(--radius);
    margin: 1rem 0;
    text-align: center;
    font-weight: 600;
}

.cart-actions {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin-top: 1.5rem;
}

.empty-cart {
    padding: 3rem;
    grid-column: 1 / -1;
}

.empty-cart i {
    color: var(--border-color);
    margin-bottom: 1rem;
}

@media (max-width: 768px) {
    .cart-layout {
        grid-template-columns: 1fr;
    }
    
    .cart-item-content {
        grid-template-columns: 80px 1fr;
        gap: 1rem;
    }
    
    .cart-item-actions {
        grid-column: 1 / -1;
        justify-content: space-between;
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid var(--border-color);
    }
}
</style>

<script>
function updateQuantity(cartId, newQuantity) {
    if (newQuantity < 1) return;
    
    const formData = new FormData();
    formData.append('cart_id', cartId);
    formData.append('quantity', newQuantity);
    formData.append('update_quantity', '1');
    
    fetch('cart.php', {
        method: 'POST',
        body: formData
    }).then(response => {
        window.location.reload();
    });
}
</script>

<?php include 'includes/footer.php'; ?>