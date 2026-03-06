<?php
require_once '../config/dbConnect.php';
require('../fpdf/fpdf.php');

// Start session
session_start();
if (!isset($_SESSION['Email_id']) || $_SESSION['user_type'] !== 'Delivery Agent') {
    echo "<p style='color: red;'>Unauthorized Access!</p>";
    exit;
}

$email = $_SESSION['Email_id'];

// Get selected month from the form (default: current month)
$selectedMonth = isset($_POST['month']) ? $_POST['month'] : date('Y-m');

// Count total orders delivered (overall)
$countQuery = "SELECT COUNT(*) AS total_orders FROM order_tbl WHERE Delivered_By = ?";
$stmt = $conn->prepare($countQuery);
$stmt->bind_param("s", $email);
$stmt->execute();
$countResult = $stmt->get_result();
$totalOrders = $countResult->fetch_assoc()['total_orders'];
$stmt->close();

// Count total orders for the selected month
$monthCountQuery = "SELECT COUNT(*) AS month_total FROM order_tbl 
                   WHERE Delivered_By = ? AND DATE_FORMAT(Delivery_Date, '%Y-%m') = ?";
$stmt = $conn->prepare($monthCountQuery);
$stmt->bind_param("ss", $email, $selectedMonth);
$stmt->execute();
$monthCountResult = $stmt->get_result();
$monthTotalOrders = $monthCountResult->fetch_assoc()['month_total'];
$stmt->close();

// Fetch order history filtered by selected month with customer details
$query = "
    SELECT ot.Order_Id, ot.Delivery_Date, 
           ud.First_Name, ud.Last_Name, ot.Delivery_Address, ot.Delivery_Pincode AS Pincode, ud.Phone_Number
    FROM order_tbl ot
    JOIN user_detail_tbl ud ON ot.Email_Id = ud.Email_id
    WHERE ot.Delivered_By = ? AND DATE_FORMAT(ot.Delivery_Date, '%Y-%m') = ?
    ORDER BY ot.Delivery_Date DESC
";
$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $email, $selectedMonth);
$stmt->execute();
$result = $stmt->get_result();
$orderHistory = [];

while ($row = $result->fetch_assoc()) {
    $orderHistory[] = $row;
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PartsMart - Delivered Orders</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 80%;
            margin: auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }
        .order-counts {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 20px;
        }
        .card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            text-align: center;
            width: 200px;
        }
        .card h3 {
            margin: 0;
            font-size: 18px;
            color: #333;
        }
        .card p {
            margin: 10px 0 0;
            font-size: 24px;
            font-weight: bold;
            color: #555;
        }
        .filter-form {
            text-align: center;
            margin-bottom: 20px;
        }
        .order-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .order-card {
            background: #fff;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-left: 5px solid #333;
            text-align: justify;
        }
        .order-card p {
            margin: 5px 0;
            text-align: justify;
        }
        .status {
            font-weight: bold;
            color: green;
        }
        .no-data {
            text-align: center;
            color: red;
            font-size: 18px;
            margin-top: 20px;
        }
        button {
            background-color: #333;
            color: white;
            border: none;
            padding: 10px 15px;
            font-size: 16px;
            cursor: pointer;
            border-radius: 5px;
        }
        button:hover {
            background-color: #555;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Delivered Orders</h1>

        <div class="order-counts">
            <div class="card">
                <h3>Total Orders Delivered</h3>
                <p><?= htmlspecialchars($totalOrders) ?></p>
            </div>
            <div class="card">
                <h3>Orders in <?= htmlspecialchars($selectedMonth) ?></h3>
                <p><?= htmlspecialchars($monthTotalOrders) ?></p>
            </div>
        </div>

        <form method="POST" class="filter-form">
            <label for="month">Select Month:</label>
            <input type="month" id="month" name="month" value="<?= htmlspecialchars($selectedMonth) ?>" required>
            <button type="submit">Filter</button>
        </form>

        <?php if (!empty($orderHistory)): ?>
            <div class="order-list">
                <?php foreach ($orderHistory as $history): ?>
                    <div class="order-card">
                        <p><strong>Order ID:</strong> <?= htmlspecialchars($history['Order_Id']) ?></p>
                        <p><strong>Customer Name:</strong> <?= htmlspecialchars($history['First_Name'] . ' ' . $history['Last_Name']) ?></p>
                        <p><strong>Address:</strong> <?= htmlspecialchars($history['Delivery_Address']) ?></p>
                        <p><strong>Pincode:</strong> <?= htmlspecialchars($history['Pincode']) ?></p>
                        <p><strong>Phone Number:</strong> <?= htmlspecialchars($history['Phone_Number']) ?></p>
                        <p class="status"><strong>Delivery Date:</strong> <?= htmlspecialchars($history['Delivery_Date']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="no-data">No delivered orders found for the selected month.</p>
        <?php endif; ?>
        <form method="POST" action="deliveryagentgenerate_report.php" target="_blank">
            <input type="hidden" name="month" value="<?= htmlspecialchars($selectedMonth) ?>">
            <button type="submit">Download Report</button>
        </form>
    </div>
</body>
</html>