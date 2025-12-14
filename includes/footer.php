    </main>

    <footer class="footer">
        <div class="footer-container">
            <div class="footer-section">
                <h3><?php echo SITE_NAME; ?></h3>
                <p>Premium furniture for your dream home. Quality, comfort, and style in every piece.</p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-pinterest"></i></a>
                </div>
            </div>
            
            <div class="footer-section">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="products.php">Products</a></li>
                    <li><a href="about.php">About Us</a></li>
                    <li><a href="contact.php">Contact</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h4>Categories</h4>
                <ul>
                    <li><a href="products.php?category=1">Living Room</a></li>
                    <li><a href="products.php?category=2">Bedroom</a></li>
                    <li><a href="products.php?category=3">Dining Room</a></li>
                    <li><a href="products.php?category=4">Home Office</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h4>Contact Info</h4>
                <ul>
                    <li><i class="fas fa-map-marker-alt"></i> 123 , sumerucity , motavaracha , surat - 395006</li>
                    <li><i class="fas fa-phone"></i> +91 9925144365</li>
                    <li><i class="fas fa-envelope"></i> info@homeclone.com</li>
                </ul>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
        </div>
    </footer>

    <script src="js/script.js"></script>
    <?php if (isset($page_scripts)): ?>
        <?php foreach ($page_scripts as $script): ?>
            <script src="js/<?php echo $script; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>