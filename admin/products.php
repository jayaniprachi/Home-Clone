<?php
include 'includes/config.php';
include 'includes/auth.php';

$page_title = "Manage Products";

// Handle product actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_product'])) {
        $name = sanitize($_POST['name']);
        $description = sanitize($_POST['description']);
        $price = floatval($_POST['price']);
        $old_price = !empty($_POST['old_price']) ? floatval($_POST['old_price']) : null;
        $category_id = intval($_POST['category_id']);
        $stock_quantity = intval($_POST['stock_quantity']);
        $featured = isset($_POST['featured']) ? 1 : 0;
        
        // Handle image upload
        $image = 'placeholder.jpg';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            $file_type = $_FILES['image']['type'];
            
            if (in_array($file_type, $allowed_types)) {
                $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $image = 'product_' . time() . '.' . $extension;
                move_uploaded_file($_FILES['image']['tmp_name'], '../images/' . $image);
            }
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO products (name, description, price, old_price, category_id, image, stock_quantity, featured)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        if ($stmt->execute([$name, $description, $price, $old_price, $category_id, $image, $stock_quantity, $featured])) {
            $_SESSION['success'] = "Product added successfully!";
        } else {
            $_SESSION['error'] = "Failed to add product.";
        }
    }
    elseif (isset($_POST['update_product'])) {
        $product_id = intval($_POST['product_id']);
        $name = sanitize($_POST['name']);
        $description = sanitize($_POST['description']);
        $price = floatval($_POST['price']);
        $old_price = !empty($_POST['old_price']) ? floatval($_POST['old_price']) : null;
        $category_id = intval($_POST['category_id']);
        $stock_quantity = intval($_POST['stock_quantity']);
        $featured = isset($_POST['featured']) ? 1 : 0;
        $status = sanitize($_POST['status']);
        
        // Handle image upload
        $image = $_POST['current_image'];
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            $file_type = $_FILES['image']['type'];
            
            if (in_array($file_type, $allowed_types)) {
                $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $image = 'product_' . time() . '.' . $extension;
                move_uploaded_file($_FILES['image']['tmp_name'], '../images/' . $image);
                
                // Delete old image if it's not placeholder
                if ($_POST['current_image'] !== 'placeholder.jpg') {
                    @unlink('../images/' . $_POST['current_image']);
                }
            }
        }
        
        $stmt = $pdo->prepare("
            UPDATE products 
            SET name = ?, description = ?, price = ?, old_price = ?, category_id = ?, image = ?, 
                stock_quantity = ?, featured = ?, status = ?
            WHERE id = ?
        ");
        
        if ($stmt->execute([$name, $description, $price, $old_price, $category_id, $image, $stock_quantity, $featured, $status, $product_id])) {
            $_SESSION['success'] = "Product updated successfully!";
        } else {
            $_SESSION['error'] = "Failed to update product.";
        }
    }
    elseif (isset($_POST['delete_product'])) {
        $product_id = intval($_POST['product_id']);
        
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        if ($stmt->execute([$product_id])) {
            $_SESSION['success'] = "Product deleted successfully!";
        } else {
            $_SESSION['error'] = "Failed to delete product.";
        }
    }
    
    header('Location: products.php');
    exit;
}

