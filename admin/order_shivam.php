<?php
require_once 'dbConnect.php';
// Default filter status value
$filterStatus = isset($_GET['status']) ? $_GET['status'] : '';

// Base SQL query to fetch order details including Total_amount, excluding Delivered status
$query = "
    SELECT ot.Order_id, ot.Total_amount, ot.Order_Status, ud.First_Name, ud.Last_Name 
    FROM order_tbl ot 
    JOIN user_detail_tbl ud ON ot.Email_id = ud.Email_id
    WHERE ot.Order_Status != 'Delivered'";

// Add additional filtering condition if status is provided
if ($filterStatus != '') {
    $query .= " AND ot.Order_Status = ?";
}

$stmt = $conn->prepare($query);
if ($filterStatus != '') {
    $stmt->bind_param("s", $filterStatus);
}
$stmt->execute();
$result = $stmt->get_result();

$orders = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
}
$stmt->close();

// Handle form submission to update order status
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['orders'])) {
    foreach ($_POST['orders'] as $orderId => $orderStatus) {
        $updateQuery = "UPDATE order_tbl SET Order_Status = ? WHERE Order_id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param("si", $orderStatus, $orderId);
        $updateStmt->execute();
        $updateStmt->close();
    }
    echo "<script>alert('Changes saved successfully!'); window.location.href='" . $_SERVER['PHP_SELF'] . "';</script>";
    exit();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders</title>
    <style>
    body {
        font-family: Arial, sans-serif;
        padding: 20px;
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
    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }

    th,
    td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: left;
    }

    th {
        background-color: #333;
        color: white;
    }

    .filter-container {
        margin-bottom: 15px;
    }

    .filter-container select {
        padding: 8px;
        border-radius: 5px;
    }

    .filter-container button {
        padding: 8px 12px;
        border: none;
        border-radius: 5px;
        background-color: #007bff;
        color: white;
        cursor: pointer;
    }

    .filter-container button:hover {
        background-color: #0056b3;
    }

    .action-buttons {
        margin-top: 20px;
    }

    .edit-button,
    .save-button {
        width:180px;
        padding: 10px 15px;
        border: none;
        border-radius: 5px;
        color: white;
        cursor: pointer;
    }

    .edit-button {
        background-color: #007bff;
    }

    .edit-button:hover {
        background-color: #0056b3;
    }

    .save-button {
        background-color: #28a745;
        display: none;
    }

    .save-button:hover {
        background-color: #218838;
    }

    td a {
        color: #007bff;
        text-decoration: none;
    }

    td a:hover {
        color: #0056b3;
        text-decoration: none;
    }

    /* Style changes for the 'Change Status' dropdown */
    .status-dropdown {
        border: none;
        background: transparent;
        padding: 5px;
        min-width: 150px;
        display: inline-block;
    }

    .status-dropdown:focus {
        outline: none;
    }

    .status-dropdown option {
        padding: 5px;
    }
    </style>
    <script>
    function enableEditing() {
        document.querySelectorAll("select").forEach(select => select.removeAttribute("disabled"));
        document.getElementById("saveButton").style.display = "inline-block";
        document.getElementById("editButton").style.display = "none";
    }

    function confirmSave() {
        return confirm("Are you sure you want to save changes?");
    }
    </script>
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
    <form method="POST" onsubmit="return confirmSave()">
        <table>
            <tr>
                <th>Order ID</th>
                <th>Total Amount</th>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Change Status</th>
                <th>Order Details</th>
            </tr>
            <?php foreach ($orders as $order): ?>
            <tr>
                <td><?= $order['Order_id'] ?></td>
                <td><?= number_format($order['Total_amount'], 2) ?></td>
                <td><?= htmlspecialchars($order['First_Name']) ?></td>
                <td><?= htmlspecialchars($order['Last_Name']) ?></td>
                <td>
                    <select name="orders[<?= $order['Order_id'] ?>]" class="status-dropdown" disabled>
                        <option value="Processing" <?= $order['Order_Status'] == "Processing" ? 'selected' : '' ?>>
                            Processing</option>
                        <option value="Processed" <?= $order['Order_Status'] == "Processed" ? 'selected' : '' ?>>
                            Processed</option>
                        <option value="Out for Delivery"
                            <?= $order['Order_Status'] == "Out for Delivery" ? 'selected' : '' ?>>Out for Delivery
                        </option>
                    </select>
                </td>
                <td>
                    <a href="order_detail_shivam.php?order_id=<?= $order['Order_id'] ?>">View Details</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <div class="action-buttons">
            <button type="button" class="edit-button" id="editButton" onclick="enableEditing()">✎ EDIT STATUS</button>
            <button type="submit" class="save-button" id="saveButton">SAVE CHANGES</button>
        </div>
    </form>
</body>

</html>