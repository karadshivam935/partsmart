<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Include the database connection file
require '../config/dbConnect.php';

// Get product detail ID from URL
$pdt_det_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($pdt_det_id <= 0) {
  echo "Invalid product detail ID!";
  exit;
}

// Fetch product details from the database using Pdt_Det_Id
$sql = "
    SELECT p.Product_Id, p.Product_Name, p.Category_Id, pd.Pdt_Det_Id, pd.Price, pd.Stock_Qty, pd.Color, pd.Material, pd.Dimensions, pd.ImagePath
    FROM product_tbl p
    JOIN product_details_tbl pd ON p.Product_Id = pd.Product_Id
    WHERE pd.Pdt_Det_Id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $pdt_det_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
  echo "Product not found!";
  exit;
}

$product = $result->fetch_assoc();

// No need to prepend the base path here since it's already included in the database
// $product['ImagePath'] already contains the full path

// Fetch reviews for the product from the database
$sql_reviews = "
    SELECT r.Review_Text, u.First_Name, u.Last_Name
    FROM review_tbl r
    JOIN user_detail_tbl u ON r.Email_Id = u.Email_Id
    WHERE r.Product_Id = ?
";
$stmt_reviews = $conn->prepare($sql_reviews);
$stmt_reviews->bind_param("i", $product['Product_Id']);
$stmt_reviews->execute();
$result_reviews = $stmt_reviews->get_result();

$product_reviews = [];
while ($row = $result_reviews->fetch_assoc()) {
  $product_reviews[] = [
    'customer' => $row['First_Name'] . ' ' . $row['Last_Name'],
    'review' => $row['Review_Text']
  ];
}

