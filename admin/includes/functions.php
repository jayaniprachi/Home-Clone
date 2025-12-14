<?php
// Admin specific functions

// Get admin statistics
function getAdminStats() {
    global $pdo;
    
    $stats = [];
    
    // Total products
    $stats['total_products'] = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    
    // Total orders
    $stats['total_orders'] = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    
    // Total users (excluding admins)
    $stats['total_users'] = $pdo->query("SELECT COUNT(*) FROM users WHERE is_admin = FALSE")->fetchColumn();
    
    // Total revenue
    $stats['total_revenue'] = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE payment_status = 'paid'")->fetchColumn();
    
    // Pending orders
    $stats['pending_orders'] = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
    
    // Low stock products (less than 10)
    $stats['low_stock'] = $pdo->query("SELECT COUNT(*) FROM products WHERE stock_quantity < 10")->fetchColumn();
    
    return $stats;
}

// Get recent activities
function getRecentActivities($limit = 10) {
    global $pdo;
    
    // This would typically come from an activities log table
    // For now, we'll return a placeholder
    return [
        ['type' => 'order', 'message' => 'New order #HC20231215001 placed', 'time' => '2 hours ago'],
        ['type' => 'user', 'message' => 'New user registration: john_doe', 'time' => '4 hours ago'],
        ['type' => 'product', 'message' => 'Product "Modern Sofa" updated', 'time' => '6 hours ago'],
        ['type' => 'order', 'message' => 'Order #HC20231214005 shipped', 'time' => '1 day ago'],
    ];
}

// Format currency
function formatCurrency($amount) {
    return '$' . number_format($amount, 2);
}

// Get order status color
function getStatusColor($status) {
    $colors = [
        'pending' => 'warning',
        'confirmed' => 'info',
        'shipped' => 'primary',
        'delivered' => 'success',
        'cancelled' => 'danger'
    ];
    
    return $colors[$status] ?? 'secondary';
}
?>