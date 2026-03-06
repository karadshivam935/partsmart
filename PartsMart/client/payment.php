<?php
// Start the session for order tracking and redirection
session_start();

// Dummy data (this can be replaced with actual order details)
$orderSuccess = false;
$orderMessage = '';

// Check if payment was processed
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['payment_method'])) {
    // Dummy payment processing (you can replace this with actual payment gateway integration)
    $paymentMethod = $_POST['payment_method'];

    if ($paymentMethod === 'credit_card' || $paymentMethod === 'debit_card') {
      // Order is successfully placed
      $orderSuccess = true;
      $orderMessage = "Payment via $paymentMethod was successful! Your order has been placed.";
    } else {
      $orderMessage = "Invalid payment method selected!";
    }
  } else {
    $orderMessage = "Please select a payment method.";
  }
}

// Redirect to products.php after 3 seconds if order is successful
if ($orderSuccess) {
  // Wait for 3 seconds to show the success message
  echo '<script>setTimeout(function(){ window.location.href = "products.php"; }, 3000);</script>';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dummy Payment System</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <style>
    .payment-container {
      max-width: 400px;
      margin: 50px auto;
      padding: 20px;
      background-color: #fff;
      border-radius: 8px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .btn {
      width: 100%;
      padding: 10px;
      border-radius: 5px;
      margin-bottom: 10px;
      font-size: 1.1rem;
      cursor: pointer;
    }

    .btn-credit {
      background-color: #373737;
      color: white;
    }

    .btn-credit:hover {
      background-color: white;
      color: #373737;
    }

    .btn-debit {
      background-color: #373737;
      color: white;
    }

    .btn-debit:hover {
      background-color: white;
      color: #373737;
    }

    .btn:hover {
      opacity: 0.9;
    }
  </style>
</head>

<body class="bg-gray-100">

  <div class="payment-container">
    <h2 class="text-center text-xl font-semibold mb-4">Select Payment Method</h2>

    <!-- Display order success message if applicable -->
    <?php if ($orderSuccess): ?>
      <div class="bg-green-200 p-4 rounded-md text-center text-green-700 mb-4">
        <?php echo $orderMessage; ?>
      </div>
    <?php elseif ($orderMessage): ?>
      <div class="bg-red-200 p-4 rounded-md text-center text-red-700 mb-4">
        <?php echo $orderMessage; ?>
      </div>
    <?php endif; ?>

    <!-- Payment options form -->
    <form method="POST">
      <button type="submit" name="payment_method" value="credit_card" class="btn btn-credit">Pay with Credit
        Card</button>
      <button type="submit" name="payment_method" value="debit_card" class="btn btn-debit">Pay with Debit Card</button>
    </form>
  </div>

</body>

</html>