<?php
session_start();

// Include the database connection file
require '../config/dbConnect.php';

// Check if the user is logged in
if (!isset($_SESSION['Email_id'])) {
  header('Location: login.php'); // Redirect to login if not logged in
  exit;
}

// Get the order ID from the URL
if (!isset($_GET['order_id'])) {
  header('Location: cart.php'); // Redirect if no order ID is provided
  exit;
}

$orderId = $_GET['order_id'];

// Fetch order details from the database
$sql = "
    SELECT o.Order_Id, o.Order_Date, o.Total_Amount, o.Delivery_Address, o.Delivery_Pincode, 
           p.Payment_Method, p.Payment_Status, p.Payment_Date
    FROM order_tbl o
    JOIN payment_tbl p ON o.Order_Id = p.Order_Id
    WHERE o.Order_Id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $orderId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
  // If no order is found, redirect to the cart page
  header('Location: cart.php');
  exit;
}

$orderDetails = $result->fetch_assoc();

// Fetch order items from the database
$sql = "
    SELECT p.Product_Name, od.Qty, od.Unit_Price, od.Sub_Total
    FROM order_detail_tbl od
    JOIN product_details_tbl pd ON od.Pdt_Det_Id = pd.Pdt_Det_Id
    JOIN product_tbl p ON pd.Product_Id = p.Product_Id
    WHERE od.Order_Id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $orderId);
$stmt->execute();
$result = $stmt->get_result();

$orderItems = [];
while ($row = $result->fetch_assoc()) {
  $orderItems[] = $row;
}

// Include the FPDF library
require('../fpdf/fpdf.php');

// Function to generate PDF receipt
function generatePDFReceipt($orderDetails, $orderItems)
{
  $pdf = new FPDF();
  $pdf->AddPage();
  $pdf->SetFont('Arial', 'B', 18);

  // Company Name
  $pdf->SetTextColor(0, 0, 0); // Black color
  $pdf->Cell(0, 10, 'PartsMart', 0, 1, 'C');
  $pdf->Ln(10); // Add some space

  // Thank You Message
  $pdf->SetFont('Arial', 'I', 14);
  $pdf->SetTextColor(50, 50, 50); // Dark gray color
  $pdf->Cell(0, 10, 'Thank you for your purchase!', 0, 1, 'C');
  $pdf->Ln(10); // Add some space

  // Order Details
  $pdf->SetFont('Arial', 'B', 14);
  $pdf->SetTextColor(0, 0, 0); // Black color
  $pdf->Cell(0, 10, 'Order Details', 0, 1, 'L');
  $pdf->SetFont('Arial', '', 12);
  $pdf->Cell(0, 10, 'Order Date: ' . $orderDetails['Order_Date'], 0, 1);
  $pdf->Cell(0, 10, 'Total Amount: Rs. ' . number_format($orderDetails['Total_Amount'], 2), 0, 1);
  $pdf->Cell(0, 10, 'Payment Method: ' . $orderDetails['Payment_Method'], 0, 1);
  $pdf->Cell(0, 10, 'Payment Status: ' . $orderDetails['Payment_Status'], 0, 1);
  $pdf->Ln(10); // Add some space

  // Order Items
  $pdf->SetFont('Arial', 'B', 14);
  $pdf->Cell(0, 10, 'Order Items:', 0, 1, 'L');
  $pdf->SetFont('Arial', '', 12);

  // Table Header
  $pdf->SetFillColor(200, 220, 255); // Light blue background for header
  $pdf->Cell(100, 10, 'Product Name', 1, 0, 'C', true);
  $pdf->Cell(40, 10, 'Quantity', 1, 0, 'C', true);
  $pdf->Cell(40, 10, 'Price', 1, 1, 'C', true);

  // Table Rows
  $pdf->SetFillColor(245, 245, 245); // Light gray background for rows
  $fill = false; // Alternate row background
  foreach ($orderItems as $item) {
    $pdf->Cell(100, 10, $item['Product_Name'], 1, 0, 'L', $fill);
    $pdf->Cell(40, 10, $item['Qty'], 1, 0, 'C', $fill);
    $pdf->Cell(40, 10, 'Rs. ' . number_format($item['Sub_Total'], 2), 1, 1, 'R', $fill);
    $fill = !$fill; // Alternate row background
  }

  // Footer Message
  $pdf->Ln(15); // Add some space
  $pdf->SetFont('Arial', 'I', 12);
  $pdf->SetTextColor(100, 100, 100); // Gray color
  $pdf->Cell(0, 10, 'We appreciate your business and look forward to serving you again!', 0, 1, 'C');

  // Output the PDF
  $pdf->Output('F', '../receipts/receipt_' . $orderDetails['Order_Id'] . '.pdf');
}

