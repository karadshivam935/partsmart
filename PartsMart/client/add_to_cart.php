<?php
session_start();

// Include the database connection file
require '../config/dbConnect.php';

// Check if the user is logged in
if (!isset($_SESSION['Email_id'])) {
  echo json_encode(['success' => false, 'message' => 'Please login to add items to cart.']);
  exit;
}

// Get the action from the request (add, update, delete)
$action = isset($_POST['action']) ? $_POST['action'] : '';

// Validate the action
if (!in_array($action, ['add', 'update', 'delete'])) {
  echo json_encode(['success' => false, 'message' => 'Invalid action.']);
  exit;
}

// Get product details from the request
$pdtDetId = isset($_POST['pdt_det_id']) ? intval($_POST['pdt_det_id']) : 0;
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;

// Validate product detail ID and quantity
if ($pdtDetId <= 0 || $quantity <= 0) {
  echo json_encode(['success' => false, 'message' => 'Invalid product details or quantity.']);
  exit;
}

// Fetch product details from the database
$sql = "SELECT pd.Price, pd.Stock_Qty, p.MOQ 
        FROM product_details_tbl pd
        JOIN product_tbl p ON pd.Product_Id = p.Product_Id
        WHERE pd.Pdt_Det_Id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $pdtDetId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
  echo json_encode(['success' => false, 'message' => 'Product not found.']);
  exit;
}

$product = $result->fetch_assoc();
$price = $product['Price'];
$stockQty = $product['Stock_Qty'];
$moq = $product['MOQ'];

// Validate quantity against MOQ and stock availability
if ($quantity < $moq) {
  echo json_encode(['success' => false, 'message' => 'Minimum order quantity is ' . $moq . '.']);
  exit;
}

if ($quantity > $stockQty) {
  echo json_encode(['success' => false, 'message' => 'Only ' . $stockQty . ' items are available in stock.']);
  exit;
}

// Fetch the user's cart ID
$email = $_SESSION['Email_id'];
$sql = "SELECT Cart_Id FROM cart_tbl WHERE Email_Id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
  // Fetch the last Cart_Id from the cart_tbl
  $sql = "SELECT MAX(Cart_Id) AS last_cart_id FROM cart_tbl";
  $stmt = $conn->prepare($sql);
  $stmt->execute();
  $result = $stmt->get_result();
  $row = $result->fetch_assoc();
  $lastCartId = $row['last_cart_id'];

  // Generate the new Cart_Id by incrementing the last Cart_Id
  $newCartId = $lastCartId + 1;

  // Create a new cart for the user with the new Cart_Id
  $sql = "INSERT INTO cart_tbl (Cart_Id, Email_Id, Creation_Date) VALUES (?, ?, NOW())";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("is", $newCartId, $email);
  $stmt->execute();
  $cartId = $newCartId;
} else {
  // Use the existing cart
  $row = $result->fetch_assoc();
  $cartId = $row['Cart_Id'];
}

// Handle CRUD operations
switch ($action) {
  case 'add':
    // Check if the product already exists in the cart
    $sql = "SELECT Qty FROM cart_detail_tbl WHERE Cart_Id = ? AND Pdt_Det_Id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $cartId, $pdtDetId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
      // Update the quantity if the product already exists in the cart
      $row = $result->fetch_assoc();
      $newQty = $row['Qty'] + $quantity;

      if ($newQty > $stockQty) {
        echo json_encode(['success' => false, 'message' => 'Only ' . $stockQty . ' items are available in stock.']);
        exit;
      }

      $sql = "UPDATE cart_detail_tbl SET Qty = ?, Price = ? WHERE Cart_Id = ? AND Pdt_Det_Id = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("idii", $newQty, $price, $cartId, $pdtDetId);
    } else {
      // Add the product to the cart if it doesn't exist
      $sql = "INSERT INTO cart_detail_tbl (Cart_Id, Pdt_Det_Id, Qty, Price) VALUES (?, ?, ?, ?)";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("iiid", $cartId, $pdtDetId, $quantity, $price);
    }

    if ($stmt->execute()) {
      // Decrement the stock quantity in the database
      $newStockQty = $stockQty - $quantity;
      $sql = "UPDATE product_details_tbl SET Stock_Qty = ? WHERE Pdt_Det_Id = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ii", $newStockQty, $pdtDetId);
      $stmt->execute();

      echo json_encode(['success' => true, 'message' => 'Product added to cart successfully.']);
    } else {
      echo json_encode(['success' => false, 'message' => 'Failed to add product to cart.']);
    }
    break;

  default:
    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    break;
}

$stmt->close();
$conn->close();
?>