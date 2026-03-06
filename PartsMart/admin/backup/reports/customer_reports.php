<?php
require_once '../dbConnect.php';
require_once '../../fpdf/fpdf.php';

// Fetch all products for the dropdown (unchanged)
$product_query = "SELECT Product_Id, Product_Name FROM product_tbl ORDER BY Product_Name";
$product_result = $conn->query($product_query);
$products = [];
while ($row = $product_result->fetch_assoc()) {
  $products[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // PHP processing logic remains unchanged
  $report_type = $_POST['report_type'];
  $start_date = $_POST['start_date'];
  $end_date = $_POST['end_date'];
  $selected_product = isset($_POST['product_id']) ? $_POST['product_id'] : null;

  $report_data = [];

  if ($report_type == "clv_report") {
    $query = "SELECT CONCAT(u.First_Name, ' ', u.Last_Name) AS Customer_Name, 
                  COUNT(o.Order_Id) AS Total_Orders, SUM(o.Total_Amount) AS Total_Spend, 
                  AVG(o.Total_Amount) AS Avg_Order_Value, 
                  CASE WHEN u.Profile_Status = 1 THEN 'Active' ELSE 'Inactive' END AS Profile_Status
                  FROM user_detail_tbl u
                  LEFT JOIN order_tbl o ON u.Email_Id = o.Email_Id
                  WHERE (o.Order_Date BETWEEN ? AND ? OR o.Order_Date IS NULL)
                  GROUP BY u.Email_Id, u.First_Name, u.Last_Name, u.Profile_Status
                  ORDER BY Total_Spend DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
      $report_data[] = $row;
    }
    $stmt->close();
  }

  if ($report_type == "review_report") {
    $query = "SELECT r.Review_Id, CONCAT(u.First_Name, ' ', u.Last_Name) AS Customer_Name, 
                  p.Product_Id, p.Product_Name, r.Review_Text, r.Review_Date
                  FROM review_tbl r
                  JOIN user_detail_tbl u ON r.Email_Id = u.Email_Id
                  JOIN product_tbl p ON r.Product_Id = p.Product_Id
                  WHERE r.Review_Date BETWEEN ? AND ?";
    if ($selected_product && $selected_product !== "all") {
      $query .= " AND p.Product_Id = ?";
      $stmt = $conn->prepare($query);
      $stmt->bind_param("ssi", $start_date, $end_date, $selected_product);
    } else {
      $query .= " GROUP BY p.Product_Id, p.Product_Name, r.Review_Id, u.First_Name, u.Last_Name, r.Review_Text, r.Review_Date";
      $stmt = $conn->prepare($query);
      $stmt->bind_param("ss", $start_date, $end_date);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
      $report_data[$row['Product_Id']][] = $row;
    }
    $stmt->close();
  }

  if (isset($_POST['download']) && !empty($report_data)) {
    // PDF generation remains unchanged
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 18);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(0, 10, 'PartsMart', 0, 1, 'C');
    $pdf->Ln(5);
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor(50, 50, 50);
    $pdf->Cell(0, 10, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 1, 'R');
    $pdf->Ln(5);
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(0, 10, 'Customer Report', 0, 1, 'L');
    $pdf->SetFont('Arial', '', 12);
    $title = ($report_type == 'clv_report' ? 'Customer Lifetime Value' : 'Customer Review') . ' Report: ' . $start_date . ' to ' . $end_date;
    if ($report_type == "review_report" && $selected_product !== "all") {
      $product_name = array_column($products, 'Product_Name', 'Product_Id')[$selected_product];
      $title .= " (Product: $product_name)";
    }
    $pdf->Cell(0, 10, $title, 0, 1, 'L');
    $pdf->Ln(10);

    if ($report_type == "clv_report") {
      $table_width = 190;
      $page_width = 210;
      $pdf->SetLeftMargin(($page_width - $table_width) / 2);
      $pdf->SetFont('Arial', 'B', 11);
      $pdf->SetFillColor(200, 220, 255);
      $pdf->Cell(50, 10, 'Customer Name', 1, 0, 'C', true);
      $pdf->Cell(30, 10, 'Total Orders', 1, 0, 'C', true);
      $pdf->Cell(40, 10, 'Total Spend', 1, 0, 'C', true);
      $pdf->Cell(40, 10, 'Avg Order Value', 1, 0, 'C', true);
      $pdf->Cell(30, 10, 'Profile Status', 1, 1, 'C', true);
      $pdf->SetFont('Arial', '', 10);
      $pdf->SetFillColor(245, 245, 245);
      $fill = false;
      foreach ($report_data as $row) {
        $name = strlen($row['Customer_Name']) > 25 ? substr($row['Customer_Name'], 0, 22) . '...' : $row['Customer_Name'];
        $pdf->Cell(50, 10, $name, 1, 0, 'C', $fill);
        $pdf->Cell(30, 10, $row['Total_Orders'] ?? '0', 1, 0, 'C', $fill);
        $pdf->Cell(40, 10, 'Rs. ' . number_format($row['Total_Spend'] ?? 0, 2), 1, 0, 'C', $fill);
        $pdf->Cell(40, 10, 'Rs. ' . number_format($row['Avg_Order_Value'] ?? 0, 2), 1, 0, 'C', $fill);
        $pdf->Cell(30, 10, $row['Profile_Status'], 1, 1, 'C', $fill);
        $fill = !$fill;
      }
    } else {
      $table_width = 190;
      $page_width = 210;
      $pdf->SetLeftMargin(($page_width - $table_width) / 2);
      foreach ($report_data as $product_id => $reviews) {
        $product = $reviews[0];
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 10, 'Product ID: ' . $product['Product_Id'] . ' | Product Name: ' . $product['Product_Name'], 0, 1, 'L');
        $pdf->Ln(5);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetFillColor(200, 220, 255);
        $pdf->Cell(20, 10, 'Review ID', 1, 0, 'C', true);
        $pdf->Cell(40, 10, 'Customer Name', 1, 0, 'C', true);
        $pdf->Cell(100, 10, 'Review Text', 1, 0, 'C', true);
        $pdf->Cell(30, 10, 'Review Date', 1, 1, 'C', true);
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetFillColor(245, 245, 245);
        $fill = false;
        foreach ($reviews as $row) {
          $x = $pdf->GetX();
          $y = $pdf->GetY();
          $pdf->Cell(20, 10, $row['Review_Id'], 1, 0, 'C', $fill);
          $pdf->Cell(40, 10, $row['Customer_Name'], 1, 0, 'C', $fill);
          $pdf->MultiCell(100, 10, $row['Review_Text'], 1, 'C', $fill);
          $pdf->SetXY($x + 160, $y);
          $pdf->Cell(30, 10, $row['Review_Date'], 1, 1, 'C', $fill);
          $fill = !$fill;
        }
        $pdf->Ln(10);
      }
    }
    $pdf->Output('D', 'Customer_Report_' . $report_type . '_' . date('Ymd') . '.pdf');
    exit();
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Customer Reports</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 0;
      padding: 20px;
      background-color: white;
    }

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
      max-width: 750px;
      margin: 0 auto;
      background: white;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    h2 {
      text-align: center;
      color: #333;
      font-size: 24px;
    }

    .form-group {
      margin-bottom: 15px;
      display: flex;
      align-items: center;
    }

    label {
      font-weight: bold;
      width: 30%;
      font-size: 14px;
    }

    select,
    input {
      width: 70%;
      padding: 6px;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 14px;
    }

    button {
      padding: 8px 16px;
      border: none;
      background: #007bff;
      color: white;
      border-radius: 4px;
      cursor: pointer;
      font-size: 14px;
    }

    button:hover {
      background: #0056b3;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin: 15px 0;
      table-layout: fixed;
      font-size: 12px;
    }

    table,
    th,
    td {
      border: 1px solid #ddd;
    }

    th,
    td {
      padding: 8px;
      text-align: center;
      vertical-align: middle;
      word-wrap: break-word;
      overflow: hidden;
      text-overflow: ellipsis;
      max-width: 0;
    }

    th {
      background: #333;
      color: white;
      font-size: 13px;
      font-weight: bold;
    }

    td {
      background: #fff;
    }

    .clv-table th:nth-child(1),
    .clv-table td:nth-child(1) {
      width: 35%;
      /* Customer Name - increased for better visibility */
    }

    .clv-table th:nth-child(2),
    .clv-table td:nth-child(2) {
      width: 15%;
      /* Total Orders */
    }

    .clv-table th:nth-child(3),
    .clv-table td:nth-child(3) {
      width: 20%;
      /* Total Spend */
    }

    .clv-table th:nth-child(4),
    .clv-table td:nth-child(4) {
      width: 20%;
      /* Avg Order Value */
    }

    .clv-table th:nth-child(5),
    .clv-table td:nth-child(5) {
      width: 10%;
      /* Profile Status - reduced to fit better */
    }

    .review-table th:nth-child(1),
    .review-table td:nth-child(1) {
      width: 10%;
      /* Review ID */
    }

    .review-table th:nth-child(2),
    .review-table td:nth-child(2) {
      width: 25%;
      /* Customer Name */
    }

    .review-table th:nth-child(3),
    .review-table td:nth-child(3) {
      width: 50%;
      /* Review Text */
    }

    .review-table th:nth-child(4),
    .review-table td:nth-child(4) {
      width: 15%;
      /* Review Date */
      white-space: nowrap;
    }

    .product-header {
      font-size: 16px;
      font-weight: bold;
      margin: 15px 0 10px;
      color: #333;
    }

    #product_group {
      display: none;
    }
  </style>
  <script>
    function validateForm() {
      const startDate = document.getElementById('start_date').value;
      const endDate = document.getElementById('end_date').value;
      if (!startDate || !endDate) {
        alert('Please select both start and end dates.');
        return false;
      }
      return true;
    }

    function toggleProductDropdown() {
      const reportType = document.getElementById('report_type').value;
      const productGroup = document.getElementById('product_group');
      productGroup.style.display = reportType === 'review_report' ? 'flex' : 'none';
    }

    window.onload = function () {
      toggleProductDropdown();
    };
  </script>
