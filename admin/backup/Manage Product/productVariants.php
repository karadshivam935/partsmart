<?php
session_start();
require_once 'dbConnect.php';

// Fetch the product id from the query string
if (!isset($_GET['product_id']) || empty($_GET['product_id'])) {
    $_SESSION['error'] = "No product selected.";
    header("Location: products.php");
    exit;
}

$product_id = intval($_GET['product_id']);

// Fetch product name
$productQuery = "SELECT Product_Name FROM product_tbl WHERE Product_Id = ?";
$stmt = $conn->prepare($productQuery);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$stmt->bind_result($productName);
$stmt->fetch();
$stmt->close();

if (empty($productName)) {
    $_SESSION['error'] = "Product not found.";
    header("Location: products.php");
    exit;
}

// Fetch product variants based on Product_Id
$variantsQuery = "
    SELECT Pdt_Det_Id, Product_Id, Price, Stock_Qty
    FROM product_details_tbl 
    WHERE Product_Id = ?
";
$stmt = $conn->prepare($variantsQuery);
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
        justify-content: space-between;
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

    .edit:visited {
        color: #007bff;
    }

    td a {
        color: #007bff;
        text-decoration: none;
    }

    td a:hover {
        color: #0056b3;
        text-decoration: none;
    }
    </style>
</head>

<body>

    <h2>Variants of <?= htmlspecialchars($productName) ?> <span style="font-size: 24px;"></span></h2>

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
            <td><?= $variant['Pdt_Det_Id'] ?></td>
            <td><?= $variant['Product_Id'] ?></td>
            <td><?= htmlspecialchars($variant['Price']) ?></td>
            <td><?= htmlspecialchars($variant['Stock_Qty']) ?></td>
            <td>
                <a href="editProducts.php?pdt_det_id=<?= $variant['Pdt_Det_Id'] ?>" class="edit">EDIT VARIANT</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>

    <div class="button-container">
        <a href="addProduct.php?product_id=<?= $product_id ?>" class="add-btn">ADD VARIANT</a>
    </div>
</body>

</html>
