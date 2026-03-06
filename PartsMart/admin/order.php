<?php
require_once 'dbConnect.php';
// Default filter status value
$filterStatus = isset($_GET['status']) ? $_GET['status'] : '';

// SQL query to fetch order details based on filter
$query = "
   SELECT
    od.Order_id,
    od.Pdt_Det_id,
    od.Qty,
    od.Unit_Price,
    od.Sub_Total,
    ot.Order_Status,
    ud.First_Name,
    ud.Last_Name
FROM
    order_detail_tbl od
JOIN
    order_tbl ot ON od.Order_id = ot.Order_id
JOIN
    user_detail_tbl ud ON ot.Email_id = ud.Email_id
";

// Add filtering condition if status is provided
if ($filterStatus != '') {
    $query .= " WHERE ot.Order_Status = ?";
}

$stmt = $conn->prepare($query);

// If a filter is applied, bind the parameter to the query
if ($filterStatus != '') {
    $stmt->bind_param("s", $filterStatus);
}

// Execute the query
$stmt->execute();
$result = $stmt->get_result();

// Check if any records were returned
$orders = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
} else {
    echo "No records found.";
}

// Close the statement
$stmt->close();

// Handle form submission to update order status
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['orders'])) {
    foreach ($_POST['orders'] as $orderId => $orderStatus) {
        // Update the order status in the database
        $updateQuery = "UPDATE order_tbl SET Order_Status = ? WHERE Order_id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param("si", $orderStatus, $orderId);
        $updateStmt->execute();
        $updateStmt->close();
    }

    // Redirect to avoid resubmission on page refresh
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Close the connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #333; color: white; }
        .filter-container { margin-bottom: 15px; }
        .filter-container select { padding: 8px; border-radius: 5px; }
        .filter-container button { padding: 8px 12px; border: none; border-radius: 5px; background-color: #007bff; color: white; cursor: pointer; }
        .filter-container button:hover { background-color: #0056b3; }
        .save-button { margin-top: 20px; padding: 8px 12px; border: none; border-radius: 5px; background-color: #28a745; color: white; cursor: pointer; }
        .save-button:hover { background-color: #218838; }
    </style>
</head>
<body>

    <h2>Order List</h2>
    
    <div class="filter-container">
        <form method="GET">
            <label for="status">Filter by Status:</label>
            <select id="status" name="status">
                <option value="">All</option>
                <option value="Processing" <?= $filterStatus == "Processing" ? 'selected' : '' ?>>Processing</option>
                <option value="Processed" <?= $filterStatus == "Processed" ? 'selected' : '' ?>>Processed</option>
                <option value="Out for Delivery" <?= $filterStatus == "Out for Delivery" ? 'selected' : '' ?>>Out for Delivery</option>
            </select>
            <button type="submit">Filter</button>
        </form>
    </div>

    <form method="POST">
        <table>
            <tr>
                <th>Order ID</th>
                <th>Product Detail ID</th>
                <th>Quantity</th>
                <th>Unit Price ($)</th>
                <th>Sub Total ($)</th>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Change Status</th>
            </tr>
            
            <?php foreach ($orders as $order): ?>
            <tr>
                <td><?= $order['Order_id'] ?></td>
                <td><?= $order['Pdt_Det_id'] ?></td>
                <td><?= $order['Qty'] ?></td>
                <td>$<?= number_format($order['Unit_Price'], 2) ?></td>
                <td>$<?= number_format($order['Sub_Total'], 2) ?></td>
                <td><?= htmlspecialchars($order['First_Name']) ?></td>
                <td><?= htmlspecialchars($order['Last_Name']) ?></td>
                <td>
                    <select name="orders[<?= $order['Order_id'] ?>]">
                        <option value="Processing" <?= $order['Order_Status'] == "Processing" ? 'selected' : '' ?>>Processing</option>
                        <option value="Processed" <?= $order['Order_Status'] == "Processed" ? 'selected' : '' ?>>Processed</option>
                        <option value="Out for Delivery" <?= $order['Order_Status'] == "Out for Delivery" ? 'selected' : '' ?>>Out for Delivery</option>
                    </select>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        
        <button type="submit" class="save-button">Save Changes</button>
    </form>

</body>
</html>
