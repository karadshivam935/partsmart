<?php
require_once 'dbConnect.php';

// Fetch product details based on pdt_det_id
if (isset($_GET['pdt_det_id'])) {
    $pdt_det_id = intval($_GET['pdt_det_id']);

    // Query to get product details
    $sql = "
        SELECT p.Product_Name, p.Product_Id, p.Visibility_Mode, 
               pd.Pdt_Det_Id, pd.Price AS Price, pd.Stock_Qty AS Quantity, 
               pd.Color, pd.Dimensions, pd.Material, 
               pd.Img_Path1, pd.Img_Path2, pd.Img_Path3, pd.Img_Path4 
        FROM product_details_tbl pd
        JOIN product_tbl p ON p.Product_Id = pd.Product_Id
        WHERE pd.Pdt_Det_Id = '$pdt_det_id'
    ";

    $result = $conn->query($sql);
    $product = $result->fetch_assoc();
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
        .image-container { display: flex; justify-content: space-around; padding: 20px; }
        .image-container img { width: 200px; height: 200px; object-fit: cover; }
        .product-info { padding: 20px; }
        .product-info p { font-size: 18px; margin-bottom: 10px; }
        .modify-btn { background-color: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
    </style>
</head>
<body>

    <h2>Product Details</h2>

    <!-- Display Product Images -->
    <div class="image-container">
        <?php 
        // Prepare image paths and display up to 4 images or placeholders if fewer are available
        $imagePaths = [
            isset($product['Img_Path1']) ? $product['Img_Path1'] : '',
            isset($product['Img_Path2']) ? $product['Img_Path2'] : '',
            isset($product['Img_Path3']) ? $product['Img_Path3'] : '',
            isset($product['Img_Path4']) ? $product['Img_Path4'] : ''
        ];

        // Display images
        foreach ($imagePaths as $index => $image) {
            $image = !empty($image) ? 'images/' . $image : 'images/default_image_1.jpg';
            $altText = !empty($image) ? "Image " . ($index + 1) : "No Image Available";
        ?>
            <div>
                <img src="<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($altText) ?>">
                <p><?= "Image " . ($index + 1) ?></p>
            </div>
        <?php } ?>
    </div>

    <!-- Modify Images Button -->
    <button class="modify-btn">Modify Pictures</button>

    <div class="product-info">
        <h3>Description</h3>
        <p><strong>Color:</strong> <?= htmlspecialchars($product['Color']) ?></p>
        <p><strong>Dimensions:</strong> <?= htmlspecialchars($product['Dimensions']) ?></p>
        <p><strong>Material:</strong> <?= htmlspecialchars($product['Material']) ?></p>
        <p><strong>Price:</strong> ₹<?= number_format($product['Price'], 2) ?></p>
        <p><strong>Stock Quantity:</strong> <?= $product['Quantity'] ?></p>
        <p><strong>Visibility Mode:</strong> <?= $product['Visibility_Mode'] == 1 ? 'Visible' : 'Hidden' ?></p>
    </div>

</body>
</html>
