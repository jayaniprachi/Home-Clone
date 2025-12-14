<?php
include 'includes/config.php';
include 'includes/functions.php';

$page_title = "My Wishlist";

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Handle remove from wishlist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_item'])) {
    $wishlist_id = intval($_POST['wishlist_id']);
    $stmt = $pdo->prepare("DELETE FROM wishlist WHERE id = ? AND user_id = ?");
    $stmt->execute([$wishlist_id, $user_id]);
    $_SESSION['success'] = "Item removed from wishlist";
    redirect('wishlist.php');
}

// Get wishlist items
$stmt = $pdo->prepare("
    SELECT w.*, p.name, p.description, p.price, p.old_price, p.image, 
           p.stock_quantity, p.rating, cat.name as category_name
    FROM wishlist w
    JOIN products p ON w.product_id = p.id
    LEFT JOIN categories cat ON p.category_id = cat.id
    WHERE w.user_id = ?
    ORDER BY w.created_at DESC
");
$stmt->execute([$user_id]);
$wishlist_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>My Wishlist</h1>
        <p>Your saved items</p>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($wishlist_items)): ?>
        <div class="empty-wishlist text-center">
            <i class="far fa-heart fa-3x"></i>
            <h3>Your wishlist is empty</h3>
            <p>Save items you love for later</p>
            <a href="products.php" class="btn btn-primary">Browse Products</a>
        </div>
    <?php else: ?>
        <div class="wishlist-grid grid grid-3">
            <?php foreach ($wishlist_items as $item): ?>
            <div class="product-card">
                <?php if ($item['old_price']): ?>
                    <div class="product-badge">Sale</div>
                <?php endif; ?>
                
                <img src="images/<?php echo $item['image'] ?: 'placeholder.jpg'; ?>" 
                     alt="<?php echo $item['name']; ?>" 
                     class="product-img">
                
                <div class="product-info">
                    <div class="product-category"><?php echo $item['category_name']; ?></div>
                    <h3 class="product-title"><?php echo $item['name']; ?></h3>
                    
                    <div class="product-price">
                        <span class="current-price"><?php echo $item['price']; ?></span>
                        <?php if ($item['old_price']): ?>
                            <span class="old-price"><?php echo $item['old_price']; ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="product-rating">
                        <?php
                        $rating = $item['rating'];
                        for ($i = 1; $i <= 5; $i++):
                            if ($i <= floor($rating)):
                                echo '<i class="fas fa-star"></i>';
                            elseif ($i - 0.5 <= $rating):
                                echo '<i class="fas fa-star-half-alt"></i>';
                            else:
                                echo '<i class="far fa-star"></i>';
                            endif;
                        endfor;
                        ?>
                        <span>(<?php echo $rating; ?>)</span>
                    </div>
                    
                    <div class="product-actions">
                        <form method="POST" class="remove-wishlist-form">
                            <input type="hidden" name="wishlist_id" value="<?php echo $item['id']; ?>">
                            <button type="submit" name="remove_item" class="btn-icon btn-wishlist active">
                                <i class="fas fa-heart"></i>
                            </button>
                        </form>
                        <button class="btn btn-cart" data-product-id="<?php echo $item['product_id']; ?>">
                            <i class="fas fa-shopping-cart"></i> Add to Cart
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.empty-wishlist {
    padding: 3rem;
}

.empty-wishlist i {
    color: var(--border-color);
    margin-bottom: 1rem;
}

.remove-wishlist-form {
    display: inline;
}

@media (max-width: 768px) {
    .wishlist-grid {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    }
}
</style>

<?php
$page_scripts = ['products.js'];
include 'includes/footer.php';
?>