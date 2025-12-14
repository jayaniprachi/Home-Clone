<?php
// Include config only once
if (!isset($pdo)) {
    include 'config.php';
    include 'functions.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?><?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="css/responsive.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <header class="header">
        <nav class="navbar">
            <div class="nav-container">
                <div class="nav-logo">
                    <a href="index.php" class="logo-link">
                        <i class="fas fa-couch"></i>
                        <span><?php echo SITE_NAME; ?></span>
                    </a>
                </div>
                
                <div class="nav-menu" id="nav-menu">
                    <a href="index.php" class="nav-link">Home</a>
                    <a href="products.php" class="nav-link">Products</a>
                    <a href="about.php" class="nav-link">About</a>
                    <a href="contact.php" class="nav-link">Contact</a>
                    
                    <?php if (isLoggedIn()): ?>
                        <a href="profile.php" class="nav-link">Profile</a>
                        
                    <?php endif; ?>
                </div>

                <div class="nav-icons">
                    <?php if (isLoggedIn()): ?>
                        <a href="wishlist.php" class="nav-icon">
                            <i class="far fa-heart"></i>
                            <span class="icon-badge" id="wishlist-count"><?php echo getWishlistCount($_SESSION['user_id']); ?></span>
                        </a>
                        <a href="cart.php" class="nav-icon">
                            <i class="fas fa-shopping-cart"></i>
                            <span class="icon-badge" id="cart-count"><?php echo getCartCount($_SESSION['user_id']); ?></span>
                        </a>
                        <a href="logout.php" class="nav-icon">
                            <i class="fas fa-sign-out-alt"></i>
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="nav-link">Login</a>
                        <a href="register.php" class="btn btn-primary">Register</a>
                    <?php endif; ?>
                    
                    <div class="hamburger" id="hamburger">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <main class="main-content">