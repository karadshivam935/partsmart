<?php
require_once 'dbConnect.php';

session_start(); // Start session to store success message

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

// Update product details upon form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];
    $color = $_POST['color'];
    $material = $_POST['material'];
    $dimensions = $_POST['dimensions'];

    // Handle image uploads and get paths
    $imgPaths = [];
    for ($i = 1; $i <= 4; $i++) {
        $imgPaths[$i] = handleImageUpload('image' . $i);
    }

    // Update product details in the database with new image paths
    $updateSql = "
        UPDATE product_details_tbl 
        SET Price = '$price', Stock_Qty = '$quantity', 
            Color = '$color', Material = '$material', Dimensions = '$dimensions',
            Img_Path1 = '{$imgPaths[1]}', Img_Path2 = '{$imgPaths[2]}', 
            Img_Path3 = '{$imgPaths[3]}', Img_Path4 = '{$imgPaths[4]}'
        WHERE Pdt_Det_Id = '$pdt_det_id'
    ";

    if ($conn->query($updateSql)) {
        // Store success flag in session to display pop-up
        $_SESSION['success'] = "Changes Saved Successfully";
        echo "<script>
                setTimeout(function(){
                    alert('Changes Saved Successfully');
                    window.location.href = 'productVariants.php?pdt_det_id=$pdt_det_id';
                }, 100);
              </script>";
        exit;
    } else {
        echo "Error updating product details: " . $conn->error;
    }
}

// Image upload handling
function handleImageUpload($fieldName) {
    if (isset($_FILES[$fieldName]) && $_FILES[$fieldName]['error'] === UPLOAD_ERR_OK) {
        $tempName = $_FILES[$fieldName]['tmp_name'];
        $imageName = basename($_FILES[$fieldName]['name']);
        $uploadDir = 'C:/xampp/htdocs/XPartsMart/images/'; // Path for storing images on the server
        $uploadPath = $uploadDir . $imageName;

        if (move_uploaded_file($tempName, $uploadPath)) {
            return $uploadPath; // Return the full path
        }
    }
    return null; // Return null if no image is uploaded or error occurs
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product</title>
    <style>
    body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 20px;
    }

    h2 {
        text-align: left;
        color: #333;
        margin-top: 30px;
    }
    .form-container {
        max-width: 800px;
        margin: auto;
        background-color: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        margin-bottom: 30px;
    }

    .image-container {
        display: flex;
        justify-content: space-between;
        margin-bottom: 30px;
    }

    .image-container div {
        text-align: center;
        width: 22%;
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        padding: 10px;
        transition: transform 0.3s;
    }

    .image-container div:hover {
        transform: scale(1.05);
    }

    .image-container img {
        width: 100%;
        height: 200px;
        object-fit: cover;
        border-radius: 8px;
    }

    .image-container p {
        font-size: 14px;
        margin-top: 10px;
        color: #555;
    }

    .form-group {
        margin-bottom: 30px;
        display: flex;
        flex-direction: row;
        align-items: center;
    }

    label {
        font-weight: bold;
        font-size: 16px;
        color: #333;
        width: 25%;
        margin-right: 10px;
        margin-bottom: 10px;
    }

    input,
    select {
        width: 70%;
        padding: 12px;
        margin-left: 20px;
        font-size: 16px;
        border-radius: 12px;
        display:inline;
        border: 1px solid #ddd;
        background-color: #f9f9f9;
        margin-bottom: 10px;
    }

    .modify-btn {
        width: 30%;
        padding: 12px 30px;
        background-color: #007bff;
        color: white;
        border: none;
        border-radius: 12px;
        font-size: 16px;
        cursor: pointer;
        transition: background-color 0.3s ease;
        display: block;
        margin: 30px auto 0;
    }

    .modify-btn:hover {
        background-color: #0056b3;
    }

    @media (max-width: 768px) {
        .form-container {
            padding: 15px;
        }

        .form-group {
            flex-direction: column;
            align-items: flex-start;
        }

        .form-group label,
        .form-group input,
        .form-group select {
            width: 100%;
        }

        .modify-btn {
            font-size: 18px;
            padding: 14px;
        }

        .image-container {
            flex-direction: column;
            align-items: center;
        }

        .image-container div {
            width: 80%;
            margin-bottom: 15px;
        }
    }
    </style>

    <script>
        // JavaScript Validation for Price and Quantity to ensure only numbers are entered
        function validateForm() {
            var price = document.getElementById("price").value;
            var quantity = document.getElementById("quantity").value;

            // Validate if Price and Quantity are numbers
            if (isNaN(price) || price <= 0) {
                alert("Please enter a valid price (number only).");
                return false;
            }

            if (isNaN(quantity) || quantity < 0) {
                alert("Please enter a valid quantity (number only).");
                return false;
            }

            return true; // Form is valid
        }
    </script>

