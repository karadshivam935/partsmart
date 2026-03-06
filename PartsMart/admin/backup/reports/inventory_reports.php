<?php
require_once '../dbConnect.php';
require_once '../../fpdf/fpdf.php';

// Fetch products for dropdown
$products_query = "SELECT Product_Id, Product_Name FROM product_tbl WHERE Visibility_Mode = 1";
$products_result = $conn->query($products_query);
$products = [];
while ($row = $products_result->fetch_assoc()) {
    $products[] = $row;
}

$report_data = [];
$grouped_report_data = [];
$selected_product_name = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $report_type = $_POST['report_type'] ?? '';
    $selected_product = $_POST['product_id'] ?? '';
    $stock_level = $_POST['stock_level'] ?? '';

    // Fetch data for both preview and download
    if ($report_type === 'product_stock') {
        // Fetch product details and MOQ for Product Stock Report
        $query = "SELECT pd.Pdt_Det_Id, pd.Color, pd.Material, pd.Dimensions, pd.Price, pd.Stock_Qty, pt.MOQ 
                  FROM product_details_tbl pd 
                  JOIN product_tbl pt ON pd.Product_Id = pt.Product_Id 
                  WHERE pd.Product_Id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $selected_product);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $status = '';
            $moq = $row['MOQ'];
            $qty = $row['Stock_Qty'];

            // Determine stock status
            if ($moq > $qty) {
                $status = 'OUT OF STOCK';
            } elseif ($qty > $moq * 5 && $qty <= $moq * 10) {
                $status = 'LOW STOCK';
            } elseif ($qty > $moq * 10) {
                $status = 'IN STOCK';
            }

            $row['Status'] = $status;
            $report_data[] = $row;
        }
        $stmt->close();
    } elseif ($report_type === 'stock_level') {
        // Fetch all products with their stock levels for Stock Level Report
        $query = "SELECT pt.Product_Id, pt.Product_Name, pd.Pdt_Det_Id, pd.Color, pd.Material, pd.Dimensions, pd.Price, pd.Stock_Qty, pt.MOQ 
                  FROM product_details_tbl pd 
                  JOIN product_tbl pt ON pd.Product_Id = pt.Product_Id 
                  WHERE pt.Visibility_Mode = 1";
        $result = $conn->query($query);

        while ($row = $result->fetch_assoc()) {
            $status = '';
            $moq = $row['MOQ'];
            $qty = $row['Stock_Qty'];

            // Determine stock status
            if ($moq > $qty) {
                $status = 'OUT OF STOCK';
            } elseif ($qty > $moq * 5 && $qty <= $moq * 10) {
                $status = 'LOW STOCK';
            } elseif ($qty > $moq * 10) {
                $status = 'IN STOCK';
            }

            $row['Status'] = $status;
            $report_data[] = $row;
        }

        // Filter data based on stock level
        if (!empty($stock_level)) {
            $report_data = array_filter($report_data, function($row) use ($stock_level) {
                return $row['Status'] === $stock_level;
            });
        }

        // Group data by Product_Id for Stock Level Report
        foreach ($report_data as $row) {
            $grouped_report_data[$row['Product_Id']][] = $row;
        }
    }

    // Get selected product name for PDF and preview
    if (!empty($selected_product)) {
        foreach ($products as $product) {
            if ($product['Product_Id'] == $selected_product) {
                $selected_product_name = $product['Product_Name'];
                break;
            }
        }
    }

    // Handle PDF download
    if (isset($_POST['download']) && !empty($report_data)) {
        $pdf = new FPDF();
        $pdf->AddPage();

        // Header
        $pdf->SetFont('Arial', '', 12);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(0, 10, 'PartsMart', 0, 1, 'C');

        // Date
        $pdf->SetFont('Arial', '', 12);
        $pdf->SetTextColor(50, 50, 50);
        $pdf->Cell(0, 10, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 1, 'R');
        $pdf->Ln(5);

        // Report Title
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(0, 10, 'Inventory Report', 0, 1, 'L');

        // Subtitle
        $pdf->SetFont('Arial', 'B', 12);
        if ($report_type === 'product_stock') {
            $pdf->Cell(0, 10, 'Product Stock Report: ' . strtoupper($selected_product_name), 0, 1, 'L');
        } else {
            $pdf->Cell(0, 10, 'Stock Level Report: ' . strtoupper($stock_level), 0, 1, 'L');
        }
        $pdf->Ln(10);

        // Table
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->SetFillColor(200, 220, 255);
        $table_width = 190;
        $page_width = 210;
        $pdf->SetLeftMargin(($page_width - $table_width) / 2);

        if ($report_type === 'product_stock') {
            // Table Header for Product Stock Report
            $pdf->Cell(25, 10, 'VARIANT ID', 1, 0, 'C', true);
            $pdf->Cell(30, 10, 'COLOR', 1, 0, 'C', true);
            $pdf->Cell(30, 10, 'MATERIAL', 1, 0, 'C', true);
            $pdf->Cell(30, 10, 'DIMENSIONS', 1, 0, 'C', true);
            $pdf->Cell(25, 10, 'PRICE', 1, 0, 'C', true);
            $pdf->Cell(20, 10, 'QTY', 1, 0, 'C', true);
            $pdf->Cell(30, 10, 'STATUS', 1, 1, 'C', true);

            // Table Data
            $pdf->SetFont('Arial', '', 10);
            $pdf->SetFillColor(245, 245, 245);
            $fill = false;
            foreach ($report_data as $row) {
                $pdf->Cell(25, 10, $row['Pdt_Det_Id'], 1, 0, 'C', $fill);
                $pdf->Cell(30, 10, $row['Color'], 1, 0, 'C', $fill);
                $pdf->Cell(30, 10, $row['Material'], 1, 0, 'C', $fill);
                $pdf->Cell(30, 10, $row['Dimensions'], 1, 0, 'C', $fill);
                $pdf->Cell(25, 10, 'Rs. ' . number_format($row['Price'], 2), 1, 0, 'C', $fill);
                $pdf->Cell(20, 10, $row['Stock_Qty'], 1, 0, 'C', $fill);
                $pdf->Cell(30, 10, $row['Status'], 1, 1, 'C', $fill);
                $fill = !$fill;
            }
        } else {
            // Stock Level Report - Grouped by Product
            foreach ($grouped_report_data as $product_id => $variants) {
                $product = $variants[0];
                $pdf->SetFont('Arial', 'B', 12);
                $pdf->Cell(0, 10, 'Product ID: ' . $product['Product_Id'] . ' | Product Name: ' . $product['Product_Name'], 0, 1, 'L');
                $pdf->Ln(5);

                // Table Header
                $pdf->SetFont('Arial', 'B', 10);
                $pdf->SetFillColor(200, 220, 255);
                $pdf->Cell(25, 10, 'VARIANT ID', 1, 0, 'C', true);
                $pdf->Cell(25, 10, 'COLOR', 1, 0, 'C', true);
                $pdf->Cell(25, 10, 'MATERIAL', 1, 0, 'C', true);
                $pdf->Cell(25, 10, 'DIMENSIONS', 1, 0, 'C', true);
                $pdf->Cell(25, 10, 'PRICE', 1, 0, 'C', true);
                $pdf->Cell(20, 10, 'QTY', 1, 0, 'C', true);
                $pdf->Cell(45, 10, 'MINIMUM ORDER QTY', 1, 1, 'C', true);

                // Table Data
                $pdf->SetFont('Arial', '', 10);
                $pdf->SetFillColor(245, 245, 245);
                $fill = false;
                foreach ($variants as $row) {
                    $pdf->Cell(25, 10, $row['Pdt_Det_Id'], 1, 0, 'C', $fill);
                    $pdf->Cell(25, 10, $row['Color'], 1, 0, 'C', $fill);
                    $pdf->Cell(25, 10, $row['Material'], 1, 0, 'C', $fill);
                    $pdf->Cell(25, 10, $row['Dimensions'], 1, 0, 'C', $fill);
                    $pdf->Cell(25, 10, 'Rs. ' . number_format($row['Price'], 2), 1, 0, 'C', $fill);
                    $pdf->Cell(20, 10, $row['Stock_Qty'], 1, 0, 'C', $fill);
                    $pdf->Cell(45, 10, $row['MOQ'], 1, 1, 'C', $fill);
                    $fill = !$fill;
                }
                $pdf->Ln(10);
            }
        }

        $pdf->Output('D', 'Inventory_Report_' . ($report_type === 'product_stock' ? $selected_product : $stock_level) . '_' . date('Ymd') . '.pdf');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Reports</title>
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
        select {
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
            margin: 15px 0;
            table-layout: fixed;
            font-size: 12px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
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
            white-space: nowrap; /* Prevent header text from wrapping */
        }
        td {
            background: #fff;
        }
        /* Adjusted column widths for Product Stock Report */
        .inventory-table th:nth-child(1), .inventory-table td:nth-child(1) { /* VARIANT ID */
            width: 10%;
        }
        .inventory-table th:nth-child(2), .inventory-table td:nth-child(2) { /* COLOR */
            width: 15%;
        }
        .inventory-table th:nth-child(3), .inventory-table td:nth-child(3) { /* MATERIAL */
            width: 15%;
        }
        .inventory-table th:nth-child(4), .inventory-table td:nth-child(4) { /* DIMENSIONS */
            width: 15%;
        }
        .inventory-table th:nth-child(5), .inventory-table td:nth-child(5) { /* PRICE */
            width: 15%;
        }
        .inventory-table th:nth-child(6), .inventory-table td:nth-child(6) { /* QTY */
            width: 10%;
        }
        .inventory-table th:nth-child(7), .inventory-table td:nth-child(7) { /* STATUS */
            width: 20%;
        }
        /* Adjusted column widths for Stock Level Report */
        .stock-level-table th:nth-child(1), .stock-level-table td:nth-child(1) { /* VARIANT ID */
            width: 13%;
        }
        .stock-level-table th:nth-child(2), .stock-level-table td:nth-child(2) { /* COLOR */
            width: 13%;
        }
        .stock-level-table th:nth-child(3), .stock-level-table td:nth-child(3) { /* MATERIAL */
            width: 13%;
        }
        .stock-level-table th:nth-child(4), .stock-level-table td:nth-child(4) { /* DIMENSIONS */
            width: 13%;
        }
        .stock-level-table th:nth-child(5), .stock-level-table td:nth-child(5) { /* PRICE */
            width: 13%;
        }
        .stock-level-table th:nth-child(6), .stock-level-table td:nth-child(6) { /* QTY */
            width: 10%;
        }
        .stock-level-table th:nth-child(7), .stock-level-table td:nth-child(7) { /* MINIMUM ORDER QUANTITY */
            width: 25%;
        }
        .product-header {
            font-size: 16px;
            font-weight: bold;
            margin: 15px 0 10px;
            color: #333;
        }
    </style>
    <script>
        function toggleFields() {
            const reportType = document.getElementById('report_type').value;
            const productGroup = document.getElementById('product_group');
            const stockLevelGroup = document.getElementById('stock_level_group');
            const productLabel = document.getElementById('product_label');

            if (reportType === 'product_stock') {
                productGroup.style.display = 'flex';
                stockLevelGroup.style.display = 'none';
                productLabel.textContent = 'SELECT PRODUCT:';
            } else if (reportType === 'stock_level') {
                productGroup.style.display = 'none';
                stockLevelGroup.style.display = 'flex';
                productLabel.textContent = 'STOCK LEVEL:';
            } else {
                productGroup.style.display = 'none';
                stockLevelGroup.style.display = 'none';
            }
        }

        function validateForm() {
            const reportType = document.getElementById('report_type').value;
            if (!reportType) {
                alert('Please select a report type.');
                return false;
            }
            if (reportType === 'product_stock') {
                const productId = document.getElementById('product_id').value;
                if (!productId) {
                    alert('Please select a product.');
                    return false;
                }
            } else if (reportType === 'stock_level') {
                const stockLevel = document.getElementById('stock_level').value;
                if (!stockLevel) {
                    alert('Please select a stock level.');
                    return false;
                }
            }
            return true;
        }
    </script>
</head>
<body>
    <div class="container">
        <h2>INVENTORY REPORTS</h2>
        <form method="POST" onsubmit="return validateForm()">
            <div class="form-group">
                <label for="report_type">REPORT TYPE:</label>
                <select id="report_type" name="report_type" onchange="toggleFields()">
                    <option value="">-- Select Report Type --</option>
                    <option value="product_stock" <?php echo (isset($_POST['report_type']) && $_POST['report_type'] == 'product_stock') ? 'selected' : ''; ?>>PRODUCT STOCK REPORT</option>
                    <option value="stock_level" <?php echo (isset($_POST['report_type']) && $_POST['report_type'] == 'stock_level') ? 'selected' : ''; ?>>STOCK LEVEL REPORT</option>
                </select>
            </div>
            <div class="form-group" id="product_group" style="display: none;">
                <label id="product_label" for="product_id">SELECT PRODUCT:</label>
                <select id="product_id" name="product_id">
                    <option value="">-- Select Product --</option>
                    <?php foreach ($products as $product): ?>
                        <option value="<?php echo $product['Product_Id']; ?>" <?php echo (isset($_POST['product_id']) && $_POST['product_id'] == $product['Product_Id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($product['Product_Name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" id="stock_level_group" style="display: none;">
                <label for="stock_level">STOCK LEVEL:</label>
                <select id="stock_level" name="stock_level">
                    <option value="">-- Select Stock Level --</option>
                    <option value="OUT OF STOCK" <?php echo (isset($_POST['stock_level']) && $_POST['stock_level'] == 'OUT OF STOCK') ? 'selected' : ''; ?>>OUT OF STOCK</option>
                    <option value="IN STOCK" <?php echo (isset($_POST['stock_level']) && $_POST['stock_level'] == 'IN STOCK') ? 'selected' : ''; ?>>IN STOCK</option>
                    <option value="LOW STOCK" <?php echo (isset($_POST['stock_level']) && $_POST['stock_level'] == 'LOW STOCK') ? 'selected' : ''; ?>>LOW STOCK</option>
                </select>
            </div>
            <button type="submit" name="preview">PREVIEW REPORT</button>
            <button type="submit" name="download">DOWNLOAD REPORT</button>
        </form>

        <?php
        if (isset($_POST['preview']) && !empty($report_data)) {
            if ($report_type === 'product_stock') {
                // Add the "STOCK DETAILS OF <SELECTED PRODUCT>" line
                echo "<div class='product-header'>STOCK DETAILS OF " . htmlspecialchars($selected_product_name) . "</div>";
                echo "<table class='inventory-table'>";
                echo "<tr><th>VARIANT ID</th><th>COLOR</th><th>MATERIAL</th><th>DIMENSIONS</th><th>PRICE</th><th>QTY</th><th>STATUS</th></tr>";
                foreach ($report_data as $row) {
                    echo "<tr>";
                    echo "<td>{$row['Pdt_Det_Id']}</td>";
                    echo "<td>{$row['Color']}</td>";
                    echo "<td>{$row['Material']}</td>";
                    echo "<td>{$row['Dimensions']}</td>";
                    echo "<td>" . number_format($row['Price'], 2) . "</td>";
                    echo "<td>{$row['Stock_Qty']}</td>";
                    echo "<td>{$row['Status']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                if (empty($grouped_report_data)) {
                    echo "<p>No data found for the selected stock level.</p>";
                } else {
                    foreach ($grouped_report_data as $product_id => $variants) {
                        $product = $variants[0];
                        echo "<div class='product-header'>Product ID: {$product['Product_Id']} | Product Name: {$product['Product_Name']}</div>";
                        echo "<table class='stock-level-table'>";
                        echo "<tr><th>VARIANT ID</th><th>COLOR</th><th>MATERIAL</th><th>DIMENSIONS</th><th>PRICE</th><th>QTY</th><th>MINIMUM ORDER QUANTITY</th></tr>";
                        foreach ($variants as $row) {
                            echo "<tr>";
                            echo "<td>{$row['Pdt_Det_Id']}</td>";
                            echo "<td>{$row['Color']}</td>";
                            echo "<td>{$row['Material']}</td>";
                            echo "<td>{$row['Dimensions']}</td>";
                            echo "<td>" . number_format($row['Price'], 2) . "</td>";
                            echo "<td>{$row['Stock_Qty']}</td>";
                            echo "<td>{$row['MOQ']}</td>";
                            echo "</tr>";
                        }
                        echo "</table>";
                    }
                }
            }
        }
        ?>
    </div>

    <script>
        // Initialize form fields visibility on page load
        toggleFields();
    </script>
</body>
</html>