// Generate the PDF receipt
generatePDFReceipt($orderDetails, $orderItems);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Order Confirmation</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../styles/navbar.css" />
  <link rel="stylesheet" href="../styles/footer.css" />
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      margin: 0;
      padding: 0;
      background-color: #f5f5f5;
    }

    .container {
      max-width: 1200px;
      margin: 13vh auto 5vh;
      padding: 2rem;
      background-color: #fff;
      border-radius: 12px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
    }

    .order-confirmation-title {
      font-size: 2.5rem;
      font-weight: 600;
      margin-bottom: 2rem;
      color: #2a2a2a;
      text-align: center;
    }

    .order-details {
      margin-bottom: 2rem;
    }

    .order-details h2 {
      font-size: 1.8rem;
      color: #2a2a2a;
      margin-bottom: 1.5rem;
    }

    .order-details p {
      font-size: 1.1rem;
      color: #4a4a4a;
      margin-bottom: 1rem;
    }

    .order-items {
      margin-top: 2rem;
    }

    .order-item {
      display: flex;
      justify-content: space-between;
      padding: 1rem 0;
      border-bottom: 1px solid #e0e0e0;
    }

    .order-item:last-child {
      border-bottom: none;
    }

    .order-item .item-name {
      font-weight: 500;
      color: #2a2a2a;
    }

    .order-item .item-quantity,
    .order-item .item-price {
      color: #4a4a4a;
    }

    .download-receipt-btn {
      background-color: #373737;
      color: white;
      padding: 1rem 2rem;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-size: 1.1rem;
      transition: all 0.3s ease;
      margin-top: 2rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 1px;
    }

    .download-receipt-btn:hover {
      background-color: white;
      color: black;
      transform: translateY(-2px);
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }
  </style>
</head>

<body>
  <?php include('header.php'); ?>

  <div class="container">
    <h1 class="order-confirmation-title">Order Confirmation</h1>

    <div class="order-details">
      <h2>Order Details</h2>
      <p><strong>Order ID:</strong> <?php echo $orderDetails['Order_Id']; ?></p>
      <p><strong>Order Date:</strong> <?php echo $orderDetails['Order_Date']; ?></p>
      <p><strong>Total Amount:</strong> Rs. <?php echo number_format($orderDetails['Total_Amount'], 2); ?></p>
      <p><strong>Payment Method:</strong> <?php echo $orderDetails['Payment_Method']; ?></p>
      <p><strong>Payment Status:</strong> <?php echo $orderDetails['Payment_Status']; ?></p>
      <p><strong>Delivery Address:</strong> <?php echo $orderDetails['Delivery_Address']; ?></p>
      <p><strong>Delivery Pincode:</strong> <?php echo $orderDetails['Delivery_Pincode']; ?></p>
    </div>

    <div class="order-items">
      <h2>Order Items</h2>
      <?php foreach ($orderItems as $item): ?>
        <div class="order-item">
          <span class="item-name"><?php echo $item['Product_Name']; ?></span>
          <span class="item-quantity"><?php echo $item['Qty']; ?></span>
          <span class="item-price">Rs. <?php echo number_format($item['Sub_Total'], 2); ?></span>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Download Receipt Button -->
    <a href="../receipts/receipt_<?php echo $orderDetails['Order_Id']; ?>.pdf"
      download="receipt_<?php echo $orderDetails['Order_Id']; ?>.pdf">
      <button class="download-receipt-btn">Download Payment Receipt</button>
    </a>
  </div>

  <?php include('footer.php'); ?>
</body>

</html>