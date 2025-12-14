<?php
include '../includes/config.php';
include '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login to add items to wishlist']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = intval($_POST['product_id']);
    $action = $_POST['action'] ?? 'toggle';
    $user_id = $_SESSION['user_id'];
    
    try {
        if ($action === 'add' || $action === 'toggle') {
            // Check if already in wishlist
            $stmt = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$user_id, $product_id]);
            
            if ($stmt->fetch()) {
                // Remove from wishlist
                $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
                $stmt->execute([$user_id, $product_id]);
                $message = "Product removed from wishlist";
                $is_in_wishlist = false;
            } else {
                // Add to wishlist
                $stmt = $pdo->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
                $stmt->execute([$user_id, $product_id]);
                $message = "Product added to wishlist";
                $is_in_wishlist = true;
            }
        } elseif ($action === 'remove') {
            $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$user_id, $product_id]);
            $message = "Product removed from wishlist";
            $is_in_wishlist = false;
        }
        
        // Get updated wishlist count
        $wishlist_count = getWishlistCount($user_id);
        
        echo json_encode([
            'success' => true,
            'message' => $message,
            'wishlist_count' => $wishlist_count,
            'is_in_wishlist' => $is_in_wishlist
        ]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>