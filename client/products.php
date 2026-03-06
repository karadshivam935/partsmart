<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
  $isLoggedIn = isset($_SESSION['isLoggedIn']) && $_SESSION['isLoggedIn'] === true;

  // Initialize filter variables with GET parameters or default values
  $selectedCategory = isset($_GET['category']) ? $_GET['category'] : '';
  $selectedMaterial = isset($_GET['material']) ? $_GET['material'] : '';
  $minPrice = isset($_GET['min_price']) ? $_GET['min_price'] : '';
  $maxPrice = isset($_GET['max_price']) ? $_GET['max_price'] : '';
}

$isLoggedIn = isset($_SESSION['isLoggedIn']) && $_SESSION['isLoggedIn'] === true;

// Include database connection
require_once '../config/dbConnect.php'; // Your MySQLi connection file
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Products - Hardware Store</title>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&family=Poppins:wght@300;400;600&display=swap"
    rel="stylesheet" />
  <link rel="stylesheet" href="../styles/navbar.css" />
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

    /* Main Container Styles */
    .main-container {
      max-width: 1200px;
      margin: 40px auto 0;
      padding: 2rem;
    }

    .section-title {
      text-align: center;
      margin-bottom: 2rem;
      color: #333;
      font-size: 2rem;
    }

    /* Products Container */
    .products-container {
      display: flex;
      gap: 2rem;
    }

    /* Filters Section */
    .filters-section {
      width: 250px;
      background: white;
      padding: 1.5rem;
      border-radius: 8px;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
      position: sticky;
      top: 100px;
      height: fit-content;
    }

    .filter-group {
      margin-bottom: 1.5rem;
    }

    .filter-group label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: 500;
    }

    .filter-group select,
    .filter-group input {
      width: 100%;
      padding: 0.5rem;
      border: 1px solid #ddd;
      border-radius: 4px;
      margin-bottom: 0.5rem;
    }

    .apply-filters {
      width: 100%;
      padding: 0.75rem;
      background-color: #373737;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      transition: background-color 0.3s;
    }

    .apply-filters:hover {
      background-color: white;
    }

    /* Products Grid */
    .products-grid {
      flex: 1;
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
      gap: 2rem;
    }

    .product-card {
      background: white;
      border-radius: 8px;
      padding: 1rem;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
      position: relative;
      transition: transform 0.3s;
      height: auto;
      /* Ensure the card has sufficient height for content */
    }

    .product-card:hover {
      transform: translateY(-5px);
    }

    .product-image {
      width: 100%;
      height: 200px;
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

    /* Fixed Grid Layout with Scrollable Container */
    .products-grid-fixed {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      /* 3 columns */
      grid-template-rows: repeat(2, auto);
      /* 2 rows */
      gap: 1rem;
      scroll-behavior: smooth;
      /* Space between items */
      max-height: 600px;
      /* Fixed height for the container */
      overflow-y: scroll;
      /* Enable vertical scrollbar */
      padding: 1rem;
      border: 1px solid #ddd;
      /* Optional: Add a border */
      border-radius: 8px;
      /* Optional: Add rounded corners */
      scrollbar-width: none;
      /* Hide scrollbar for Firefox */
      -ms-overflow-style: none;
      /* Hide scrollbar for IE and Edge */
    }

    /* Hide scrollbar for WebKit browsers (Chrome, Safari) */
    .products-grid-fixed::-webkit-scrollbar {
      display: none;
    }

    .products-grid-fixed {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      /* 3 columns */
      grid-template-rows: repeat(2, auto);
      /* 2 rows */
      gap: 1rem;
      /* Space between items */
      max-height: 600px;
      /* Fixed height for the container */
      overflow-y: auto;
      /* Enable vertical scrollbar */
      padding: 1rem;
      border: 1px solid #ddd;
      /* Optional: Add a border */
      border-radius: 8px;
      /* Optional: Add rounded corners */
    }

    /* Product Card Styles */
    .product-card {
      background: white;
      border-radius: 8px;
      padding: 1rem;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
      transition: transform 0.3s;
    }

    .product-card:hover {
      transform: translateY(-5px);
    }

    .product-image {
      width: 100%;
      height: 200px;
      /* Ensure height is set */
      object-fit: fill;
      /* Forces the image to stretch */
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

    /* Scrollbar Styling */
    .products-grid-fixed::-webkit-scrollbar {
      width: 8px;
    }

    .products-grid-fixed::-webkit-scrollbar-track {
      background: #f1f1f1;
      border-radius: 4px;
    }

    .products-grid-fixed::-webkit-scrollbar-thumb {
      background: #888;
      border-radius: 4px;
    }

    .products-grid-fixed::-webkit-scrollbar-thumb:hover {
      background: #555;
    }

    /* Footer Styles */
    .footer {
      background-color: #333;
      color: white;
      padding: 2rem;
      margin-top: 4rem;
    }
  </style>
</head>

<body>
  <?php include('header.php') ?>
  <!-- Main Content -->
  <div class="main-container">
    <h1 class="section-title">Our Products</h1>

    <div class="products-container">
      <!-- Filters Section -->
      <div class="filters-section">
        <h3>Filters</h3>
        <form id="filters-form" method="GET">
          <div class="filter-group">
            <label>Category</label>
            <select name="category">
              <option value="">All Categories</option>
              <?php
              // Query to get visible categories
              $sql = "SELECT Category_Name FROM category_tbl WHERE Visibility_Mode = 1 ORDER BY Category_Name";
              $result = $conn->query($sql);

              if ($result) {
                if ($result->num_rows > 0) {
                  while ($row = $result->fetch_assoc()) {
                    $categoryValue = htmlspecialchars($row['Category_Name']);
                    $selected = ($selectedCategory === $categoryValue) ? 'selected' : '';
                    echo "<option value=\"$categoryValue\" $selected>" . ucfirst($categoryValue) . "</option>";
                  }
                } else {
                  echo "<option value=\"\">No categories found</option>";
                }
              } else {
                echo "<option value=\"\">Error loading categories</option>";
                error_log("Error fetching categories: " . $conn->error);
              }
              ?>
            </select>
          </div>

          <div class="filter-group">
            <label>Material</label>
            <select name="material">
              <option value="">All Materials</option>
              <?php
              // Query to get distinct materials from product_details_tbl
              $sql = "SELECT DISTINCT Material FROM product_details_tbl ORDER BY Material";
              $result = $conn->query($sql);

              if ($result) {
                if ($result->num_rows > 0) {
                  while ($row = $result->fetch_assoc()) {
                    $materialValue = htmlspecialchars($row['Material']);
                    $selected = ($selectedMaterial === $materialValue) ? 'selected' : '';
                    echo "<option value=\"$materialValue\" $selected>" . ucfirst($materialValue) . "</option>";
                  }
                } else {
                  echo "<option value=\"\">No materials found</option>";
                }
              } else {
                echo "<option value=\"\">Error loading materials</option>";
                error_log("Error fetching materials: " . $conn->error);
              }
              ?>
            </select>
          </div>

          <div class="filter-group">
            <label>Price Range</label>
            <input type="number" name="min_price" placeholder="Min Price" value="<?php echo $minPrice; ?>">
            <input type="number" name="max_price" placeholder="Max Price" value="<?php echo $maxPrice; ?>">
          </div>

          <button type="submit" class="apply-filters">Apply Filters</button>
        </form>
      </div>
      <?php include('productList.php') ?>
      <!-- Products Grid -->
    </div>
  </div>

  <!-- Login Alert -->
  <div class="login-alert" id="loginAlert">
    <h3>Please Login</h3>
    <p>You need to be logged in to add items to cart</p>
    <div class="dialog-buttons">
      <button class="confirm-add" onclick="redirectToLogin()">Go to Login</button>
      <button class="cancel-add" onclick="closeCartDialog()">Cancel</button>
    </div>
  </div>

  <?php include('footer.php') ?>

  <script src="../scripts/addToCart.js"></script>
  <script src="../scripts/navbar.js"></script>
</body>

</html>

<?php
// Close the database connection
$conn->close();
?>