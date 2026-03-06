<?php
require_once '../dbConnect.php';
require_once '../../fpdf/fpdf.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $report_type = $_POST['report_type'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    $report_data = [];
    $total_sales_amount = 0;
    $logoPath = 'partmartLogo.png'; // Update with your logo path

    if ($report_type == "total_sales") {
        $query = "SELECT o.Order_Id, CONCAT(u.First_Name, ' ', u.Last_Name) AS Customer_Name, o.Order_Date, o.Order_Status, o.Total_Amount 
                  FROM order_tbl o 
                  JOIN user_detail_tbl u ON o.Email_Id = u.Email_Id
                  WHERE o.Order_Date BETWEEN ? AND ? AND o.Order_Status = 'Delivered'
                  ORDER BY o.Order_Date DESC";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $report_data[] = $row;
            $total_sales_amount += $row['Total_Amount'];
        }
        $stmt->close();
    }

    if ($report_type == "sales_by_category") {
        $query = "SELECT pt.Category_Id, c.Category_Name, COUNT(o.Order_Id) AS Total_Orders, 
                  SUM(od.Qty) AS Total_Quantity_Sold, SUM(od.Sub_Total) AS Total_Income, 
                  AVG(od.Sub_Total) AS Average_Order_Value 
                  FROM order_detail_tbl od
                  JOIN product_details_tbl p ON od.Pdt_Det_Id = p.Pdt_Det_Id
                  JOIN product_tbl pt ON p.Product_Id = pt.Product_Id
                  JOIN category_tbl c ON pt.Category_Id = c.Category_Id
                  JOIN order_tbl o ON od.Order_Id = o.Order_Id
                  WHERE o.Order_Date BETWEEN ? AND ?
                  GROUP BY pt.Category_Id, c.Category_Name";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $report_data[] = $row;
        }
        $stmt->close();
    }

    if (isset($_POST['download']) && !empty($report_data)) {
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
        $pdf->Cell(0, 7, 'Sales Report', 0, 1, 'L');
        $pdf->SetFont('Arial', '', 12);
        
        // Underlined date range
        $title = ($report_type == 'total_sales' ? 'Total Sales' : 'Sales by Category') . ' Report ';
        $pdf->Cell($pdf->GetStringWidth($title), 10, $title, 0, 0, 'L');
        
        $dateRange = ' From ' . $start_date . ' To ' . $end_date;
        $pdf->SetFont('Arial', 'U', 12); // Underlined
        $pdf->Cell($pdf->GetStringWidth($dateRange), 10, $dateRange, 0, 1, 'L');
        $pdf->SetFont('Arial', '', 12); // Reset underline
        
        $pdf->Ln(10);

        // Table
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->SetFillColor(200, 220, 255);
        
        if ($report_type == "total_sales") {
            $table_width = 190;
            $page_width = 210;
            $pdf->SetLeftMargin(($page_width - $table_width) / 2);

            // Table Header
            $pdf->Cell(25, 10, 'Order ID', 1, 0, 'C', true);
            $pdf->Cell(60, 10, 'Customer Name', 1, 0, 'C', true);
            $pdf->Cell(35, 10, 'Order Date', 1, 0, 'C', true);
            $pdf->Cell(30, 10, 'Status', 1, 0, 'C', true);
            $pdf->Cell(40, 10, 'Amount', 1, 1, 'C', true);

            // Table Data
            $pdf->SetFont('Arial', '', 12);
            $pdf->SetFillColor(245, 245, 245);
            $fill = false;
            foreach ($report_data as $row) {
                $pdf->Cell(25, 10, $row['Order_Id'], 1, 0, 'C', $fill);
                $pdf->Cell(60, 10, $row['Customer_Name'], 1, 0, 'C', $fill);
                $pdf->Cell(35, 10, $row['Order_Date'], 1, 0, 'C', $fill);
                $pdf->Cell(30, 10, $row['Order_Status'], 1, 0, 'C', $fill);
                $pdf->Cell(40, 10, 'Rs. ' . number_format($row['Total_Amount'], 2), 1, 1, 'C', $fill);
                $fill = !$fill;
            }

            // Total
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->SetFillColor(200, 220, 255);
            $pdf->Cell(150, 10, 'TOTAL SALES', 1, 0, 'C', true);
            $pdf->Cell(40, 10, 'Rs. ' . number_format($total_sales_amount, 2), 1, 1, 'C', true);
        } else { // Sales by Category
            $table_width = 190;
            $page_width = 210;
            $pdf->SetLeftMargin(($page_width - $table_width) / 2);
        
            // Table Header with multi-line support
            $pdf->SetFont('Arial', 'B', 10); // Reduced font size for better fit
            $headerHeight = 10; // Total height for two-line header
            
            // Define column widths
            $widths = [
                'Category_Id' => 25,
                'Category_Name' => 45,
                'Total_Orders' => 25,
                'Total_Quantity_Sold' => 30,
                'Total_Income' => 35,
                'Average_Order_Value' => 30
            ];
        
            // Multi-line headers
            $pdf->SetFillColor(200, 220, 255);
            $pdf->MultiCell($widths['Category_Id'], 5, "CATEGORY\nID", 1, 'C', true);
            $pdf->SetXY($pdf->GetX() + $widths['Category_Id'], $pdf->GetY() - $headerHeight);
            $pdf->MultiCell($widths['Category_Name'], 5, "CATEGORY\nNAME", 1, 'C', true);
            $pdf->SetXY($pdf->GetX() + $widths['Category_Id'] + $widths['Category_Name'], $pdf->GetY() - $headerHeight);
            $pdf->MultiCell($widths['Total_Orders'], 5, "TOTAL\nORDERS", 1, 'C', true);
            $pdf->SetXY($pdf->GetX() + $widths['Category_Id'] + $widths['Category_Name'] + $widths['Total_Orders'], $pdf->GetY() - $headerHeight);
            $pdf->MultiCell($widths['Total_Quantity_Sold'], 5, "QUANTITY\nSOLD", 1, 'C', true);
            $pdf->SetXY($pdf->GetX() + $widths['Category_Id'] + $widths['Category_Name'] + $widths['Total_Orders'] + $widths['Total_Quantity_Sold'], $pdf->GetY() - $headerHeight);
            $pdf->MultiCell($widths['Total_Income'], 5, "TOTAL\nINCOME", 1, 'C', true);
            $pdf->SetXY($pdf->GetX() + $widths['Category_Id'] + $widths['Category_Name'] + $widths['Total_Orders'] + $widths['Total_Quantity_Sold'] + $widths['Total_Income'], $pdf->GetY() - $headerHeight);
            $pdf->MultiCell($widths['Average_Order_Value'], 5, "AVERAGE\nORDER VALUE", 1, 'C', true);
        
            // Table Data
            $pdf->SetFont('Arial', '', 11); // Slightly smaller font for data
            $pdf->SetFillColor(245, 245, 245);
            $fill = false;
            foreach ($report_data as $row) {
                $pdf->Cell($widths['Category_Id'], 10, $row['Category_Id'], 1, 0, 'C', $fill);
                $pdf->Cell($widths['Category_Name'], 10, $row['Category_Name'], 1, 0, 'C', $fill); // Left-aligned for better readability
                $pdf->Cell($widths['Total_Orders'], 10, $row['Total_Orders'], 1, 0, 'C', $fill);
                $pdf->Cell($widths['Total_Quantity_Sold'], 10, $row['Total_Quantity_Sold'], 1, 0, 'C', $fill);
                $pdf->Cell($widths['Total_Income'], 10, 'Rs. ' . number_format($row['Total_Income'], 2), 1, 0, 'C', $fill); // Right-aligned for currency
                $pdf->Cell($widths['Average_Order_Value'], 10, 'Rs. ' . number_format($row['Average_Order_Value'], 2), 1, 1, 'C', $fill); // Right-aligned for currency
                $fill = !$fill;
            }
        }

        $pdf->Output('D', 'Sales_Report_' . $report_type . '_' . date('Ymd') . '.pdf');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Reports</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: white;
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
        }
        .form-group {
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        label {
            font-weight: bold;
            width: 30%;
        }
        select, input {
            width: 70%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            padding: 10px 20px;
            border: none;
            background: #007bff;
            color: white;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 10px;
        }
        button:hover {
            background: #0056b3;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            margin-left: auto;
            margin-right: auto;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: center;
        }
        th {
            background: #333;
            color: white;
        }
        /* Adjusted column widths for Total Sales Report */
        .total-sales-table {
            margin-left: auto;
            margin-right: auto;
        }
        .total-sales-table th:nth-child(1), .total-sales-table td:nth-child(1) { /* ORDER ID */
            width: 10%;
        }
        .total-sales-table th:nth-child(2), .total-sales-table td:nth-child(2) { /* CUSTOMER NAME */
            width: 30%;
        }
        .total-sales-table th:nth-child(3), .total-sales-table td:nth-child(3) { /* ORDER DATE */
            width: 33%;
        }
        .total-sales-table th:nth-child(4), .total-sales-table td:nth-child(4) { /* STATUS */
            width: 10%;
        }
        .total-sales-table th:nth-child(5), .total-sales-table td:nth-child(5) { /* AMOUNT */
            width: 20%;
        }
        /* Adjusted column widths for Sales by Category Report */
        .sales-by-category-table th:nth-child(1), .sales-by-category-table td:nth-child(1) { /* CATEGORY ID */
            width: 10%;
        }
        .sales-by-category-table th:nth-child(2), .sales-by-category-table td:nth-child(2) { /* CATEGORY NAME */
            width: 18%;
        }
        .sales-by-category-table th:nth-child(3), .sales-by-category-table td:nth-child(3) { /* TOTAL ORDERS */
            width: 11%;
        }
        .sales-by-category-table th:nth-child(4), .sales-by-category-table td:nth-child(4) { /* TOTAL QUANTITY SOLD */
            width: 11%;
        }
        .sales-by-category-table th:nth-child(5), .sales-by-category-table td:nth-child(5) { /* TOTAL INCOME */
            width: 11%;
        }
        .sales-by-category-table th:nth-child(6), .sales-by-category-table td:nth-child(6) { /* AVERAGE ORDER VALUE */
            width: 14%;
        }
        /* Headers in two lines for Sales by Category (Preview only) */
        .sales-by-category-table th {
            height: 40px;
            white-space: normal;
            line-height: 20px;
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
        <h2>SALES REPORTS</h2>
        <form method="POST" onsubmit="return validateForm()">
            <div class="form-group">
                <label for="report_type">REPORT TYPE:</label>
                <select id="report_type" name="report_type">
                    <option value="total_sales" <?php echo (isset($_POST['report_type']) && $_POST['report_type'] == 'total_sales') ? 'selected' : ''; ?>>TOTAL SALES REPORT</option>
                    <option value="sales_by_category" <?php echo (isset($_POST['report_type']) && $_POST['report_type'] == 'sales_by_category') ? 'selected' : ''; ?>>SALES BY CATEGORY</option>
                </select>
            </div>
            <div class="form-group">
                <label for="start_date">START DATE:</label>
                <input type="date" id="start_date" name="start_date" value="<?php echo isset($_POST['start_date']) ? htmlspecialchars($_POST['start_date']) : ''; ?>">
            </div>
            <div class="form-group">
                <label for="end_date">END DATE:</label>
                <input type="date" id="end_date" name="end_date" value="<?php echo isset($_POST['end_date']) ? htmlspecialchars($_POST['end_date']) : ''; ?>">
            </div>
            <div class="button-group">
                <button type="submit" name="preview">PREVIEW REPORT</button>
                <button type="submit" name="download">DOWNLOAD REPORT</button>
            </div>
        </form>

        <?php
        if (isset($_POST['preview'])) {
            $report_type = $_POST['report_type'];
            $start_date = $_POST['start_date'];
            $end_date = $_POST['end_date'];
        
            if ($report_type == "total_sales") {
                $query = "SELECT o.Order_Id, CONCAT(u.First_Name, ' ', u.Last_Name) AS Customer_Name, o.Order_Date, o.Order_Status, o.Total_Amount 
                          FROM order_tbl o 
                          JOIN user_detail_tbl u ON o.Email_Id = u.Email_Id
                          WHERE o.Order_Date BETWEEN ? AND ? AND o.Order_Status = 'Delivered'
                          ORDER BY o.Order_Date DESC";
                
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ss", $start_date, $end_date);
                $stmt->execute();
                $result = $stmt->get_result();
                
                // Calculate total sales amount
                $total_sales_amount = 0;
                $rows = []; // Store rows for display
                while ($row = $result->fetch_assoc()) {
                    $rows[] = $row;
                    $total_sales_amount += $row['Total_Amount'];
                }
                
                // Display the table
                echo "<table class='total-sales-table'>";
                echo "<tr><th>ORDER ID</th><th>CUSTOMER NAME</th><th>ORDER DATE</th><th>STATUS</th><th>AMOUNT</th></tr>";
                foreach ($rows as $row) {
                    echo "<tr><td>{$row['Order_Id']}</td><td>{$row['Customer_Name']}</td><td>{$row['Order_Date']}</td><td>{$row['Order_Status']}</td><td>" . number_format($row['Total_Amount'], 2) . "</td></tr>";
                }
                // Add Total Sales row
                echo "<tr style='font-weight: bold; background-color: #f0f8ff;'>"; // Optional styling
                echo "<td colspan='4'>TOTAL SALES</td>";
                echo "<td>" . number_format($total_sales_amount, 2) . "</td>";
                echo "</tr>";
                echo "</table>";
                
                $stmt->close();
            }
        
            // The "sales_by_category" block remains unchanged
            if ($report_type == "sales_by_category") {
                $query = "SELECT pt.Category_Id, c.Category_Name, COUNT(o.Order_Id) AS Total_Orders, 
                          SUM(od.Qty) AS Total_Quantity_Sold, SUM(od.Sub_Total) AS Total_Income, 
                          AVG(od.Sub_Total) AS Average_Order_Value 
                          FROM order_detail_tbl od
                          JOIN product_details_tbl p ON od.Pdt_Det_Id = p.Pdt_Det_Id
                          JOIN product_tbl pt ON p.Product_Id = pt.Product_Id
                          JOIN category_tbl c ON pt.Category_Id = c.Category_Id
                          JOIN order_tbl o ON od.Order_Id = o.Order_Id
                          WHERE o.Order_Date BETWEEN ? AND ?
                          GROUP BY pt.Category_Id, c.Category_Name";
                
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ss", $start_date, $end_date);
                $stmt->execute();
                $result = $stmt->get_result();
                echo "<table class='sales-by-category-table'>";
                echo "<tr><th>CATEGORY ID</th><th>CATEGORY NAME</th><th>TOTAL ORDERS</th><th>TOTAL QUANTITY SOLD</th><th>TOTAL INCOME</th><th>AVERAGE ORDER VALUE</th></tr>";
                while ($row = $result->fetch_assoc()) {
                    echo "<tr><td>{$row['Category_Id']}</td><td>{$row['Category_Name']}</td><td>{$row['Total_Orders']}</td><td>{$row['Total_Quantity_Sold']}</td><td>" . number_format($row['Total_Income'], 2) . "</td><td>" . number_format($row['Average_Order_Value'], 2) . "</td></tr>";
                }
                echo "</table>";
                $stmt->close();
            }
        }
        ?>
    </div>
</body>
</html>