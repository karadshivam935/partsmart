<?php
session_start();

// Include the database connection file
require '../config/dbConnect.php';

// Check if the user is logged in
if (!isset($_SESSION['Email_id'])) {
  header('Location: login.php');
  exit;
}

$email = $_SESSION['Email_id'];

// Fetch the user's cart ID
$sql = "SELECT Cart_Id FROM cart_tbl WHERE Email_Id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
  header('Location: cart.php');
  exit;
}

$row = $result->fetch_assoc();
$cartId = $row['Cart_Id'];

// Fetch cart items
$sql = "
    SELECT pd.Pdt_Det_Id, p.Product_Name, pd.Price, cd.Qty, pd.ImagePath
    FROM cart_detail_tbl cd
    JOIN product_details_tbl pd ON cd.Pdt_Det_Id = pd.Pdt_Det_Id
    JOIN product_tbl p ON pd.Product_Id = p.Product_Id
    WHERE cd.Cart_Id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $cartId);
$stmt->execute();
$result = $stmt->get_result();

$cartItems = [];
$totalAmount = 0;
while ($row = $result->fetch_assoc()) {
  $cartItems[] = [
    'id' => $row['Pdt_Det_Id'],
    'name' => $row['Product_Name'],
    'price' => $row['Price'],
    'quantity' => $row['Qty'],
    'image' => $row['ImagePath']
  ];
  $totalAmount += $row['Price'] * $row['Qty'];
}

// Calculate shipping and tax
$shipping = 150.00;
$tax = $totalAmount * 0.18;
$totalAmount += $shipping + $tax;

// Fetch user details
$sql = "SELECT First_Name, Last_Name, Address, Pincode, Phone_Number FROM user_detail_tbl WHERE Email_Id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$userDetails = $result->fetch_assoc();

