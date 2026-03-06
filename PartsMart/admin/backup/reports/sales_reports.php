<?php
require_once '../dbConnect.php';
require_once '../../fpdf/fpdf.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $report_type = $_POST['report_type'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    $report_data = [];
    $total_sales_amount = 0;

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
        
        // Header
        $pdf->SetFont('Arial', 'B', 18);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(0, 10, 'PartsMart', 0, 1, 'C');
        $pdf->Ln(5);

        // Generated timestamp
        $pdf->SetFont('Arial', '', 12);
        $pdf->SetTextColor(50, 50, 50);
        $pdf->Cell(0, 10, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 1, 'R');
        $pdf->Ln(5);

        // Report Title
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(0, 10, 'Sales Report', 0, 1, 'L');
        $pdf->SetFont('Arial', '', 12);
        $title = ($report_type == 'total_sales' ? 'Total Sales' : 'Sales by Category') . ' Report: ' . $start_date . ' to ' . $end_date;
        $pdf->Cell(0, 10, $title, 0, 1, 'L');
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
        } else {
            $table_width = 190;
            $page_width = 210;
            $pdf->SetLeftMargin(($page_width - $table_width) / 2);

            // Table Header
            $pdf->Cell(25, 10, 'Cat. ID', 1, 0, 'C', true);
            $pdf->Cell(40, 10, 'Category Name', 1, 0, 'C', true);
            $pdf->Cell(25, 10, 'Orders', 1, 0, 'C', true);
            $pdf->Cell(30, 10, 'Qty Sold', 1, 0, 'C', true);
            $pdf->Cell(35, 10, 'Income', 1, 0, 'C', true);
            $pdf->Cell(35, 10, 'Avg Value', 1, 1, 'C', true);

            // Table Data
            $pdf->SetFont('Arial', '', 12);
            $pdf->SetFillColor(245, 245, 245);
            $fill = false;
            foreach ($report_data as $row) {
                $pdf->Cell(25, 10, $row['Category_Id'], 1, 0, 'C', $fill);
                $pdf->Cell(40, 10, $row['Category_Name'], 1, 0, 'C', $fill);
                $pdf->Cell(25, 10, $row['Total_Orders'], 1, 0, 'C', $fill);
                $pdf->Cell(30, 10, $row['Total_Quantity_Sold'], 1, 0, 'C', $fill);
                $pdf->Cell(35, 10, 'Rs. ' . number_format($row['Total_Income'], 2), 1, 0, 'C', $fill);
                $pdf->Cell(35, 10, 'Rs. ' . number_format($row['Average_Order_Value'], 2), 1, 1, 'C', $fill);
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
            <button type="submit" name="preview">PREVIEW REPORT</button>
            <button type="submit" name="download">DOWNLOAD REPORT</button>
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
                echo "<table class='total-sales-table'><tr><th>ORDER ID</th><th>CUSTOMER NAME</th><th>ORDER DATE</th><th>STATUS</th><th>AMOUNT</th></tr>";
                $sr = 1;
                while ($row = $result->fetch_assoc()) {
                    echo "<tr><td>{$row['Order_Id']}</td><td>{$row['Customer_Name']}</td><td>{$row['Order_Date']}</td><td>{$row['Order_Status']}</td><td>" . number_format($row['Total_Amount'], 2) . "</td></tr>";
                    $sr++;
                }
                echo "</table>";
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