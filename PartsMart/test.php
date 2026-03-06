<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
$products = [
  [
    'id' => 1,
    'name' => 'Door Handle Set',
    'price' => 1299.99,
    'description' => 'Premium stainless steel door handle set with modern design',
    'category' => 'door hardware',
    'material' => 'steel',
    'image' => '../assets/products/doorHandle.jpeg',
    'min_order_qty' => 30,
    'stock_qty' => 100,
  ],
  [
    'id' => 2,
    'name' => 'Window Lock',
    'price' => 499.99,
    'description' => 'High-security window locking mechanism',
    'category' => 'windows hardware',
    'material' => 'brass',
    'image' => '../assets/products/windowsLock.webp',
    'min_order_qty' => 20,
    'stock_qty' => 50,
  ],
  [
    'id' => 3,
    'name' => 'Plumbing Wrench',
    'price' => 799.99,
    'description' => 'Professional grade adjustable plumbing wrench',
    'category' => 'tools',
    'material' => 'aluminium',
    'image' => '../assets/products/wrench.webp',
    'min_order_qty' => 25,
    'stock_qty' => 200,
  ],
];

// Filter logic
$selectedCategory = isset($_GET['category']) ? $_GET['category'] : '';
$selectedMaterial = isset($_GET['material']) ? $_GET['material'] : '';
$minPrice = isset($_GET['min_price']) ? floatval($_GET['min_price']) : '';
$maxPrice = isset($_GET['max_price']) ? floatval($_GET['max_price']) : '';

// Apply filters
$filteredProducts = array_filter($products, function ($product) use ($selectedCategory, $selectedMaterial, $minPrice, $maxPrice) {
  $matchesCategory = empty($selectedCategory) || $product['category'] === $selectedCategory;
  $matchesMaterial = empty($selectedMaterial) || $product['material'] === $selectedMaterial;
  $matchesMinPrice = empty($minPrice) || $product['price'] >= $minPrice;
  $matchesMaxPrice = empty($maxPrice) || $product['price'] <= $maxPrice;

  return $matchesCategory && $matchesMaterial && $matchesMinPrice && $matchesMaxPrice;
});
$isLoggedIn = isset($_SESSION['user_name']);
$loginSuccess = isset($_SESSION['login_success']);

if ($loginSuccess) {
  echo '<script>alert("Login successful!");</script>';
  unset($_SESSION['login_success']); // Unset the session variable
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>PartsMart-HomePage</title>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&family=Poppins:wght@300;400;600&display=swap"
    rel="stylesheet" />
  <link rel="stylesheet" href="../styles/navbar.css">
  <link rel="stylesheet" href="../styles/footer.css" />
  <style>
    body,
    html {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      overflow-x: hidden;
      font-family: Poppins, sans-serif;
    }

    body::-webkit-scrollbar {
      display: none
    }

    .hero {
      height: 60vh;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 100px;
      padding: 20px;
      width: 80%;
      margin: 13vh auto 0
    }

    .hero-content {
      width: 50%
    }

    .hero-content h1 {
      font-size: 3rem;
      margin-bottom: 20px
    }

    .hero-content p {
      font-size: 1.2rem;
      margin-bottom: 20px
    }

    .hero-content a {
      text-decoration: none;
      color: black;
    }

    .hero-content button {
      padding: 10px 20px;
      font-size: 1rem;
      cursor: pointer
    }

    .hero-image {
      width: 50%
    }

    .hero-image img {
      max-width: 100%;
      height: 200px;
      border-radius: 10px
    }

    .hero h5 {
      font-weight: 100;
      text-align: justify
    }

    .elegant-quote {
      text-align: center;
      font-family: Apercu, sans-serif;
      background: #fff;
      color: #181818;
      border-radius: 10px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, .1);
      max-width: 80%;
      height: 17vh;
      margin: 0 auto;
      transform: scale(1);
      transition: transform .3s ease, box-shadow .3s ease
    }

    .elegant-quote p {
      font-size: 1.5rem;
      font-weight: 600;
      letter-spacing: 1px;
      text-transform: uppercase;
      line-height: 1.4;
      padding: 20px
    }

    .elegant-quote:hover {
      transform: scale(1.01);
      box-shadow: 0 15px 50px rgba(0, 0, 0, .15)
    }

    .testimonials {
      padding: 60px 20px;
      background-color: #f8f8f8;
      text-align: center
    }

    .testimonial-item {
      margin-bottom: 40px;
      display: inline-block;
      width: 300px;
      background-color: #fff;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, .1);
      transform: scale(1);
      transition: transform .3s ease
    }

    .testimonial-item:hover {
      transform: scale(1.05)
    }

    .testimonial-item p {
      font-size: 1.1rem;
      font-style: italic;
      margin-bottom: 15px
    }

    .testimonial-item h4 {
      font-size: 1.2rem;
      font-weight: 700;
      color: #333
    }

    .testimonial-item img {
      width: 70px;
      height: 70px;
      border-radius: 50%;
      margin-bottom: 10px
    }

    button {
      background-color: white;
      color: black;
    }

    .buttons {
      display: flex;
      justify-content: space-between;
      gap: 10px
    }

    .products-grid {
      margin-top: 10%;
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      width: 80%;
      height: 100%;
      align-items: center;
      justify-content: center;
      margin: 3% auto;
      gap: 10%;
    }

    .product-card {
      background: white;
      border-radius: 8px;
      padding: 1rem;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
      position: relative;
      transition: transform 0.3s;
      height: 100%;
    }

    .product-card:hover {
      transform: translateY(-5px);
    }

    .product-image {
      width: 100%;
      height: 300px;
      object-fit: cover;
      border-radius: 4px;
      margin-bottom: 1rem;
    }

    .product-name {
      font-size: 1.1rem;
      margin-bottom: 0.5rem;
      color: #333;
    }

    .product-price {
      font-size: 1.2rem;
      font-weight: 600;
      color: #2a2a2a;
      margin-bottom: 0.5rem;
    }

    .product-description {
      font-size: 0.9rem;
      color: #666;
      margin-bottom: 1rem;
    }

    .add-to-cart {
      width: 100%;
      padding: 0.75rem;
      background-color: #373737;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      transition: background-color 0.3s;
    }

    .add-to-cart:hover {
      background-color: white;
    }

    .view-more {
      display: inline-block;
      padding: 0.5rem 1rem;
      background-color: #f8f9fa;
      color: #333;
      text-decoration: none;
      border-radius: 4px;
      margin-top: 1rem;
      transition: background-color 0.3s;
    }

    .view-more:hover {
      background-color: #e9ecef;
    }

    /* Dialog Styles */
    .dialog-overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-color: rgba(0, 0, 0, 0.5);
      z-index: 1000;
    }

    .cart-dialog,
    .login-alert {
      display: none;
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      background: white;
      padding: 2rem;
      border-radius: 8px;
      z-index: 1001;
      min-width: 300px;
    }

    .dialog-buttons {
      display: flex;
      gap: 1rem;
      margin-top: 1rem;
    }

    .dialog-buttons button {
      flex: 1;
      padding: 0.75rem;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      transition: background-color 0.3s;
    }

    .confirm-add {
      background-color: #373737;
      color: white;
    }

    .cancel-add {
      background-color: #f1f1f1;
      color: #333;
    }


    .features {
      width: 80%;
      margin: 1% auto;
      gap: 50px;
      text-align: center;
      margin: 0 auto;
    }

    .feature-items {
      display: flex;
      align-items: center;
      justify-content: space-evenly;
    }

    .feature-item {
      width: 30%;
      background-color: #f1f1f1;
      /* Light background */
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      transition: transform 0.3s ease;
    }

    .feature-item h3 {
      font-size: 1.5rem;
      margin-bottom: 10px;
    }

    .feature-item p {
      font-size: 1rem;
      margin-bottom: 15px;
    }

    .feature-item:hover {
      transform: translateY(-10px);
      /* Elevates the card on hover */
    }

    .feature-item img {
      max-width: 100%;
      height: 30px;
      object-fit: contain;
      margin-bottom: 10px;
    }
  </style>

