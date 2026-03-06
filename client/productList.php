<?php
// Include the database connection file
require '../config/dbConnect.php';

// Build SQL query with filters
$sql = "
    SELECT 
        p.Product_Id,
        p.Product_Name,
        pd.Pdt_Det_Id,
        pd.Price,
        pd.Color,
        pd.Material,
        pd.Dimensions,
        pd.ImagePath,
        c.Category_Name
    FROM 
        product_tbl p
    INNER JOIN 
        product_details_tbl pd ON p.Product_Id = pd.Product_Id
    INNER JOIN 
        category_tbl c ON p.Category_Id = c.Category_Id
    WHERE 
        c.Visibility_Mode = 1";

$params = [];
$types = "";

// Handle search functionality
if (!empty($_GET['search'])) {
  $searchTerm = '%' . $_GET['search'] . '%'; // Wildcard for partial matching
  $sql .= " AND (LOWER(p.Product_Name) LIKE LOWER(?) 
                   OR LOWER(c.Category_Name) LIKE LOWER(?) 
                   OR LOWER(pd.Material) LIKE LOWER(?) 
                   OR LOWER(pd.Color) LIKE LOWER(?))";

  $params[] = $searchTerm;
  $params[] = $searchTerm;
  $params[] = $searchTerm;
  $params[] = $searchTerm;
  $types .= "ssss";
}

// Add category filter
if (!empty($_GET['category'])) {
  $sql .= " AND LOWER(c.Category_Name) = LOWER(?)";
  $params[] = $_GET['category'];
  $types .= "s";
}

// Add material filter
if (!empty($_GET['material'])) {
  $sql .= " AND LOWER(pd.Material) = LOWER(?)";
  $params[] = $_GET['material'];
  $types .= "s";
}

// Add price range filters
if (!empty($_GET['min_price'])) {
  $sql .= " AND pd.Price >= ?";
  $params[] = $_GET['min_price'];
  $types .= "d";
}

if (!empty($_GET['max_price'])) {
  $sql .= " AND pd.Price <= ?";
  $params[] = $_GET['max_price'];
  $types .= "d";
}

// Prepare and execute the query
$stmt = $conn->prepare($sql);

if ($stmt === false) {
  die("Error preparing statement: " . $conn->error);
}

if (!empty($params)) {
  $stmt->bind_param($types, ...$params);
}

if (!$stmt->execute()) {
  die("Error executing query: " . $stmt->error);
}

$result = $stmt->get_result();
$products = [];

while ($row = $result->fetch_assoc()) {
  // No need to prepend the base path here since it's already included in the database
  $products[] = $row;
}

// Store the count for display
$productCount = count($products);
?>

<!-- Products Grid -->
<div class="products-grid-fixed">
  <?php if ($productCount > 0): ?>
    <?php foreach ($products as $product): ?>
      <div class="product-card">
        <img src="<?php echo $product['ImagePath']; ?>" alt="<?php echo $product['Product_Name']; ?>" class="product-image">
        <h3 class="product-name"><?php echo $product['Product_Name']; ?></h3>
        <p class="product-price">₹<?php echo number_format($product['Price'], 2); ?></p>
        <p><strong>Category:</strong> <?php echo $product['Category_Name']; ?></p>
        <p><strong>Material:</strong> <?php echo $product['Material']; ?></p>
        <p><strong>Color:</strong> <?php echo $product['Color']; ?></p>
        <p><strong>Dimensions:</strong> <?php echo $product['Dimensions']; ?></p>
        <a href="product-details.php?id=<?php echo $product['Pdt_Det_Id']; ?>" class="view-more">View More →</a>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <div class="no-products-message" style="grid-column: 1 / -1; text-align: center; padding: 2rem;">
      <h3>No products found matching your search criteria</h3>
      <p>Try adjusting your search or <a href="products.php">view all products</a>.</p>
    </div>
  <?php endif; ?>
</div>