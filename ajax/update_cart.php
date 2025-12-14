<?php
include '../includes/config.php';
include '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login to update cart']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cart_id = intval($_POST['cart_id']);
    $quantity = intval($_POST['quantity']);
    $user_id = $_SESSION['user_id'];
    
    if ($quantity < 1) {
        echo json_encode(['success' => false, 'message' => 'Quantity must be at least 1']);
        exit;
    }
    
    try {
        // Check if cart item belongs to user and get product info
        $stmt = $pdo->prepare("
            SELECT c.*, p.stock_quantity 
            FROM cart c 
            JOIN products p ON c.product_id = p.id 
            WHERE c.id = ? AND c.user_id = ?
        ");
        $stmt->execute([$cart_id, $user_id]);
        $cart_item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$cart_item) {
            echo json_encode(['success' => false, 'message' => 'Cart item not found']);
            exit;
        }
        
        if ($quantity > $cart_item['stock_quantity']) {
            echo json_encode([
                'success' => false, 
                'message' => "Only {$cart_item['stock_quantity']} items available in stock"
            ]);
            exit;
        }
        
        // Update quantity
        $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$quantity, $cart_id, $user_id]);
        
        // Get updated cart info
        $cart_count = getCartCount($user_id);
        
        // Calculate new item total
        $stmt = $pdo->prepare("
            SELECT c.quantity * p.price as item_total 
            FROM cart c 
            JOIN products p ON c.product_id = p.id 
            WHERE c.id = ?
        ");
        $stmt->execute([$cart_id]);
        $item_total = $stmt->fetchColumn();
        
        echo json_encode([
            'success' => true,
            'message' => 'Cart updated successfully',
            'cart_count' => $cart_count,
            'item_total' => number_format($item_total, 2)
        ]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>