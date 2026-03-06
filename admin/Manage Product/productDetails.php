<?php
require_once 'dbConnect.php';

// Fetch product details based on pdt_det_id
if (isset($_GET['pdt_det_id'])) {
    $pdt_det_id = intval($_GET['pdt_det_id']);

    // Query adjusted to match database schema with prepared statement
    $sql = "
        SELECT p.Product_Name, p.Product_Id, p.Visibility_Mode, 
               pd.Pdt_Det_Id, pd.Price, pd.Stock_Qty, 
               pd.Color, pd.Dimensions, pd.Material, 
               pd.ImagePath
        FROM product_details_tbl pd
        JOIN product_tbl p ON p.Product_Id = pd.Product_Id
        WHERE pd.Pdt_Det_Id = ?
    ";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("i", $pdt_det_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();

    if (!$product) {
        echo "No product found with the specified ID.";
        exit;
    }

    $stmt->close();
} else {
    echo "Invalid product details ID.";
    exit;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Details</title>
    <style>
        .image-container {
            display: flex;
            justify-content: center;
            padding: 20px;
        }

        .image-container img {
            width: 200px;
            height: 200px;
            object-fit: cover;
        }

        .image-container div {
            text-align: center;
        }

        .product-info {
            padding: 20px;
        }

        .product-info p {
            font-size: 18px;
            margin-bottom: 10px;
        }

        .modify-btn {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin: 20px;
            display: block;
        }
    </style>
</head>

<body>

    <h2>Product Details</h2>

    <!-- Display Product Image -->
    <div class="image-container">
        <?php
        // Use the single ImagePath from the database
        $imagePath = !empty($product['ImagePath']) ? htmlspecialchars($product['ImagePath']) : '../assets/products/default_image.jpg';
        $altText = !empty($product['ImagePath']) ? "Product Image" : "No Image Available";
        ?>
        <div>
            <img src="<?= $imagePath ?>" alt="<?= $altText ?>">
            <p>Product Image</p>
        </div>
    </div>

    <!-- Modify Images Button -->
    <button class="modify-btn"
        onclick="window.location.href='editProduct.php?pdt_det_id=<?= $product['Pdt_Det_Id'] ?>'">Modify
        Pictures</button>

    <div class="product-info">
        <h3><?= htmlspecialchars($product['Product_Name']) ?></h3>
        <p><strong>Variant ID:</strong> <?= htmlspecialchars($product['Pdt_Det_Id']) ?></p>
        <p><strong>Color:</strong> <?= htmlspecialchars($product['Color']) ?></p>
        <p><strong>Dimensions:</strong> <?= htmlspecialchars($product['Dimensions']) ?></p>
        <p><strong>Material:</strong> <?= htmlspecialchars($product['Material']) ?></p>
        <p><strong>Price:</strong> ₹<?= number_format($product['Price'], 2) ?></p>
        <p><strong>Stock Quantity:</strong> <?= htmlspecialchars($product['Stock_Qty']) ?></p>
        <p><strong>Visibility Mode:</strong> <?= $product['Visibility_Mode'] == 1 ? 'Visible' : 'Hidden' ?></p>
    </div>

</body>

</html>