// Get all products
$products = $pdo->query("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    ORDER BY p.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Get categories for dropdown
$categories = $pdo->query("SELECT * FROM categories WHERE status = 'active'")->fetchAll(PDO::FETCH_ASSOC);

// Check if we're adding or editing
$editing = isset($_GET['edit']);
$adding = isset($_GET['add']);
$product_to_edit = null;

if ($editing && isset($_GET['id'])) {
    $product_id = intval($_GET['id']);
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product_to_edit = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product_to_edit) {
        $_SESSION['error'] = "Product not found.";
        header('Location: products.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - HomeClone Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="css/admin.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="admin-layout">
        <?php include 'includes/sidebar.php'; ?>

        <div class="admin-main">
            <div class="admin-header">
                <h1>Manage Products</h1>
                <div class="admin-actions">
                    <a href="products.php?add" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Product
                    </a>
                </div>
            </div>

            <div class="admin-content">
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-error">
                        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <?php if ($adding || $editing): ?>
                    <!-- Add/Edit Product Form -->
                    <div class="card">
                        <div class="card-body">
                            <h2><?php echo $editing ? 'Edit Product' : 'Add New Product'; ?></h2>
                            
                            <form method="POST" action="" enctype="multipart/form-data">
                                <?php if ($editing): ?>
                                    <input type="hidden" name="product_id" value="<?php echo $product_to_edit['id']; ?>">
                                    <input type="hidden" name="current_image" value="<?php echo $product_to_edit['image']; ?>">
                                <?php endif; ?>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Product Name *</label>
                                        <input type="text" class="form-control" name="name" 
                                               value="<?php echo $product_to_edit['name'] ?? ''; ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Category *</label>
                                        <select class="form-control" name="category_id" required>
                                            <option value="">Select Category</option>
                                            <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>"
                                                <?php echo ($product_to_edit['category_id'] ?? '') == $category['id'] ? 'selected' : ''; ?>>
                                                <?php echo $category['name']; ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Description</label>
                                    <textarea class="form-control" name="description" rows="4"><?php echo $product_to_edit['description'] ?? ''; ?></textarea>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Price *</label>
                                        <input type="number" class="form-control" name="price" step="0.01" 
                                               value="<?php echo $product_to_edit['price'] ?? ''; ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Old Price</label>
                                        <input type="number" class="form-control" name="old_price" step="0.01" 
                                               value="<?php echo $product_to_edit['old_price'] ?? ''; ?>">
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Stock Quantity *</label>
                                        <input type="number" class="form-control" name="stock_quantity" 
                                               value="<?php echo $product_to_edit['stock_quantity'] ?? 0; ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Status</label>
                                        <select class="form-control" name="status">
                                            <option value="active" <?php echo ($product_to_edit['status'] ?? 'active') == 'active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="inactive" <?php echo ($product_to_edit['status'] ?? '') == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Product Image</label>
                                    <input type="file" class="form-control" name="image" accept="image/*">
                                    <?php if ($editing && $product_to_edit['image']): ?>
                                        <div class="current-image mt-2">
                                            <p>Current Image:</p>
                                            <img src="../images/<?php echo $product_to_edit['image']; ?>" 
                                                 alt="<?php echo $product_to_edit['name']; ?>" 
                                                 style="max-width: 200px; height: auto;">
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="form-check mb-3">
                                    <input type="checkbox" class="form-check-input" name="featured" value="1" 
                                           <?php echo ($product_to_edit['featured'] ?? 0) ? 'checked' : ''; ?>>
                                    <label class="form-check-label">Featured Product</label>
                                </div>
                                
                                <div class="form-actions">
                                    <a href="products.php" class="btn btn-outline">Cancel</a>
                                    <button type="submit" name="<?php echo $editing ? 'update_product' : 'add_product'; ?>" 
                                            class="btn btn-primary">
                                        <?php echo $editing ? 'Update Product' : 'Add Product'; ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Products List -->
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Image</th>
                                            <th>Name</th>
                                            <th>Category</th>
                                            <th>Price</th>
                                            <th>Stock</th>
                                            <th>Status</th>
                                            <th>Featured</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($products as $product): ?>
                                        <tr>
                                            <td>
                                                <img src="../images/<?php echo $product['image'] ?: 'placeholder.jpg'; ?>" 
                                                     alt="<?php echo $product['name']; ?>" 
                                                     style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                            </td>
                                            <td><?php echo $product['name']; ?></td>
                                            <td><?php echo $product['category_name']; ?></td>
                                            <td><?php echo $product['price']; ?></td>
                                            <td>
                                                <span class="<?php echo $product['stock_quantity'] > 0 ? 'text-success' : 'text-danger'; ?>">
                                                    <?php echo $product['stock_quantity']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="status-badge status-<?php echo $product['status']; ?>">
                                                    <?php echo ucfirst($product['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($product['featured']): ?>
                                                    <i class="fas fa-star text-warning"></i>
                                                <?php else: ?>
                                                    <i class="far fa-star text-muted"></i>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="products.php?edit&id=<?php echo $product['id']; ?>" 
                                                       class="btn btn-sm btn-outline" title="Edit">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </a>
                                                    <form method="POST" action="" style="display: inline;">
                                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                        <button type="submit" name="delete_product" 
                                                                class="btn btn-sm btn-outline" 
                                                                onclick="return confirm('Are you sure you want to delete this product?')"
                                                                title="Delete" style="margin-left: 5px;">
                                                            <i class="fas fa-trash"></i> Delete
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="../js/script.js"></script>
</body>
</html>