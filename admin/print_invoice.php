<?php
include 'includes/config.php';
include 'includes/auth.php';

if (!isset($_GET['id'])) {
    header('Location: orders.php');
    exit;
}

$order_id = intval($_GET['id']);

// Get order details for invoice
$stmt = $pdo->prepare("
    SELECT o.*, u.username, u.email, u.phone
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE o.id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die("Order not found.");
}

// Get order items
$stmt = $pdo->prepare("
    SELECT oi.*, p.name
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - <?php echo $order['order_number']; ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; color: #333; }
        .invoice { max-width: 800px; margin: 0 auto; background: white; }
        .header { display: flex; justify-content: space-between; margin-bottom: 30px; }
        .company-info h1 { color: #2c5530; margin: 0; }
        .invoice-details { text-align: right; }
        .section { margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f8f9fa; }
        .total-row { font-weight: bold; background-color: #f8f9fa; }
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="invoice">
        <div class="header">
            <div class="company-info">
                <h1>HomeClone Furniture</h1>
                <p>123 Furniture Street<br>City, State 12345<br>Phone: (123) 456-7890</p>
            </div>
            <div class="invoice-details">
                <h2>INVOICE</h2>
                <p><strong>Invoice #:</strong> <?php echo $order['order_number']; ?></p>
                <p><strong>Date:</strong> <?php echo date('F j, Y', strtotime($order['created_at'])); ?></p>
            </div>
        </div>

        <div class="section">
            <h3>Bill To:</h3>
            <p>
                <strong><?php echo $order['username']; ?></strong><br>
                <?php echo $order['email']; ?><br>
                <?php if ($order['phone']) echo $order['phone'] . '<br>'; ?>
            </p>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $subtotal = 0;
                foreach ($order_items as $item): 
                    $item_total = $item['price'] * $item['quantity'];
                    $subtotal += $item_total;
                ?>
                <tr>
                    <td><?php echo $item['name']; ?></td>
                    <td><?php echo $item['quantity']; ?></td>
                    <td><?php echo number_format($item['price'], 2); ?></td>
                    <td><?php echo number_format($item_total, 2); ?></td>
                </tr>
                <?php endforeach; ?>
                
                <?php
                $shipping = $subtotal > 499 ? 0 : 49.99;
                $tax = $subtotal * 0.08;
                $total = $subtotal + $shipping + $tax;
                ?>
                
                <tr class="total-row">
                    <td colspan="3" style="text-align: right;">Subtotal:</td>
                    <td><?php echo number_format($subtotal, 2); ?></td>
                </tr>
                <tr class="total-row">
                    <td colspan="3" style="text-align: right;">Shipping:</td>
                    <td><?php echo $shipping > 0 ? '$' . number_format($shipping, 2) : 'FREE'; ?></td>
                </tr>
                <tr class="total-row">
                    <td colspan="3" style="text-align: right;">Tax (8%):</td>
                    <td><?php echo number_format($tax, 2); ?></td>
                </tr>
                <tr class="total-row">
                    <td colspan="3" style="text-align: right;"><strong>Total:</strong></td>
                    <td><strong><?php echo number_format($total, 2); ?></strong></td>
                </tr>
            </tbody>
        </table>

        <div class="section">
            <p><strong>Payment Status:</strong> <?php echo ucfirst($order['payment_status']); ?></p>
            <p><strong>Order Status:</strong> <?php echo ucfirst($order['status']); ?></p>
        </div>

        <div class="no-print" style="margin-top: 30px; text-align: center;">
            <button onclick="window.print()" class="btn btn-primary">Print Invoice</button>
            <button onclick="window.close()" class="btn btn-outline">Close</button>
        </div>
    </div>
</body>
</html>