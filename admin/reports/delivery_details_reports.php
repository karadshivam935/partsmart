<?php
require_once '../dbConnect.php';
require_once '../../fpdf/fpdf.php';

// Fetch all delivery agents from user_detail_tbl where User_Type is 'Delivery Agent'
$agent_query = "SELECT Email_Id, CONCAT(First_Name, ' ', Last_Name) AS Agent_Name 
                FROM user_detail_tbl 
                WHERE User_Type = 'Delivery Agent' 
                ORDER BY First_Name, Last_Name";
$agent_result = $conn->query($agent_query);
$agents = [];
while ($row = $agent_result->fetch_assoc()) {
  $agents[$row['Email_Id']] = $row['Agent_Name'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $start_date = $_POST['start_date'];
  $end_date = $_POST['end_date'];
  $selected_agent = isset($_POST['agent_id']) ? $_POST['agent_id'] : 'all';
  $logoPath = 'partmartLogo.png'; // Update with your actual logo path

  $report_data = [];
  $grouped_data = [];

  $query = "SELECT o.Order_Id, CONCAT(u.First_Name, ' ', u.Last_Name) AS Customer_Name, 
                  o.Total_Amount AS Order_Amount, o.Delivery_Address AS Address, 
                  o.Delivery_Pincode AS Pincode, o.Delivery_Date, o.Delivered_By
            FROM order_tbl o
            JOIN user_detail_tbl u ON o.Email_Id = u.Email_Id
            WHERE o.Delivery_Date BETWEEN ? AND ? AND o.Delivery_Date IS NOT NULL";
  if ($selected_agent !== 'all') {
    $query .= " AND o.Delivered_By = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sss", $start_date, $end_date, $selected_agent);
  } else {
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $start_date, $end_date);
  }
  $stmt->execute();
  $result = $stmt->get_result();
  while ($row = $result->fetch_assoc()) {
    $report_data[] = $row;
    if ($selected_agent === 'all') {
      $agent_id = $row['Delivered_By'];
      $grouped_data[$agent_id][] = $row;
    }
  }
  $stmt->close();

  if (isset($_POST['download']) && !empty($report_data)) {
    // PDF generation
    $pdf = new FPDF();
    $pdf->AddPage();
    
    // Add border around the page
    $pdf->SetDrawColor(0, 0, 0);
    $pdf->Rect(5, 5, $pdf->GetPageWidth() - 10, $pdf->GetPageHeight() - 10);
    
    // Add logo and title
    $logoWidth = 20;
    $logoHeight = 15;
    $text = 'PartsMart';
    $pdf->SetFont('Arial', 'B', 20);
    $textWidth = $pdf->GetStringWidth($text);
    $spacing = 0;
    $totalWidth = $logoWidth + $spacing + $textWidth;
    $startX = ($pdf->GetPageWidth() - $totalWidth) / 2;
    
    if (file_exists($logoPath)) {
        $pdf->Image($logoPath, $startX, 15, $logoWidth, $logoHeight);
    }
    
    $pdf->SetXY($startX + $logoWidth + $spacing, 15);
    $pdf->Cell($textWidth, 15, $text, 0, 1);
    
    // Report generation date in top right
    $pdf->SetY(10);
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->Cell(0, 6, 'Generated On ' . date('Y-m-d H:i:s'), 0, 0, 'R');
    
    // Report details below logo
    $pdf->SetY(40);
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 7, 'Delivery Details Report', 0, 1, 'L');
    $pdf->SetFont('Arial', '', 12);
    
    // Underlined date range
    $title = 'Report Period  ';
    $pdf->Cell($pdf->GetStringWidth($title), 10, $title, 0, 0, 'L');
    
    $dateRange = $start_date . ' to ' . $end_date;
    $pdf->SetFont('Arial', 'U', 12); // Underlined
    $pdf->Cell($pdf->GetStringWidth($dateRange), 10, $dateRange, 0, 1, 'L');
    $pdf->SetFont('Arial', '', 12); // Reset underline
    
    if ($selected_agent !== 'all') {
        $agent_name = $agents[$selected_agent];
        $pdf->Cell(0, 10, "Agent: $agent_name", 0, 1, 'L');
    }
    
    $pdf->Ln(10);

    $table_width = 190;
    $page_width = 210;
    $pdf->SetLeftMargin(($page_width - $table_width) / 2);

    if ($selected_agent === 'all') {
      foreach ($grouped_data as $agent_id => $agent_orders) {
        $agent_name = $agents[$agent_id] ?? 'Unknown Agent';
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 10, "ORDERS DELIVERED BY $agent_name", 0, 1, 'L');
        $pdf->Ln(5);

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetFillColor(200, 220, 255);
        $pdf->Cell(20, 10, 'Order ID', 1, 0, 'C', true);
        $pdf->Cell(40, 10, 'Customer Name', 1, 0, 'C', true);
        $pdf->Cell(30, 10, 'Order Amount', 1, 0, 'C', true);
        $pdf->Cell(50, 10, 'Address', 1, 0, 'C', true);
        $pdf->Cell(20, 10, 'Pincode', 1, 0, 'C', true);
        $pdf->Cell(30, 10, 'Delivery Date', 1, 1, 'C', true);
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetFillColor(245, 245, 245);
        $fill = false;
        foreach ($agent_orders as $row) {
          $name = strlen($row['Customer_Name']) > 25 ? substr($row['Customer_Name'], 0, 22) . '...' : $row['Customer_Name'];
          $pdf->Cell(20, 10, $row['Order_Id'], 1, 0, 'C', $fill);
          $pdf->Cell(40, 10, $name, 1, 0, 'C', $fill);
          $pdf->Cell(30, 10, 'Rs. ' . number_format($row['Order_Amount'], 2), 1, 0, 'C', $fill);
          $pdf->MultiCell(50, 5, $row['Address'], 1, 'L', $fill);
          $pdf->SetXY($pdf->GetX() + 140, $pdf->GetY() - 10);
          $pdf->Cell(20, 10, $row['Pincode'], 1, 0, 'C', $fill);
          $pdf->Cell(30, 10, $row['Delivery_Date'], 1, 1, 'C', $fill);
          $fill = !$fill;
        }
        $pdf->Ln(10); // Space between tables
        if ($pdf->GetY() > 250) {
          $pdf->AddPage();
          $pdf->SetLeftMargin(($page_width - $table_width) / 2);
        }
      }
    } else {
      $agent_name = $agents[$selected_agent];
      $pdf->SetFont('Arial', 'B', 12);
      $pdf->Cell(0, 10, "ORDERS DELIVERED BY $agent_name", 0, 1, 'L');
      $pdf->Ln(5);

      $pdf->SetFont('Arial', 'B', 10);
      $pdf->SetFillColor(200, 220, 255);
      $pdf->Cell(20, 10, 'Order ID', 1, 0, 'C', true);
      $pdf->Cell(40, 10, 'Customer Name', 1, 0, 'C', true);
      $pdf->Cell(30, 10, 'Order Amount', 1, 0, 'C', true);
      $pdf->Cell(50, 10, 'Address', 1, 0, 'C', true);
      $pdf->Cell(20, 10, 'Pincode', 1, 0, 'C', true);
      $pdf->Cell(30, 10, 'Delivery Date', 1, 1, 'C', true);
      $pdf->SetFont('Arial', '', 9);
      $pdf->SetFillColor(245, 245, 245);
      $fill = false;
      foreach ($report_data as $row) {
        $name = strlen($row['Customer_Name']) > 25 ? substr($row['Customer_Name'], 0, 22) . '...' : $row['Customer_Name'];
        $pdf->Cell(20, 10, $row['Order_Id'], 1, 0, 'C', $fill);
        $pdf->Cell(40, 10, $name, 1, 0, 'C', $fill);
        $pdf->Cell(30, 10, 'Rs. ' . number_format($row['Order_Amount'], 2), 1, 0, 'C', $fill);
        $pdf->MultiCell(50, 5, $row['Address'], 1, 'L', $fill);
        $pdf->SetXY($pdf->GetX() + 140, $pdf->GetY() - 10);
        $pdf->Cell(20, 10, $row['Pincode'], 1, 0, 'C', $fill);
        $pdf->Cell(30, 10, $row['Delivery_Date'], 1, 1, 'C', $fill);
        $fill = !$fill;
      }
    }
    $pdf->Output('D', 'Delivery_Details_Report_' . date('Ymd') . '.pdf');
    exit();
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Delivery Details Reports</title>
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
      margin-right: 10px;
    }

    button:hover {
      background: #0056b3;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin: 15px 0 30px 0;
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

    .delivery-table th:nth-child(1),
    .delivery-table td:nth-child(1) {
      width: 10%;
    }

    .delivery-table th:nth-child(2),
    .delivery-table td:nth-child(2) {
      width: 20%;
    }

    .delivery-table th:nth-child(3),
    .delivery-table td:nth-child(3) {
      width: 15%;
    }

    .delivery-table th:nth-child(4),
    .delivery-table td:nth-child(4) {
      width: 30%;
    }

    .delivery-table th:nth-child(5),
    .delivery-table td:nth-child(5) {
      width: 10%;
    }

    .delivery-table th:nth-child(6),
    .delivery-table td:nth-child(6) {
      width: 15%;
      white-space: nowrap;
    }

    .agent-header {
      font-size: 16px;
      font-weight: bold;
      margin: 15px 0 10px;
      color: #333;
    }

    .button-group {
      display: flex;
      justify-content: center;
      margin-top: 20px;
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
  </script>
</head>

<body>
  <div class="container">
    <h2>DELIVERY DETAILS REPORTS</h2>
    <form method="POST" onsubmit="return validateForm()">
      <div class="form-group">
        <label for="agent_id">SELECT DELIVERY AGENT:</label>
        <select id="agent_id" name="agent_id">
          <option value="all" <?php echo (isset($_POST['agent_id']) && $_POST['agent_id'] == 'all') ? 'selected' : ''; ?>>ALL AGENTS</option>
          <?php foreach ($agents as $email => $name): ?>
            <option value="<?php echo $email; ?>" <?php echo (isset($_POST['agent_id']) && $_POST['agent_id'] == $email) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($name); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label for="start_date">START DATE:</label>
        <input type="date" id="start_date" name="start_date"
          value="<?php echo isset($_POST['start_date']) ? htmlspecialchars($_POST['start_date']) : ''; ?>">
      </div>
      <div class="form-group">
        <label for="end_date">END DATE:</label>
        <input type="date" id="end_date" name="end_date"
          value="<?php echo isset($_POST['end_date']) ? htmlspecialchars($_POST['end_date']) : ''; ?>">
      </div>
      <div class="button-group">
        <button type="submit" name="preview">PREVIEW REPORT</button>
        <button type="submit" name="download">DOWNLOAD REPORT</button>
      </div>
    </form>

    <?php
    if (isset($_POST['preview'])) {
      $start_date = $_POST['start_date'];
      $end_date = $_POST['end_date'];
      $selected_agent = isset($_POST['agent_id']) ? $_POST['agent_id'] : 'all';

      if (empty($report_data)) {
        echo "<p>No delivery details found for the selected criteria.</p>";
      } else {
        if ($selected_agent === 'all') {
          foreach ($grouped_data as $agent_id => $agent_orders) {
            $agent_name = $agents[$agent_id] ?? 'Unknown Agent';
            echo "<div class='agent-header'>ORDERS DELIVERED BY $agent_name</div>";
            echo "<table class='delivery-table'>";
            echo "<tr><th>ORDER ID</th><th>CUSTOMER NAME</th><th>ORDER AMOUNT</th><th>ADDRESS</th><th>PINCODE</th><th>DELIVERY DATE</th></tr>";
            foreach ($agent_orders as $row) {
              $name = strlen($row['Customer_Name']) > 25 ? substr($row['Customer_Name'], 0, 22) . '...' : $row['Customer_Name'];
              echo "<tr>";
              echo "<td>{$row['Order_Id']}</td>";
              echo "<td title='{$row['Customer_Name']}'>{$name}</td>";
              echo "<td>Rs. " . number_format($row['Order_Amount'], 2) . "</td>";
              echo "<td style='white-space: pre-wrap;'>{$row['Address']}</td>";
              echo "<td>{$row['Pincode']}</td>";
              echo "<td>{$row['Delivery_Date']}</td>";
              echo "</tr>";
            }
            echo "</table>";
          }
        } else {
          $agent_name = $agents[$selected_agent];
          echo "<div class='agent-header'>ORDERS DELIVERED BY $agent_name</div>";
          echo "<table class='delivery-table'>";
          echo "<tr><th>ORDER ID</th><th>CUSTOMER NAME</th><th>ORDER AMOUNT</th><th>ADDRESS</th><th>PINCODE</th><th>DELIVERY DATE</th></tr>";
          foreach ($report_data as $row) {
            $name = strlen($row['Customer_Name']) > 25 ? substr($row['Customer_Name'], 0, 22) . '...' : $row['Customer_Name'];
            echo "<tr>";
            echo "<td>{$row['Order_Id']}</td>";
            echo "<td title='{$row['Customer_Name']}'>{$name}</td>";
            echo "<td>Rs. " . number_format($row['Order_Amount'], 2) . "</td>";
            echo "<td style='white-space: pre-wrap;'>{$row['Address']}</td>";
            echo "<td>{$row['Pincode']}</td>";
            echo "<td>{$row['Delivery_Date']}</td>";
            echo "</tr>";
          }
          echo "</table>";
        }
      }
    }
    ?>
  </div>
</body>
</html>