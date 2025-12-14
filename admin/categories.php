<?php
include 'includes/config.php';
include 'includes/auth.php';

$page_title = "Manage Categories";

// Handle category actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_category'])) {
        $name = sanitize($_POST['name']);
        $description = sanitize($_POST['description']);
        
        // Handle image upload
        $image = 'placeholder.jpg';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            $file_type = $_FILES['image']['type'];
            
            if (in_array($file_type, $allowed_types)) {
                $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $image = 'category_' . time() . '.' . $extension;
                move_uploaded_file($_FILES['image']['tmp_name'], '../images/' . $image);
            }
        }
        
        $stmt = $pdo->prepare("INSERT INTO categories (name, description, image) VALUES (?, ?, ?)");
        
        if ($stmt->execute([$name, $description, $image])) {
            $_SESSION['success'] = "Category added successfully!";
        } else {
            $_SESSION['error'] = "Failed to add category.";
        }
    }
    elseif (isset($_POST['update_category'])) {
        $category_id = intval($_POST['category_id']);
        $name = sanitize($_POST['name']);
        $description = sanitize($_POST['description']);
        $status = sanitize($_POST['status']);
        
        // Handle image upload
        $image = $_POST['current_image'];
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            $file_type = $_FILES['image']['type'];
            
            if (in_array($file_type, $allowed_types)) {
                $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $image = 'category_' . time() . '.' . $extension;
                move_uploaded_file($_FILES['image']['tmp_name'], '../images/' . $image);
                
                // Delete old image if it's not placeholder
                if ($_POST['current_image'] !== 'placeholder.jpg') {
                    @unlink('../images/' . $_POST['current_image']);
                }
            }
        }
        
        $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ?, image = ?, status = ? WHERE id = ?");
        
        if ($stmt->execute([$name, $description, $image, $status, $category_id])) {
            $_SESSION['success'] = "Category updated successfully!";
        } else {
            $_SESSION['error'] = "Failed to update category.";
        }
    }
    elseif (isset($_POST['delete_category'])) {
        $category_id = intval($_POST['category_id']);
        
        // Check if category has products
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
        $stmt->execute([$category_id]);
        $product_count = $stmt->fetchColumn();
        
        if ($product_count > 0) {
            $_SESSION['error'] = "Cannot delete category with existing products. Please reassign or delete the products first.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            if ($stmt->execute([$category_id])) {
                $_SESSION['success'] = "Category deleted successfully!";
            } else {
                $_SESSION['error'] = "Failed to delete category.";
            }
        }
    }
    
    header('Location: categories.php');
    exit;
}

// Get all categories
$categories = $pdo->query("SELECT * FROM categories ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Check if we're adding or editing
$editing = isset($_GET['edit']);
$adding = isset($_GET['add']);
$category_to_edit = null;

if ($editing && isset($_GET['id'])) {
    $category_id = intval($_GET['id']);
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$category_id]);
    $category_to_edit = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$category_to_edit) {
        $_SESSION['error'] = "Category not found.";
        header('Location: categories.php');
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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome-6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="admin-layout">
        <?php include 'includes/sidebar.php'; ?>

        <div class="admin-main">
            <div class="admin-header">
                <h1>Manage Categories</h1>
                <div class="admin-actions">
                    <a href="categories.php?add" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Category
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
                    <!-- Add/Edit Category Form -->
                    <div class="card">
                        <div class="card-body">
                            <h2><?php echo $editing ? 'Edit Category' : 'Add New Category'; ?></h2>
                            
                            <form method="POST" action="" enctype="multipart/form-data">
                                <?php if ($editing): ?>
                                    <input type="hidden" name="category_id" value="<?php echo $category_to_edit['id']; ?>">
                                    <input type="hidden" name="current_image" value="<?php echo $category_to_edit['image']; ?>">
                                <?php endif; ?>
                                
                                <div class="form-group">
                                    <label class="form-label">Category Name *</label>
                                    <input type="text" class="form-control" name="name" 
                                           value="<?php echo $category_to_edit['name'] ?? ''; ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Description</label>
                                    <textarea class="form-control" name="description" rows="3"><?php echo $category_to_edit['description'] ?? ''; ?></textarea>
                                </div>
                                
                                <?php if ($editing): ?>
                                <div class="form-group">
                                    <label class="form-label">Status</label>
                                    <select class="form-control" name="status">
                                        <option value="active" <?php echo ($category_to_edit['status'] ?? 'active') == 'active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?php echo ($category_to_edit['status'] ?? '') == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                </div>
                                <?php endif; ?>
                                
                                <div class="form-group">
                                    <label class="form-label">Category Image</label>
                                    <input type="file" class="form-control" name="image" accept="image/*">
                                    <?php if ($editing && $category_to_edit['image']): ?>
                                        <div class="current-image mt-2">
                                            <p>Current Image:</p>
                                            <img src="../images/<?php echo $category_to_edit['image']; ?>" 
                                                 alt="<?php echo $category_to_edit['name']; ?>" 
                                                 style="max-width: 200px; height: auto;">
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="form-actions">
                                    <a href="categories.php" class="btn btn-outline">Cancel</a>
                                    <button type="submit" name="<?php echo $editing ? 'update_category' : 'add_category'; ?>" 
                                            class="btn btn-primary">
                                        <?php echo $editing ? 'Update Category' : 'Add Category'; ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Categories List -->
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Image</th>
                                            <th>Name</th>
                                            <th>Description</th>
                                            <th>Status</th>
                                            <th>Products</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($categories as $category): 
                                            // Count products in this category
                                            $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
                                            $stmt->execute([$category['id']]);
                                            $product_count = $stmt->fetchColumn();
                                        ?>
                                        <tr>
                                            <td>
                                                <img src="../images/<?php echo $category['image'] ?: 'placeholder.jpg'; ?>" 
                                                     alt="<?php echo $category['name']; ?>" 
                                                     style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                            </td>
                                            <td><?php echo $category['name']; ?></td>
                                            <td><?php echo $category['description'] ?: 'No description'; ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo $category['status']; ?>">
                                                    <?php echo ucfirst($category['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $product_count; ?> products</td>
                                            <td><?php echo date('M j, Y', strtotime($category['created_at'])); ?></td>
                                            <<!-- In the categories list table -->
<td>
    <div class="action-buttons">
        <a href="categories.php?edit&id=<?php echo $category['id']; ?>" 
           class="btn btn-sm btn-outline" title="Edit">
            <i class="fas fa-edit"></i> Edit
        </a>
        <form method="POST" action="" style="display: inline;">
            <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
            <button type="submit" name="delete_category" 
                    class="btn btn-sm btn-outline" 
                    onclick="return confirm('Are you sure you want to delete this category?')"
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