</head>

<body>
  <div class="container">
    <h2>CUSTOMER REPORTS</h2>
    <form method="POST" onsubmit="return validateForm()">
      <div class="form-group">
        <label for="report_type">REPORT TYPE:</label>
        <select id="report_type" name="report_type" onchange="toggleProductDropdown()">
          <option value="clv_report" <?php echo (isset($_POST['report_type']) && $_POST['report_type'] == 'clv_report') ? 'selected' : ''; ?>>CLV REPORT</option>
          <option value="review_report" <?php echo (isset($_POST['report_type']) && $_POST['report_type'] == 'review_report') ? 'selected' : ''; ?>>CUSTOMER REVIEW REPORT</option>
        </select>
      </div>
      <div class="form-group" id="product_group">
        <label for="product_id">SELECT PRODUCT :</label>
        <select id="product_id" name="product_id">
          <option value="all" <?php echo (isset($_POST['product_id']) && $_POST['product_id'] == 'all') ? 'selected' : ''; ?>>All Products</option>
          <?php foreach ($products as $product): ?>
            <option value="<?php echo $product['Product_Id']; ?>" <?php echo (isset($_POST['product_id']) && $_POST['product_id'] == $product['Product_Id']) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($product['Product_Name']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label for="start_date">START DATE :</label>
        <input type="date" id="start_date" name="start_date"
          value="<?php echo isset($_POST['start_date']) ? htmlspecialchars($_POST['start_date']) : ''; ?>">
      </div>
      <div class="form-group">
        <label for="end_date">END DATE :</label>
        <input type="date" id="end_date" name="end_date"
          value="<?php echo isset($_POST['end_date']) ? htmlspecialchars($_POST['end_date']) : ''; ?>">
      </div>
      <button type="submit" name="preview">PREVIEW REPORT</button>
      <button type="submit" name="download">DOWNLOAD REPORT</button>
    </form>

    <?php
    if (isset($_POST['preview'])) {
      $report_type = $_POST['report_type'];
      $start_date = $_POST['start_date'];
      $end_date = $_POST['end_date'];
      $selected_product = isset($_POST['product_id']) ? $_POST['product_id'] : null;

      if ($report_type == "clv_report") {
        $query = "SELECT CONCAT(u.First_Name, ' ', u.Last_Name) AS Customer_Name, 
                          COUNT(o.Order_Id) AS Total_Orders, SUM(o.Total_Amount) AS Total_Spend, 
                          AVG(o.Total_Amount) AS Avg_Order_Value, 
                          CASE WHEN u.Profile_Status = 1 THEN 'Active' ELSE 'Inactive' END AS Profile_Status
                          FROM user_detail_tbl u
                          LEFT JOIN order_tbl o ON u.Email_Id = o.Email_Id
                          WHERE (o.Order_Date BETWEEN ? AND ? OR o.Order_Date IS NULL)
                          GROUP BY u.Email_Id, u.First_Name, u.Last_Name, u.Profile_Status
                          ORDER BY Total_Spend DESC";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
          echo "<p>No data found for the selected date range.</p>";
        } else {
          echo "<table class='clv-table'>";
          echo "<tr><th>CUSTOMER NAME</th><th>TOTAL ORDERS</th><th>TOTAL SPEND</th><th>AVG ORDER VALUE</th><th>PROFILE STATUS</th></tr>";
          while ($row = $result->fetch_assoc()) {
            $name = strlen($row['Customer_Name']) > 30 ? substr($row['Customer_Name'], 0, 27) . '...' : $row['Customer_Name'];
            echo "<tr>";
            echo "<td title='{$row['Customer_Name']}'>{$name}</td>";
            echo "<td>" . ($row['Total_Orders'] ?? '0') . "</td>";
            echo "<td>Rs. " . number_format($row['Total_Spend'] ?? 0, 2) . "</td>";
            echo "<td>Rs. " . number_format($row['Avg_Order_Value'] ?? 0, 2) . "</td>";
            echo "<td>{$row['Profile_Status']}</td>";
            echo "</tr>";
          }
          echo "</table>";
        }
        $stmt->close();
      }

      if ($report_type == "review_report") {
        $query = "SELECT r.Review_Id, CONCAT(u.First_Name, ' ', u.Last_Name) AS Customer_Name, 
                          p.Product_Id, p.Product_Name, r.Review_Text, r.Review_Date
                          FROM review_tbl r
                          JOIN user_detail_tbl u ON r.Email_Id = u.Email_Id
                          JOIN product_tbl p ON r.Product_Id = p.Product_Id
                          WHERE r.Review_Date BETWEEN ? AND ?";
        if ($selected_product && $selected_product !== "all") {
          $query .= " AND p.Product_Id = ?";
          $stmt = $conn->prepare($query);
          $stmt->bind_param("ssi", $start_date, $end_date, $selected_product);
        } else {
          $query .= " GROUP BY p.Product_Id, p.Product_Name, r.Review_Id, u.First_Name, u.Last_Name, r.Review_Text, r.Review_Date";
          $stmt = $conn->prepare($query);
          $stmt->bind_param("ss", $start_date, $end_date);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $review_data = [];
        while ($row = $result->fetch_assoc()) {
          $review_data[$row['Product_Id']][] = $row;
        }
        $stmt->close();

        if (empty($review_data)) {
          echo "<p>No reviews found for the selected criteria.</p>";
        } else {
          foreach ($review_data as $product_id => $reviews) {
            $product = $reviews[0];
            echo "<div class='product-header'>Product ID: {$product['Product_Id']} | Product Name: {$product['Product_Name']}</div>";
            echo "<table class='review-table'>";
            echo "<tr><th>REVIEW ID</th><th>CUSTOMER NAME</th><th>REVIEW TEXT</th><th>REVIEW DATE</th></tr>";
            foreach ($reviews as $row) {
              $name = strlen($row['Customer_Name']) > 25 ? substr($row['Customer_Name'], 0, 22) . '...' : $row['Customer_Name'];
              $review = strlen($row['Review_Text']) > 50 ? substr($row['Review_Text'], 0, 47) . '...' : $row['Review_Text'];
              echo "<tr>";
              echo "<td>{$row['Review_Id']}</td>";
              echo "<td title='{$row['Customer_Name']}'>{$name}</td>";
              echo "<td title='{$row['Review_Text']}'>{$review}</td>";
              echo "<td>{$row['Review_Date']}</td>";
              echo "</tr>";
            }
            echo "</table>";
          }
        }
      }
    }
    ?>
  </div>
</body>

</html>