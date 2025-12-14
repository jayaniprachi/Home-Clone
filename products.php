<?php
include 'includes/config.php';
include 'includes/functions.php';

$page_title = "Products";

// Get filters from GET parameters
$filters = [
    'category' => $_GET['category'] ?? '',
    'search' => $_GET['search'] ?? '',
    'min_price' => $_GET['min_price'] ?? '',
    'max_price' => $_GET['max_price'] ?? '',
    'sort' => $_GET['sort'] ?? 'newest'
];

// Get all categories for filter
$categories = $pdo->query("SELECT * FROM categories WHERE status = 'active'")->fetchAll();

// Get products with filters
$products = getProducts($filters);

include 'includes/header.php';
?>

<div class="container">
    <div class="products-page">
        <!-- Page Header -->
        <div class="page-header">
            <h1>Our Products</h1>
            <p>Discover our premium furniture collection</p>
        </div>

        <div class="products-layout">
            <!-- Filters Sidebar -->
            <div class="filters-sidebar" id="filter-sidebar">
                <div class="filters-header">
                    <h3>Filters</h3>
                    <button type="button" class="btn btn-outline btn-sm" id="clear-filters">Clear All</button>
                </div>

                <form method="GET" action="" id="products-form">
                    <!-- Search -->
                    <div class="filter-group">
                        <label class="form-label">Search</label>
                        <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($filters['search']); ?>" 
                               placeholder="Search products...">
                    </div>

                    <!-- Categories -->
                    <div class="filter-group">
                        <label class="form-label">Category</label>
                        <div class="category-filters">
                            <div class="form-check">
                                <input type="radio" class="form-check-input category-checkbox" name="category" value="" 
                                       <?php echo empty($filters['category']) ? 'checked' : ''; ?> id="cat-all">
                                <label class="form-check-label" for="cat-all">All Categories</label>
                            </div>
                            <?php foreach ($categories as $category): ?>
                            <div class="form-check">
                                <input type="radio" class="form-check-input category-checkbox" name="category" 
                                       value="<?php echo $category['id']; ?>" 
                                       <?php echo $filters['category'] == $category['id'] ? 'checked' : ''; ?> 
                                       id="cat-<?php echo $category['id']; ?>">
                                <label class="form-check-label" for="cat-<?php echo $category['id']; ?>">
                                    <?php echo $category['name']; ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Price Range -->
                    <div class="filter-group">
                        <label class="form-label">Price Range</label>
                        <div class="price-inputs">
                            <input type="number" class="form-control" name="min_price" placeholder="Min" 
                                   value="<?php echo $filters['min_price']; ?>" min="0">
                            <span>to</span>
                            <input type="number" class="form-control" name="max_price" placeholder="Max" 
                                   value="<?php echo $filters['max_price']; ?>" min="0">
                        </div>
                        <div id="price-display" class="price-display">
                            <?php
                            $min_display = $filters['min_price'] ? '₹' . $filters['min_price'] : 'Min';
                            $max_display = $filters['max_price'] ? '₹' . $filters['max_price'] : 'Max';
                            echo "$min_display - $max_display";
                            ?>
                        </div>
                    </div>

                    <!-- Sort -->
                    <div class="filter-group">
                        <label class="form-label">Sort By</label>
                        <select class="form-control" name="sort" id="sort-select">
                            <option value="newest" <?php echo $filters['sort'] == 'newest' ? 'selected' : ''; ?>>Newest First</option>
                            <option value="price_low" <?php echo $filters['sort'] == 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                            <option value="price_high" <?php echo $filters['sort'] == 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                            <option value="rating" <?php echo $filters['sort'] == 'rating' ? 'selected' : ''; ?>>Highest Rated</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block" id="apply-filters">Apply Filters</button>
                </form>
            </div>

            <!-- Products Grid -->
            <div class="products-main">
                <!-- Products Header -->
                <div class="products-header">
                    <div class="products-info">
                        <p>Showing <?php echo count($products); ?> products</p>
                    </div>
                    <div class="products-actions">
                        <button class="btn btn-outline btn-sm" id="filter-toggle">
                            <i class="fas fa-filter"></i> Filters
                        </button>
                    </div>
                </div>

                <!-- Products Grid -->
                <?php if (empty($products)): ?>
                    <div class="no-products">
                        <i class="fas fa-search fa-3x"></i>
                        <h3>No products found</h3>
                        <p>Try adjusting your filters or search terms</p>
                        <a href="products.php" class="btn btn-primary">Clear Filters</a>
                    </div>
                <?php else: ?>
                    <div class="products-grid" id="products-grid">
                        <?php foreach ($products as $product):
                            $is_in_wishlist = false;
                            if (isLoggedIn()) {
                                $stmt = $pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = ? AND product_id = ?");
                                $stmt->execute([$_SESSION['user_id'], $product['id']]);
                                $is_in_wishlist = $stmt->fetchColumn() > 0;
                            }
                        ?>
                        <div class="product-card">
                            <?php if ($product['old_price']): ?>
                                <div class="product-badge">Sale</div>
                            <?php endif; ?>
                            
                            <div class="product-image-container">
                                <img src="images/<?php echo $product['image'] ?: 'placeholder.jpg'; ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                     class="product-img">
                                <div class="product-overlay">
                                    <button class="btn-wishlist-overlay <?php echo $is_in_wishlist ? 'active' : ''; ?>" 
                                            data-product-id="<?php echo $product['id']; ?>"
                                            <?php echo !isLoggedIn() ? 'disabled' : ''; ?>>
                                        <i class="<?php echo $is_in_wishlist ? 'fas' : 'far'; ?> fa-heart"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="product-info">
                                <div class="product-category"><?php echo $product['category_name']; ?></div>
                                <h3 class="product-title"><?php echo $product['name']; ?></h3>
                                <p class="product-description"><?php echo substr($product['description'], 0, 100); ?>...</p>
                                
                                <div class="product-price">
                                    <span class="current-price">₹<?php echo $product['price']; ?></span>
                                    <?php if ($product['old_price']): ?>
                                        <span class="old-price">₹<?php echo $product['old_price']; ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="product-rating">
                                    <?php
                                    $rating = $product['rating'];
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
                                    <button class="btn-icon btn-wishlist <?php echo $is_in_wishlist ? 'active' : ''; ?>" 
                                            data-product-id="<?php echo $product['id']; ?>"
                                            <?php echo !isLoggedIn() ? 'disabled' : ''; ?>>
                                        <i class="<?php echo $is_in_wishlist ? 'fas' : 'far'; ?> fa-heart"></i> Wishlist
                                    </button>
                                    <button class="btn btn-cart" data-product-id="<?php echo $product['id']; ?>">
                                        <i class="fas fa-shopping-cart"></i> Add to Cart
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.products-layout {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 2rem;
    margin-top: 2rem;
}