</head>

<body>

    <h2>EDIT PRODUCT</h2><BR>

    <!-- Display Product Images with EDIT Buttons -->
    <form method="POST" enctype="multipart/form-data" onsubmit="return validateForm()">
        <div class="image-container">
            <?php 
            $imagePaths = [
                isset($product['Img_Path1']) ? $product['Img_Path1'] : '',
                isset($product['Img_Path2']) ? $product['Img_Path2'] : '',
                isset($product['Img_Path3']) ? $product['Img_Path3'] : '',
                isset($product['Img_Path4']) ? $product['Img_Path4'] : ''
            ];

            foreach ($imagePaths as $index => $image) {
                $image = !empty($image) ? 'images/' . $image : 'images/default_image_1.jpg';
                $altText = !empty($image) ? "Image " . ($index + 1) : "No Image Available";
            ?>
            <div>
                <img src="<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($altText) ?>">
                <p><?= "Image " . ($index + 1) ?></p>
                <input type="file" name="image<?= $index + 1 ?>" accept="image/*">
            </div>
            <?php } ?>
        </div>

        <h3>Product Details</h3>

        <!-- Variant ID (non-editable) -->
        <label for="variant_id">Variant ID:</label>
        <input type="text" id="variant_id" name="variant_id" value="<?= $product['Pdt_Det_Id'] ?>" readonly><br>

        <!-- Product Name (non-editable) -->
        <label for="product_name">Product Name:</label>
        <input type="text" id="product_name" name="product_name"
            value="<?= htmlspecialchars($product['Product_Name']) ?>" readonly><br>

        <!-- Price -->
        <label for="price">Price:</label>
        <input type="text" id="price" name="price" value="<?= htmlspecialchars($product['Price']) ?>" pattern="\d+(\.\d{1,2})?" title="Please enter a valid price."><br>

        <!-- Quantity -->
        <label for="quantity">Quantity:</label>
        <input type="text" id="quantity" name="quantity" value="<?= htmlspecialchars($product['Quantity']) ?>" pattern="\d+" title="Please enter a valid quantity."><br>

        <h3>Description</h3>

        <!-- Color -->
        <label for="color">Color:</label>
        <input type="text" id="color" name="color" value="<?= htmlspecialchars($product['Color']) ?>" pattern="[A-Za-z\s]+" title="Only letters are allowed."><br>

        <!-- Material -->
        <label for="material">Material:</label>
        <input type="text" id="material" name="material" value="<?= htmlspecialchars($product['Material']) ?>" pattern="[A-Za-z\s]+" title="Only letters are allowed."><br>

        <!-- Dimensions -->
        <label for="dimensions">Dimensions:</label>
        <input type="text" id="dimensions" name="dimensions"
            value="<?= htmlspecialchars($product['Dimensions']) ?>"><br>

        <button type="submit" class="modify-btn">Save Changes</button>
    </form>

</body>

</html>
