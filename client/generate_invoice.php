<?php
session_start();
require '../config/dbConnect.php';
require('../fpdf/fpdf.php');

// Start output buffering to prevent stray output
ob_start();

if (!isset($_SESSION['Email_id']) || !isset($_GET['order_id'])) {
  header('HTTP/1.1 403 Forbidden');
  echo "Access Denied";
  exit;
}

$email = $_SESSION['Email_id'];
$orderId = (int) $_GET['order_id'];

// Verify order belongs to user
$sql = "SELECT COUNT(*) FROM order_tbl WHERE Order_Id = ? AND Email_Id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
  header('HTTP/1.1 500 Internal Server Error');
  echo "Database Error: " . $conn->error;
  exit;
}
$stmt->bind_param("is", $orderId, $email);
$stmt->execute();
$stmt->bind_result($count);
$stmt->fetch();
$stmt->close();

if ($count == 0) {
  header('HTTP/1.1 403 Forbidden');
  echo "Order not found or unauthorized";
  exit;
}

// Fetch order details including Transaction_Id
$sql = "
    SELECT o.Order_Id, o.Order_Date, o.Total_Amount, o.Delivery_Address, o.Delivery_Pincode, 
           p.Payment_Method, p.Payment_Status, p.Payment_Date, p.Transaction_Id
    FROM order_tbl o
    JOIN payment_tbl p ON o.Order_Id = p.Order_Id
    WHERE o.Order_Id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $orderId);
$stmt->execute();
$result = $stmt->get_result();
$orderDetails = $result->fetch_assoc();
if (!$orderDetails) {
  header('HTTP/1.1 404 Not Found');
  echo "Order details not found";
  exit;
}

// Fetch order items
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

// Generate PDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 18);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 10, 'PartsMart', 0, 1, 'C');
$pdf->Ln(10);

$pdf->SetFont('Arial', 'I', 14);
$pdf->SetTextColor(50, 50, 50);
$pdf->Cell(0, 10, 'Thank you for your purchase!', 0, 1, 'C');
$pdf->Ln(10);

$pdf->SetFont('Arial', 'B', 14);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 10, 'Order Details', 0, 1, 'L');
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 10, 'Order Date: ' . $orderDetails['Order_Date'], 0, 1);
$pdf->Cell(0, 10, 'Total Amount: Rs. ' . number_format($orderDetails['Total_Amount'], 2), 0, 1);
$pdf->Cell(0, 10, 'Payment Method: ' . $orderDetails['Payment_Method'], 0, 1);
$pdf->Cell(0, 10, 'Payment Status: ' . $orderDetails['Payment_Status'], 0, 1);
$pdf->Cell(0, 10, 'Transaction ID: ' . ($orderDetails['Transaction_Id'] ?? 'N/A'), 0, 1); // Handle null Transaction_Id
$pdf->Ln(10);

$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, 'Order Items:', 0, 1, 'L');
$pdf->SetFont('Arial', '', 12);
$pdf->SetFillColor(200, 220, 255);
$pdf->Cell(100, 10, 'Product Name', 1, 0, 'C', true);
$pdf->Cell(40, 10, 'Quantity', 1, 0, 'C', true);
$pdf->Cell(40, 10, 'Price', 1, 1, 'C', true);

$pdf->SetFillColor(245, 245, 245);
$fill = false;
foreach ($orderItems as $item) {
  $pdf->Cell(100, 10, $item['Product_Name'], 1, 0, 'L', $fill);
  $pdf->Cell(40, 10, $item['Qty'], 1, 0, 'C', $fill);
  $pdf->Cell(40, 10, 'Rs. ' . number_format($item['Sub_Total'], 2), 1, 1, 'R', $fill);
  $fill = !$fill;
}

$pdf->Ln(15);
$pdf->SetFont('Arial', 'I', 12);
$pdf->SetTextColor(100, 100, 100);
$pdf->Cell(0, 10, 'We appreciate your business and look forward to serving you again!', 0, 1, 'C');

// Clear buffer and output PDF
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="invoice_' . $orderId . '.pdf"');
ob_end_clean(); // Ensure no stray output corrupts the PDF
$pdf->Output('D');
exit;
?>