</head>

<body>
  <div class="wrapper">

    <?php include 'header.php' ?>
    <section class="hero">
      <div class="hero-content">
        <h1>Welcome to PartsMart!</h1>
        <p>Your one-stop shop for bulk parts.</p>
        <h5>
          At PartsMart, we simplify bulk ordering for businesses of all sizes.
          Whether you're stocking up for a project or sourcing parts for
          resale, we've got you covered. Explore our wide range of
          high-quality products at unbeatable prices. With a user-friendly
          platform, seamless checkout, and reliable delivery, your shopping
          experience has never been smoother. Join thousands of satisfied
          customers who trust PartsMart for their sourcing needs. Let's build
          something great together!
        </h5>
        <button><a href="./products.php">Shop Now</a></button>
      </div>
      <div class="hero-image">
        <img src="../assets/products/mainimage.jpg" alt="Hero Image" />
      </div>
    </section>
    <!-- Hero Section -->
    <section class="elegant-quote">
      <p>
        "Embrace the elegance. Experience the luxury. Where desire meets
        perfection."
      </p>
    </section>
    <div class="products-container">
      <?php include('productList.php') ?>
      <div class="login-alert" id="loginAlert">
        <h3>Please Login</h3>
        <p>You need to be logged in to add items to cart</p>
        <div class="dialog-buttons">
          <button class="confirm-add" onclick="redirectToLogin()">Go to Login</button>
          <button class="cancel-add" onclick="closeCartDialog()">Cancel</button>
        </div>
      </div>
    </div>


    <!-- Features Section -->
    <section class="features">
      <h2>Why Choose PartsMart?</h2>
      <div class="feature-items">
        <div class="feature-item">
          <img src="../assets/rocket.png" alt="Feature 1" />
          <p>Fast delivery</p>
        </div>
        <div class="feature-item">
          <img src="../assets/save-money.png" alt="Feature 2" />
          <p>Bulk discounts</p>
        </div>
        <!-- Add more features as needed -->
      </div>
    </section>
    <!-- Testimonials Section -->
    <section class="testimonials">
      <h2>What Our Customers Say</h2>
      <div class="testimonial-slider">
        <blockquote>"Great experience, fast delivery!"</blockquote>
        <p>- Pratik Patel</p>
      </div>
    </section>
    <?php include 'footer.php' ?>
  </div>
  <script src="../scripts/addToCart.js"></script>

  <script src="../scripts/navbar.js"></script>

</body>

</html>