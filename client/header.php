<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = isset($_SESSION['isLoggedIn']) && $_SESSION['isLoggedIn'] === true;
?>

<div class="navbar">
    <a href="../client/index.php">PartsMart</a>
    <div class="options">
        <li>
            <ul><a href="../client/index.php">Home</a></ul>
            <ul><a href="../client/products.php">Products</a></ul>
            <ul><a href="../client/about.php">About</a></ul>
        </li>
    </div>
    <div class="search-container">
        <form action="products.php" method="get" class="search-form">
            <input type="text" class="search-bar" placeholder="Search for products..." name="search"
                value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" />
            <button type="submit" id="search-button" class="search-button">Search</button>
        </form>
    </div>

    <div class="btn-grp">
        <?php if ($isLoggedIn): ?>
            <div class="dropdown">
                <a href="#" class="dropdown-toggle">
                    <span class="user-name"><?php echo $_SESSION['user_name']; ?></span>
                </a>
                <div class="dropdown-content">
                    <a href="profile.php">Profile</a>
                    <a href="orders.php">My Orders</a>
                    <a href="cart.php">My Cart</a>
                    <a href="logout.php">Logout</a>
                </div>
            </div>
        <?php else: ?>
            <button><a href="login.php">Login</a></button>
            <button><a href="signup.php">Signup</a></button>
        <?php endif; ?>
    </div>
</div>