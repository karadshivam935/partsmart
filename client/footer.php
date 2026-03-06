<?php if (session_status() === PHP_SESSION_NONE) {
    session_start();
} ?>
<footer class="footer">
    <div class="footer-container">
        <p>&copy; 2025 PartsMart. All rights reserved.</p>
        <div class="footer-links">
            <ul>
                <li><a href="../client/about.php">About Us</a></li>
                <li><a href="../client/products.php">Products</a></li>
            </ul>
            <ul>
                <?php if (!isset($_SESSION['isLoggedIn']) || $_SESSION['isLoggedIn'] !== true): ?>
                    <li><a href="../client/login.php">Login</a></li>
                    <li><a href="../client/signup.php">Sign Up</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</footer>