.filters-sidebar {
    background: var(--bg-white);
    padding: 1.5rem;
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    height: fit-content;
    position: sticky;
    top: 100px;
}

.filters-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--border-color);
}

.filter-group {
    margin-bottom: 1.5rem;
}

.category-filters .form-check {
    margin-bottom: 0.5rem;
}

.price-inputs {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    gap: 0.5rem;
    align-items: center;
}

.price-display {
    text-align: center;
    margin-top: 0.5rem;
    font-weight: 600;
    color: var(--primary-color);
}

.products-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding: 1rem;
    background: var(--bg-white);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 2rem;
}

.product-image-container {
    position: relative;
    overflow: hidden;
    height: 250px;
}

.product-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.product-image-container:hover .product-img {
    transform: scale(1.05);
}

.product-overlay {
    position: absolute;
    top: 10px;
    right: 10px;
    z-index: 2;
}

.btn-wishlist-overlay {
    background: rgba(255, 255, 255, 0.9);
    border: none;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: var(--text-light);
    font-size: 1.2rem;
    transition: all 0.3s ease;
}

.btn-wishlist-overlay:hover {
    background: white;
    color: #e74c3c;
    transform: scale(1.1);
}

.btn-wishlist-overlay.active {
    background: #ffe6e6;
    color: #e74c3c;
}

.btn-wishlist-overlay:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.product-actions {
    display: flex;
    gap: 10px;
    margin-top: 1rem;
}

.btn-icon {
    padding: 8px 12px;
    border: none;
    border-radius: var(--radius);
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 0.9rem;
}

.btn-wishlist {
    background: var(--bg-light);
    color: var(--text-light);
    border: 1px solid var(--border-color);
}

.btn-wishlist:hover {
    background: #ffe6e6;
    color: #e74c3c;
    border-color: #e74c3c;
}

.btn-wishlist.active {
    background: #ffe6e6;
    color: #e74c3c;
    border-color: #e74c3c;
}

.btn-wishlist:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.btn-cart {
    background: var(--primary-color);
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: var(--radius);
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 5px;
    flex: 1;
    justify-content: center;
}

.btn-cart:hover {
    background: var(--primary-dark);
    transform: translateY(-2px);
}

.no-products {
    text-align: center;
    padding: 3rem;
    grid-column: 1 / -1;
}

.no-products i {
    color: var(--border-color);
    margin-bottom: 1rem;
}

#filter-toggle {
    display: none;
}