$isLoggedIn = isset($_SESSION['user_name']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $product['Product_Name']; ?> - Product Details</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../styles/navbar.css">
  <link rel="stylesheet" href="../styles/footer.css">
  <style>
    body,
    html {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      overflow-x: hidden;
      font-family: Poppins, sans-serif;
      background-color: white;
      color: #373737;
    }

    body::-webkit-scrollbar {
      display: none
    }

    .container {
      max-width: 1200px;
      margin: 13vh auto 5vh;
      padding: 2rem;
    }

    /* Product Layout */
    .product-container {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 3rem;
      margin-bottom: 4rem;
    }

    /* Image Section */
    .product-image-section {
      display: flex;
      justify-content: center;
      align-items: flex-start;
    }

    .product-image {
      width: 100%;
      max-width: 500px;
      height: auto;
      border-radius: 12px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
      transition: transform 0.3s ease;
    }

    .product-image:hover {
      transform: scale(1.02);
    }

    /* Details Section */
    .product-details {
      display: flex;
      flex-direction: column;
      gap: 1rem;
    }

    .product-title {
      font-size: 2.5rem;
      font-weight: 600;
      margin: 0 0 1rem 0;
      color: #2a2a2a;
    }

    .product-info {
      background-color: #ffffff;
      padding: 2rem;
      border-radius: 12px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
    }

    .product-info p {
      font-size: 1.1rem;
      margin: 0.8rem 0;
      line-height: 1.6;
    }

    .product-info strong {
      color: #2a2a2a;
    }

    /* Quantity Input */
    .quantity-section {
      margin: 2rem 0;
    }

    input[type="number"] {
      width: 120px;
      padding: 0.8rem;
      font-size: 1rem;
      border: 2px solid #e0e0e0;
      border-radius: 8px;
      margin-top: 0.5rem;
      transition: border-color 0.3s ease;
    }

    input[type="number"]:focus {
      outline: none;
      border-color: #373737;
    }

    /* Button */
    .btn {
      background-color: #373737;
      color: white;
      padding: 1rem 2rem;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-size: 1.1rem;
      transition: all 0.3s ease;
      width: 100%;
      max-width: 300px;
    }

    .btn:hover {
      background-color: white;
      transform: translateY(-2px);
    }

    /* Reviews Section */
    .review-title {
      font-size: 2rem;
      font-weight: 600;
      text-align: center;
      margin: 4rem 0 2rem;
      color: #2a2a2a;
    }

    .reviews {
      display: grid;
      gap: 1.5rem;
      padding: 1rem;
    }

    .review-card {
      background-color: #ffffff;
      padding: 1.5rem;
      border-radius: 12px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
      transition: transform 0.3s ease;
    }

    .review-card:hover {
      transform: translateY(-5px);
    }

    .review-card strong {
      color: #2a2a2a;
      font-size: 1.1rem;
      display: block;
      margin-bottom: 0.5rem;
    }

    .review-card p {
      margin: 0.5rem 0;
      line-height: 1.6;
      color: #555;
    }

    /* Add this to your existing CSS */
    input[type="number"]::-webkit-inner-spin-button,
    input[type="number"]::-webkit-outer-spin-button {
      -webkit-appearance: none;
      margin: 0;
    }

    input[type="number"] {
      -moz-appearance: textfield;
    }

    .quantity-error {
      color: #dc3545;
      font-size: 0.9rem;
      margin-top: 0.5rem;
      min-height: 20px;
      transition: opacity 0.3s ease;
      opacity: 0;
    }

    .quantity-error.show {
      opacity: 1;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
      .product-container {
        grid-template-columns: 1fr;
        gap: 2rem;
      }

      .container {
        padding: 1rem;
      }

      .product-title {
        font-size: 2rem;
      }

      .product-info {
        padding: 1.5rem;
      }

      .review-card {
        padding: 1.2rem;
      }
    }
  </style>
</head>

<body>
  <?php include('header.php') ?>

  <div class="container">
    <div class="product-container">
      <!-- Image Section -->
      <div class="product-image-section">
        <img src="<?php echo $product['ImagePath']; ?>" alt="<?php echo $product['Product_Name']; ?>"
          class="product-image">
      </div>

      <!-- Details Section -->
      <div class="product-details">
        <h1 class="product-title"><?php echo $product['Product_Name']; ?></h1>
        <div class="product-info">
          <p><strong>Price:</strong> ₹<?php echo number_format($product['Price'], 2); ?></p>
          <p><strong>Category:</strong> <?php echo $product['Category_Id']; ?></p>
          <p><strong>Material:</strong> <?php echo $product['Material']; ?></p>
          <p><strong>Color:</strong> <?php echo $product['Color']; ?></p>
          <p><strong>Dimensions:</strong> <?php echo $product['Dimensions']; ?></p>

          <div class="quantity-section">
            <label for="quantity"><strong>Quantity:</strong></label>
            <input type="number" id="quantity" min="1" max="<?php echo $product['Stock_Qty']; ?>" value="1">
          </div>

          <?php if ($isLoggedIn): ?>
            <button class="btn" id="add-to-cart" onclick="validateAndAddToCart()">Add to Cart</button>
            <div id="quantity-error" class="quantity-error"></div>
          <?php else: ?>
            <button class="btn" onclick="redirectToLogin()">Login to Add to Cart</button>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Reviews Section -->
    <h2 class="review-title">Customer Reviews</h2>
    <div class="reviews">
      <?php if (!empty($product_reviews)): ?>
        <?php foreach ($product_reviews as $review): ?>
          <div class="review-card">
            <strong><?php echo $review['customer']; ?></strong>
            <p><?php echo $review['review']; ?></p>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p>No reviews yet.</p>
      <?php endif; ?>
    </div>
  </div>

  <?php include('footer.php') ?>

  <script src="../scripts/navbar.js"></script>

  <script>
    // Function to validate quantity
    function validateAndAddToCart() {
      var quantityInput = document.getElementById('quantity');
      var quantity = parseInt(quantityInput.value);
      var maxStockQty = parseInt(quantityInput.max);
      var errorDiv = document.getElementById('quantity-error');
      errorDiv.textContent = '';

      // Check if quantity is greater than available stock
      if (quantity > maxStockQty) {
        errorDiv.textContent = 'Stock available: ' + maxStockQty + '.';
        errorDiv.classList.add('show');
        return;
      }

      // If no error, proceed to add to cart
      errorDiv.classList.remove('show');
      alert('Added ' + quantity + ' items to cart!');
      // Implement the logic to add to cart here.
    }

    // Function to redirect to login page if not logged in
    function redirectToLogin() {
      window.location.href = 'login.php';
    }
  </script>
</body>

</html>