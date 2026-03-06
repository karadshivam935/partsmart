<?php
session_start();
require_once 'dbConnect.php';

// Check if product_id is provided
if (!isset($_GET['product_id']) || empty($_GET['product_id'])) {
    // $_SESSION['error'] = "No product selected.";
    header("Location: products.php");
    exit;
}

$product_id = intval($_GET['product_id']);

// Fetch product name
$productQuery = "SELECT Product_Name FROM product_tbl WHERE Product_Id = ?";
$stmt = $conn->prepare($productQuery);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $product_id);
$stmt->execute();
$productResult = $stmt->get_result();
$product = $productResult->fetch_assoc();
$stmt->close();

if (!$product) {
    // $_SESSION['error'] = "Product not found.";
    header("Location: products.php");
    exit;
}
$productName = $product['Product_Name'];

// Fetch product variants based on Product_Id
$variantsQuery = "
    SELECT Pdt_Det_Id, Product_Id, Price, Stock_Qty
    FROM product_details_tbl 
    WHERE Product_Id = ?
    ORDER BY Pdt_Det_Id ASC";
$stmt = $conn->prepare($variantsQuery);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $product_id);
$stmt->execute();
$variantsResult = $stmt->get_result();
$variants = [];
while ($row = $variantsResult->fetch_assoc()) {
    $variants[] = $row;
}
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Variants of <?= htmlspecialchars($productName) ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
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

        .button-container {
            margin-top: 20px;
            display: flex;
            justify-content: flex-start;
        }

        .add-btn {
            padding: 10px 15px;
            background-color: #28a745;
            color: white;
            border-radius: 5px;
            text-decoration: none;
        }

        .add-btn:hover {
            background-color: #218838;
        }

        td a {
            color: #007bff;
            text-decoration: none;
        }

        td a:hover {
            color: #0056b3;
            text-decoration: underline;
        }

        .error {
            color: red;
            margin-bottom: 15px;
        }
    </style>
</head>

<body>

    <h2>Variants of <?= htmlspecialchars($productName) ?></h2>

    <?php if (isset($_SESSION['error'])): ?>
        <p class="error"><?= htmlspecialchars($_SESSION['error']) ?></p>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if (empty($variants)): ?>
        <p>No variants available for this product.</p>
    <?php else: ?>
        <table>
            <tr>
                <th>VARIANT ID</th>
                <th>PRODUCT ID</th>
                <th>PRICE</th>
                <th>QUANTITY</th>
                <th>EDIT</th>
            </tr>
            <?php foreach ($variants as $variant): ?>
                <tr>
                    <td><?= htmlspecialchars($variant['Pdt_Det_Id']) ?></td>
                    <td><?= htmlspecialchars($variant['Product_Id']) ?></td>
                    <td>₹<?= number_format($variant['Price'], 2) ?></td>
                    <td><?= htmlspecialchars($variant['Stock_Qty']) ?></td>
                    <td>
                        <a href="editProduct.php?pdt_det_id=<?= $variant['Pdt_Det_Id'] ?>">EDIT VARIANT</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>

    <div class="button-container">
        <a href="addProduct.php?product_id=<?= $product_id ?>" class="add-btn">ADD VARIANT</a>
    </div>

</body>

</html>