// Handle form submission with validation
$orderPlaced = false;
$invoiceUrl = '';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $fullName = $_POST['full-name'];
  $deliveryAddress = $_POST['address'];
  $deliveryPincode = $_POST['pincode'];
  $phoneNumber = $_POST['phone'];
  $paymentMethod = $_POST['payment_method'];

  // Validation
  if (empty($fullName) || !preg_match('/^[a-zA-Z\s]{2,}$/', $fullName)) {
    $errors['full-name'] = "Full name must be at least 2 characters and contain only letters.";
  }
  if (empty($deliveryAddress) || strlen($deliveryAddress) < 5) {
    $errors['address'] = "Address must be at least 5 characters.";
  }
  if (empty($deliveryPincode) || !preg_match('/^\d{6}$/', $deliveryPincode)) {
    $errors['pincode'] = "Pincode must be exactly 6 digits.";
  }
  if (empty($phoneNumber) || !preg_match('/^\d{10}$/', $phoneNumber)) {
    $errors['phone'] = "Phone number must be exactly 10 digits.";
  }
  if (empty($paymentMethod) || !in_array($paymentMethod, ['credit_card', 'debit_card'])) {
    $errors['payment_method'] = "Please select a valid payment method.";
  }

  // Card details validation (only for display, ignored on submit)
  if ($paymentMethod === 'credit_card' || $paymentMethod === 'debit_card') {
    $cardNumber = $_POST['card_number'] ?? '';
    $expiryDate = $_POST['expiry_date'] ?? '';
    $cvv = $_POST['cvv'] ?? '';

    if (!preg_match('/^\d{16}$/', $cardNumber)) {
      $errors['card_number'] = "Card number must be 16 digits.";
    }
    if (!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $expiryDate)) {
      $errors['expiry_date'] = "Expiry date must be in MM/YY format.";
    }
    if (!preg_match('/^\d{3}$/', $cvv)) {
      $errors['cvv'] = "CVV must be 3 digits.";
    }
  }

  // Proceed if no errors (ignore card details)
  if (empty($errors)) {
    // Generate a dummy transaction ID
    $transactionId = 'TXN' . rand(100000, 999999);
    $paymentStatus = 'Completed';

    // Get new Order_Id
    $sql = "SELECT MAX(Order_Id) AS last_order_id FROM order_tbl";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $newOrderId = ($row['last_order_id'] ?? 0) + 1;

    // Insert order
    $sql = "
            INSERT INTO order_tbl (Order_Id, Email_Id, Order_Date, Total_Amount, Order_Status, Delivery_Address, Delivery_Pincode)
            VALUES (?, ?, NOW(), ?, 'Processing', ?, ?)
        ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isdss", $newOrderId, $email, $totalAmount, $deliveryAddress, $deliveryPincode);
    $stmt->execute();

    // Insert order details
    foreach ($cartItems as $item) {
      $sql = "
                INSERT INTO order_detail_tbl (Order_Id, Pdt_Det_Id, Qty, Unit_Price, Sub_Total)
                VALUES (?, ?, ?, ?, ?)
            ";
      $stmt = $conn->prepare($sql);
      $subTotal = $item['price'] * $item['quantity'];
      $stmt->bind_param("iiidd", $newOrderId, $item['id'], $item['quantity'], $item['price'], $subTotal);
      $stmt->execute();
    }

    // Insert payment details
    $sql = "
            INSERT INTO payment_tbl (Order_Id, Payment_Method, Payment_Date, Total_Amount, Payment_Status, Transaction_Id)
            VALUES (?, ?, NOW(), ?, ?, ?)
        ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isdss", $newOrderId, $paymentMethod, $totalAmount, $paymentStatus, $transactionId);
    $stmt->execute();

    // Clear cart
    $sql = "DELETE FROM cart_detail_tbl WHERE Cart_Id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $cartId);
    $stmt->execute();

    // Set flag and invoice URL
    $orderPlaced = true;
    $invoiceUrl = "generate_invoice.php?order_id=" . $newOrderId;
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Checkout</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
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

    .container {
      max-width: 1200px;
      margin: 13vh auto 5vh;
      padding: 2rem;
      background-color: #fff;
      border-radius: 12px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
    }

    .checkout-title {
      font-size: 2.5rem;
      font-weight: 600;
      margin-bottom: 2rem;
      color: #2a2a2a;
      text-align: center;
    }

    .checkout-container {
      display: grid;
      grid-template-columns: 2fr 1fr;
      gap: 2rem;
    }

    .checkout-form,
    .order-success {
      background-color: #ffffff;
      padding: 2rem;
      border-radius: 12px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
    }

    .checkout-summary {
      background-color: #ffffff;
      padding: 2rem;
      border-radius: 12px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
      height: fit-content;
      position: sticky;
      top: 2rem;
    }

    .form-group {
      margin-bottom: 1.5rem;
    }

    .form-group label {
      display: block;
      font-weight: 600;
      margin-bottom: 0.5rem;
    }

    .form-group input,
    .form-group select {
      width: 100%;
      padding: 0.75rem;
      border: 1px solid #e0e0e0;
      border-radius: 8px;
      font-size: 1rem;
    }

    .form-group .error {
      color: red;
      font-size: 0.9rem;
      margin-top: 0.25rem;
      display: block;
    }

    .checkout-btn,
    .download-invoice-btn {
      background-color: #373737;
      color: white;
      padding: 1rem 2rem;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-size: 1.1rem;
      transition: all 0.3s ease;
      width: 100%;
      margin-top: 1.5rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 1px;
      text-decoration: none;
      display: inline-block;
      text-align: center;
    }

    .checkout-btn:hover,
    .download-invoice-btn:hover {
      background-color: white;
      color: black;
      transform: translateY(-2px);
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .summary-item {
      display: flex;
      justify-content: space-between;
      margin-bottom: 1rem;
      padding: 0.5rem 0;
      font-size: 1.1rem;
    }

    .summary-total {
      border-top: 2px solid #e0e0e0;
      padding-top: 1rem;
      margin-top: 1rem;
      font-weight: 600;
      font-size: 1.3rem;
      color: #2a2a2a;
    }

    .empty-cart,
    .order-success {
      text-align: center;
      padding: 2rem;
    }

    #card-details {
      display: none;
    }

    @media (max-width: 768px) {
      .checkout-container {
        grid-template-columns: 1fr;
      }

      .checkout-summary {
        margin-top: 2rem;
        position: static;
      }
    }
  </style>
</head>

<body>
  <?php include('header.php'); ?>

  <div class="container">
    <h1 class="checkout-title">Checkout</h1>

    <?php if ($orderPlaced): ?>
      <div class="order-success">
        <h2>Order Placed Successfully!</h2>
        <p>Your order has been processed. You can download your invoice below.</p>
        <a href="<?php echo $invoiceUrl; ?>" class="download-invoice-btn"
          download="invoice_<?php echo $newOrderId; ?>.pdf">Download Invoice</a>
        <a href="products.php" class="checkout-btn" style="margin-top: 1rem;">Continue Shopping</a>
      </div>
    <?php elseif (empty($cartItems)): ?>
      <div class="empty-cart">
        <p>Your cart is empty</p>
        <a href="products.php" class="checkout-btn">Continue Shopping</a>
      </div>
    <?php else: ?>
      <div class="checkout-container">
        <!-- Checkout Form -->
        <div class="checkout-form">
          <h2>Shipping Details</h2>
          <form id="checkout-form" method="POST">
            <div class="form-group">
              <label for="full-name">Full Name</label>
              <input type="text" id="full-name" name="full-name"
                value="<?php echo isset($_POST['full-name']) ? htmlspecialchars($_POST['full-name']) : htmlspecialchars($userDetails['First_Name'] . ' ' . $userDetails['Last_Name']); ?>"
                required>
              <?php if (isset($errors['full-name'])): ?>
                <span class="error"><?php echo $errors['full-name']; ?></span>
              <?php endif; ?>
            </div>
            <div class="form-group">
              <label for="address">Address</label>
              <input type="text" id="address" name="address"
                value="<?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : htmlspecialchars($userDetails['Address']); ?>"
                required>
              <?php if (isset($errors['address'])): ?>
                <span class="error"><?php echo $errors['address']; ?></span>
              <?php endif; ?>
            </div>
            <div class="form-group">
              <label for="pincode">Pincode</label>
              <input type="text" id="pincode" name="pincode"
                value="<?php echo isset($_POST['pincode']) ? htmlspecialchars($_POST['pincode']) : htmlspecialchars($userDetails['Pincode']); ?>"
                required>
              <?php if (isset($errors['pincode'])): ?>
                <span class="error"><?php echo $errors['pincode']; ?></span>
              <?php endif; ?>
            </div>
            <div class="form-group">
              <label for="phone">Phone Number</label>
              <input type="text" id="phone" name="phone"
                value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : htmlspecialchars($userDetails['Phone_Number']); ?>"
                required>
              <?php if (isset($errors['phone'])): ?>
                <span class="error"><?php echo $errors['phone']; ?></span>
              <?php endif; ?>
            </div>
            <div class="form-group">
              <label for="payment-method">Payment Method</label>
              <select id="payment-method" name="payment_method" required>
                <option value="">Select Payment Method</option>
                <option value="credit_card" <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] === 'credit_card') ? 'selected' : ''; ?>>Credit Card</option>
                <option value="debit_card" <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] === 'debit_card') ? 'selected' : ''; ?>>Debit Card</option>
              </select>
              <?php if (isset($errors['payment_method'])): ?>
                <span class="error"><?php echo $errors['payment_method']; ?></span>
              <?php endif; ?>
            </div>

            <!-- Card Details Section (Validated but Ignored on Submit) -->
            <div id="card-details">
              <div class="form-group">
                <label for="card_number">Card Number</label>
                <input type="text" id="card_number" name="card_number" maxlength="16"
                  value="<?php echo isset($_POST['card_number']) ? htmlspecialchars($_POST['card_number']) : ''; ?>"
                  placeholder="1234 5678 9012 3456">
                <?php if (isset($errors['card_number'])): ?>
                  <span class="error"><?php echo $errors['card_number']; ?></span>
                <?php endif; ?>
              </div>
              <div class="form-group">
                <label for="expiry_date">Expiry Date</label>
                <input type="text" id="expiry_date" name="expiry_date" maxlength="5"
                  value="<?php echo isset($_POST['expiry_date']) ? htmlspecialchars($_POST['expiry_date']) : ''; ?>"
                  placeholder="MM/YY">
                <?php if (isset($errors['expiry_date'])): ?>
                  <span class="error"><?php echo $errors['expiry_date']; ?></span>
                <?php endif; ?>
              </div>
              <div class="form-group">
                <label for="cvv">CVV</label>
                <input type="password" id="cvv" name="cvv" maxlength="3"
                  value="<?php echo isset($_POST['cvv']) ? htmlspecialchars($_POST['cvv']) : ''; ?>" placeholder="123">
                <?php if (isset($errors['cvv'])): ?>
                  <span class="error"><?php echo $errors['cvv']; ?></span>
                <?php endif; ?>
              </div>
            </div>

            <button type="submit" class="checkout-btn">Place Order</button>
          </form>
        </div>

        <!-- Order Summary -->
        <div class="checkout-summary">
          <h2>Order Summary</h2>
          <?php foreach ($cartItems as $item): ?>
            <div class="summary-item">
              <span><?php echo htmlspecialchars($item['name']); ?> (x<?php echo $item['quantity']; ?>)</span>
              <span>₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
            </div>
          <?php endforeach; ?>
          <div class="summary-item">
            <span>Shipping Fee</span>
            <span>₹150.00</span>
          </div>
          <div class="summary-item">
            <span>GST (18%)</span>
            <span>₹<?php echo number_format($tax, 2); ?></span>
          </div>
          <div class="summary-item summary-total">
            <span>Total</span>
            <span>₹<?php echo number_format($totalAmount, 2); ?></span>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <?php include('footer.php'); ?>

  <script>
    // Show/hide card details based on payment method
    document.getElementById('payment-method').addEventListener('change', function () {
      const cardDetails = document.getElementById('card-details');
      if (this.value === 'credit_card' || this.value === 'debit_card') {
        cardDetails.style.display = 'block';
      } else {
        cardDetails.style.display = 'none';
      }
    });

    // Ensure card details are shown if payment method is pre-selected (e.g., after form submission with errors)
    window.onload = function () {
      const paymentMethod = document.getElementById('payment-method').value;
      const cardDetails = document.getElementById('card-details');
      if (paymentMethod === 'credit_card' || paymentMethod === 'debit_card') {
        cardDetails.style.display = 'block';
      }
    };
  </script>
</body>

</html>