@media (max-width: 768px) {
    .products-layout {
        grid-template-columns: 1fr;
    }
    
    .filters-sidebar {
        position: fixed;
        top: 0;
        left: -100%;
        width: 300px;
        height: 100vh;
        z-index: 1000;
        transition: left 0.3s ease;
        overflow-y: auto;
    }
    
    .filters-sidebar.active {
        left: 0;
    }
    
    #filter-toggle {
        display: inline-flex;
    }
    
    .products-grid {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    }
    
    .product-actions {
        flex-direction: column;
    }
    
    .product-image-container {
        height: 200px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mobile filter toggle
    const filterToggle = document.getElementById('filter-toggle');
    const filterSidebar = document.getElementById('filter-sidebar');
    
    if (filterToggle && filterSidebar) {
        filterToggle.addEventListener('click', function() {
            filterSidebar.classList.toggle('active');
        });
    }
    
    // Clear filters button
    const clearFiltersBtn = document.getElementById('clear-filters');
    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', function() {
            window.location.href = 'products.php';
        });
    }
    
    // Update price display
    const minPriceInput = document.querySelector('input[name="min_price"]');
    const maxPriceInput = document.querySelector('input[name="max_price"]');
    const priceDisplay = document.getElementById('price-display');
    
    function updatePriceDisplay() {
        const min = minPriceInput.value || 'Min';
        const max = maxPriceInput.value || 'Max';
        priceDisplay.textContent = `₹${min} - ₹${max}`;
    }
    
    if (minPriceInput && maxPriceInput && priceDisplay) {
        minPriceInput.addEventListener('input', updatePriceDisplay);
        maxPriceInput.addEventListener('input', updatePriceDisplay);
        updatePriceDisplay();
    }
    
    // Auto-submit sort select
    const sortSelect = document.getElementById('sort-select');
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            document.getElementById('products-form').submit();
        });
    }
    
    // Auto-submit category filters
    const categoryCheckboxes = document.querySelectorAll('.category-checkbox');
    categoryCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            document.getElementById('products-form').submit();
        });
    });
    
    // Wishlist functionality
    const wishlistButtons = document.querySelectorAll('.btn-wishlist, .btn-wishlist-overlay');
    wishlistButtons.forEach(button => {
        button.addEventListener('click', function() {
            if (this.disabled) {
                alert('Please login to add items to wishlist');
                return;
            }
            
            const productId = this.dataset.productId;
            const isActive = this.classList.contains('active');
            
            // Make AJAX request
            fetch('ajax/toggle_wishlist.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `product_id=${productId}&action=${isActive ? 'remove' : 'add'}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Toggle active state
                    const allButtons = document.querySelectorAll(`[data-product-id="${productId}"]`);
                    allButtons.forEach(btn => {
                        btn.classList.toggle('active');
                        const icon = btn.querySelector('i');
                        if (icon) {
                            icon.className = btn.classList.contains('active') ? 
                                'fas fa-heart' : 'far fa-heart';
                        }
                    });
                    
                    // Update wishlist count
                    const wishlistCount = document.getElementById('wishlist-count');
                    if (wishlistCount && data.wishlist_count !== undefined) {
                        wishlistCount.textContent = data.wishlist_count;
                    }
                    
                    // Show toast notification
                    showToast(data.message, 'success');
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('An error occurred. Please try again.', 'error');
            });
        });
    });
    
    // Add to cart functionality
    const addToCartButtons = document.querySelectorAll('.btn-cart');
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.dataset.productId;
            
            fetch('ajax/add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `product_id=${productId}&quantity=1`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update cart count
                    const cartCount = document.getElementById('cart-count');
                    if (cartCount && data.cart_count !== undefined) {
                        cartCount.textContent = data.cart_count;
                    }
                    
                    showToast(data.message, 'success');
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('An error occurred. Please try again.', 'error');
            });
        });
    });
    
    // Toast notification function
    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = message;
        
        // Add styles if not already added
        if (!document.querySelector('#toast-styles')) {
            const styles = document.createElement('style');
            styles.id = 'toast-styles';
            styles.textContent = `
                .toast {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    padding: 15px 20px;
                    border-radius: 5px;
                    color: white;
                    z-index: 10000;
                    transform: translateX(400px);
                    transition: transform 0.3s ease;
                    max-width: 300px;
                }
                .toast-success { background: #27ae60; }
                .toast-error { background: #e74c3c; }
                .toast-warning { background: #f39c12; }
                .toast.show { transform: translateX(0); }
            `;
            document.head.appendChild(styles);
        }
        
        document.body.appendChild(toast);
        
        setTimeout(() => toast.classList.add('show'), 100);
        
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
});
</script>

<?php
$page_scripts = ['products.js'];
include 'includes/footer.php';
?>