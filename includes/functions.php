<?php
// HomeClone Functions File

// Check if functions are already defined
if (!function_exists('isLoggedIn')) {

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

/**
 * Get user data by ID
 */
function getUserData($user_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting user data: " . $e->getMessage());
        return false;
    }
}

/**
 * Sanitize input data
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect to specified URL
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Get products with filters
 */
function getProducts($filters = []) {
    global $pdo;
    
    $sql = "SELECT p.*, c.name as category_name FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.status = 'active'";
    $params = [];
    
    // Category filter
    if (isset($filters['category']) && $filters['category'] != '') {
        $sql .= " AND p.category_id = ?";
        $params[] = $filters['category'];
    }
    
    // Search filter
    if (isset($filters['search']) && $filters['search'] != '') {
        $sql .= " AND (p.name LIKE ? OR p.description LIKE ? OR c.name LIKE ?)";
        $searchTerm = "%{$filters['search']}%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    // Price range filters
    if (isset($filters['min_price']) && $filters['min_price'] != '') {
        $sql .= " AND p.price >= ?";
        $params[] = $filters['min_price'];
    }
    
    if (isset($filters['max_price']) && $filters['max_price'] != '') {
        $sql .= " AND p.price <= ?";
        $params[] = $filters['max_price'];
    }
    
    // Featured products filter
    if (isset($filters['featured']) && $filters['featured']) {
        $sql .= " AND p.featured = TRUE";
    }
    
    // Add sorting
    $sort = isset($filters['sort']) ? $filters['sort'] : 'newest';
    switch($sort) {
        case 'price_low':
            $sql .= " ORDER BY p.price ASC";
            break;
        case 'price_high':
            $sql .= " ORDER BY p.price DESC";
            break;
        case 'rating':
            $sql .= " ORDER BY p.rating DESC";
            break;
        case 'name':
            $sql .= " ORDER BY p.name ASC";
            break;
        default:
            $sql .= " ORDER BY p.created_at DESC";
    }
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting products: " . $e->getMessage());
        return [];
    }
}

/**
 * Get cart count for user
 */
function getCartCount($user_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ? $result['total'] : 0;
    } catch (PDOException $e) {
        error_log("Error getting cart count: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get wishlist count for user
 */
function getWishlistCount($user_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM wishlist WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    } catch (PDOException $e) {
        error_log("Error getting wishlist count: " . $e->getMessage());
        return 0;
    }
}

/**
 * Check if product is in user's wishlist
 */
function isInWishlist($user_id, $product_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user_id, $product_id]);
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        error_log("Error checking wishlist: " . $e->getMessage());
        return false;
    }
}

/**
 * Get cart items for user
 */
function getCartItems($user_id) {
    global $pdo;
    try {
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
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting cart items: " . $e->getMessage());
        return [];
    }
}

/**
 * Calculate cart totals
 */
function calculateCartTotals($cart_items) {
    $subtotal = 0;
    foreach ($cart_items as $item) {
        $subtotal += $item['item_total'];
    }
    
    $shipping = $subtotal > 499 ? 0 : 49.99;
    $tax = $subtotal * 0.08; // 8% tax
    $total = $subtotal + $shipping + $tax;
    
    return [
        'subtotal' => $subtotal,
        'shipping' => $shipping,
        'tax' => $tax,
        'total' => $total
    ];
}

/**
 * Generate order number
 */
function generateOrderNumber() {
    return 'HC' . date('Ymd') . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
}

/**
 * Get user orders
 */
function getUserOrders($user_id, $limit = null) {
    global $pdo;
    try {
        $sql = "
            SELECT o.*, 
                   (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count,
                   (SELECT SUM(quantity) FROM order_items WHERE order_id = o.id) as total_items
            FROM orders o 
            WHERE o.user_id = ? 
            ORDER BY o.created_at DESC
        ";
        
        if ($limit) {
            $sql .= " LIMIT " . intval($limit);
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting user orders: " . $e->getMessage());
        return [];
    }
}

/**
 * Get order details
 */
function getOrderDetails($order_id, $user_id = null) {
    global $pdo;
    try {
        $sql = "SELECT o.* FROM orders o WHERE o.id = ?";
        $params = [$order_id];
        
        if ($user_id) {
            $sql .= " AND o.user_id = ?";
            $params[] = $user_id;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting order details: " . $e->getMessage());
        return false;
    }
}

/**
 * Get order items
 */
function getOrderItems($order_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT oi.*, p.name, p.image, p.category_id, cat.name as category_name
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            LEFT JOIN categories cat ON p.category_id = cat.id
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$order_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting order items: " . $e->getMessage());
        return [];
    }
}

/**
 * Get featured products
 */
function getFeaturedProducts($limit = 8) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.featured = TRUE AND p.status = 'active' 
            ORDER BY p.created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting featured products: " . $e->getMessage());
        return [];
    }
}

/**
 * Get categories with product count
 */
function getCategoriesWithCount() {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT c.*, COUNT(p.id) as product_count
            FROM categories c
            LEFT JOIN products p ON c.id = p.category_id AND p.status = 'active'
            WHERE c.status = 'active'
            GROUP BY c.id
            ORDER BY c.name
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting categories: " . $e->getMessage());
        return [];
    }
}

/**
 * Validate email format
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Format price with currency symbol
 */
function formatPrice($price) {
    return '$' . number_format($price, 2);
}

/**
 * Generate star rating HTML
 */
function generateStarRating($rating) {
    $html = '';
    $fullStars = floor($rating);
    $halfStar = ($rating - $fullStars) >= 0.5;
    $emptyStars = 5 - $fullStars - ($halfStar ? 1 : 0);
    
    // Full stars
    for ($i = 0; $i < $fullStars; $i++) {
        $html .= '<i class="fas fa-star"></i>';
    }
    
    // Half star
    if ($halfStar) {
        $html .= '<i class="fas fa-star-half-alt"></i>';
    }
    
    // Empty stars
    for ($i = 0; $i < $emptyStars; $i++) {
        $html .= '<i class="far fa-star"></i>';
    }
    
    return $html;
}

/**
 * Get product by ID
 */
function getProductById($product_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.id = ?
        ");
        $stmt->execute([$product_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting product: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if product is in stock
 */
function isProductInStock($product_id, $quantity = 1) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT stock_quantity FROM products WHERE id = ? AND status = 'active'");
        $stmt->execute([$product_id]);
        $stock = $stmt->fetchColumn();
        return $stock >= $quantity;
    } catch (PDOException $e) {
        error_log("Error checking stock: " . $e->getMessage());
        return false;
    }
}

/**
 * Update product stock
 */
function updateProductStock($product_id, $quantity) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
        return $stmt->execute([$quantity, $product_id]);
    } catch (PDOException $e) {
        error_log("Error updating product stock: " . $e->getMessage());
        return false;
    }
}

/**
 * Get similar products
 */
function getSimilarProducts($product_id, $category_id, $limit = 4) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.category_id = ? AND p.id != ? AND p.status = 'active'
            ORDER BY RAND()
            LIMIT ?
        ");
        $stmt->execute([$category_id, $product_id, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting similar products: " . $e->getMessage());
        return [];
    }
}

} // End of function_exists check

// Initialize session messages if not set
if (!isset($_SESSION['success_messages'])) {
    $_SESSION['success_messages'] = [];
}
if (!isset($_SESSION['error_messages'])) {
    $_SESSION['error_messages'] = [];
}
?>