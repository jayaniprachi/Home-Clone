<?php
$page_title = "Home - Premium Furniture Store";
include 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero">
    <div class="container">
        <div class="hero-content">
            <h1>Transform Your Space with Premium Furniture</h1>
            <p>Discover exquisite furniture pieces that combine comfort, style, and functionality for your dream home.</p>
            <a href="products.php" class="btn btn-primary">Shop Now</a>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="features">
    <div class="container">
        <div class="grid grid-4">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-shipping-fast"></i>
                </div>
                <h3>Free Shipping</h3>
                                   
                <p>Free delivery on orders over ₹499</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h3>2-Year Warranty</h3>
                <p>Comprehensive protection plan</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-undo-alt"></i>
                </div>
                <h3>Easy Returns</h3>
                <p>30-day return policy</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-headset"></i>
                </div>
                <h3>24/7 Support</h3>
                <p>Dedicated customer service</p>
            </div>
        </div>
    </div>
</section>

<!-- Categories Section -->
<section class="categories-section">
    <div class="container">
        <div class="section-header">
            <h2>Shop by Category</h2>
            <p>Explore our carefully curated furniture collections</p>
        </div>
        <div class="grid grid-4">
            <?php
            $categories = $pdo->query("SELECT * FROM categories WHERE status = 'active' LIMIT 4")->fetchAll();
            foreach ($categories as $category):
            ?>
            <div class="category-card">
                <div class="card">
                    <img src="images/<?php echo $category['image'] ?: 'placeholder.jpg'; ?>" 
                         alt="<?php echo $category['name']; ?>" 
                         class="card-img">
                    <div class="card-body text-center">
                        <h3 class="card-title"><?php echo $category['name']; ?></h3>
                        <p class="card-text"><?php echo $category['description']; ?></p>
                        <a href="products.php?category=<?php echo $category['id']; ?>" 
                           class="btn btn-outline">Explore</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Featured Products -->
<section class="products-section">
    <div class="container">
        <div class="section-header">
            <h2>Featured Products</h2>
            <p>Handpicked items for your home</p>
        </div>
        <div class="grid grid-4" id="featured-products">
            <?php
            $featured_products = $pdo->query("
                SELECT p.*, c.name as category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.featured = TRUE AND p.status = 'active' 
                LIMIT 8
            ")->fetchAll();
            
            foreach ($featured_products as $product):
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
                
                <img src="images/<?php echo $product['image'] ?: 'placeholder.jpg'; ?>" 
                     alt="<?php echo $product['name']; ?>" 
                     class="product-img">
                
                <div class="product-info">
                    <div class="product-category"><?php echo $product['category_name']; ?></div>
                    <h3 class="product-title"><?php echo $product['name']; ?></h3>
                    
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
                            <i class="<?php echo $is_in_wishlist ? 'fas' : 'far'; ?> fa-heart"></i>
                        </button>
                        <button class="btn btn-cart" data-product-id="<?php echo $product['id']; ?>">
                            <i class="fas fa-shopping-cart"></i> Add to Cart
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-3">
            <a href="products.php" class="btn btn-primary">View All Products</a>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section class="testimonials-section">
    <div class="container">
        <div class="section-header">
            <h2>What Our Customers Say</h2>
            <p>Real experiences from happy homeowners</p>
        </div>
        <div class="grid grid-3">
            <div class="testimonial-card">
                <div class="card">
                    <div class="card-body">
                        <div class="testimonial-rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <p>"The quality of the furniture exceeded my expectations. The delivery was prompt and the setup was hassle-free!"</p>
                        <div class="testimonial-author">
                            <strong>Neha Ramani</strong>
                            <span>Surat</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="testimonial-card">
                <div class="card">
                    <div class="card-body">
                        <div class="testimonial-rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                        </div>
                        <p>"Excellent customer service and beautiful furniture. My living room has never looked better!"</p>
                        <div class="testimonial-author">
                            <strong>Heer Shah</strong>
                            <span>Ahemdabad</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="testimonial-card">
                <div class="card">
                    <div class="card-body">
                        <div class="testimonial-rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <p>"The perfect blend of style and comfort. I highly recommend HomeClone for anyone looking to upgrade their home."</p>
                        <div class="testimonial-author">
                            <strong>Rohit Sharma</strong>
                            <span>Bharuch</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Newsletter Section -->
<section class="newsletter-section">
    <div class="container">
        <div class="newsletter-content text-center">
            <h2>Stay Updated</h2>
            <p>Subscribe to our newsletter for exclusive deals and new arrivals</p>
            <form class="newsletter-form">
                <div class="form-group">
                    <input type="email" class="form-control" placeholder="Enter your email" required>
                    <button type="submit" class="btn btn-primary">Subscribe</button>
                </div>
            </form>
        </div>
    </div>
</section>

<?php
$page_scripts = ['products.js'];
include 'includes/footer.php';
?>