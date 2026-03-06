<?php
require_once 'dbConnect.php';

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

$query = "
   SELECT 
        od.Pdt_Det_id, 
        p.Product_name, 
        c.category_name,  -- Added category_name
        od.Qty, 
        od.Unit_price, 
        od.Sub_total
    FROM order_detail_tbl od
    INNER JOIN product_details_tbl pd ON od.Pdt_Det_id = pd.Pdt_Det_id
    INNER JOIN product_tbl p ON pd.Product_id = p.Product_id
    INNER JOIN category_tbl c ON p.Category_id = c.Category_id  -- Join with category_tbl
    WHERE od.Order_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

$orderDetails = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $orderDetails[] = $row;
    }
}
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #333; color: white; }
        .back-link { display: inline-block; margin-bottom: 10px; color: #007bff; text-decoration: none; }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <a class="back-link" href="order_shivam.php">← Back to Orders</a>
    <h2>Order Details</h2>

    <table>
        <tr>
            <th>Variant Id</th>
            <th>Product Name</th>
            <th>Category Name</th> <!-- New category_name column -->
            <th>Quantity</th>
            <th>Unit Price </th>
            <th>Sub Total </th>
        </tr>
        <?php if (!empty($orderDetails)): ?>
            <?php foreach ($orderDetails as $detail): ?>
            <tr>
                <td><?= $detail['Pdt_Det_id'] ?></td>
                <td><?= htmlspecialchars($detail['Product_name']) ?></td>
                <td><?= htmlspecialchars($detail['category_name']) ?></td> <!-- Display category_name -->
                <td><?= $detail['Qty'] ?></td>
                <td><?= number_format($detail['Unit_price'], 2) ?></td>
                <td><?= number_format($detail['Sub_total'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="6">No details found for this order.</td></tr>
        <?php endif; ?>
    </table>
</body>
</html>
