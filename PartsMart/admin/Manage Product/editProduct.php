<?php
require_once 'dbConnect.php';

session_start();

if (isset($_GET['pdt_det_id'])) {
    $pdt_det_id = intval($_GET['pdt_det_id']);

    // Query adjusted to match database schema
    $sql = "
        SELECT p.Product_Name, p.Product_Id, p.Visibility_Mode, 
               pd.Pdt_Det_Id, pd.Price, pd.Stock_Qty, 
               pd.Color, pd.Material, pd.Dimensions, 
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
    $stmt->close();

    if (!$product) {
        echo "No product found with the specified ID.";
        exit;
    }
} else {
    echo "Invalid product details ID.";
    exit;
}

// Store existing image path
$existingImage = !empty($product['ImagePath']) ? $product['ImagePath'] : '../assets/products/default_image.jpg';

// Update product details
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];
    $color = $_POST['color'];
    $material = $_POST['material'];
    $dimensions = $_POST['dimensions'];

    // Handle single image upload
    $newImagePath = handleImageUpload('image');
    if ($newImagePath) {
        $existingImage = $newImagePath;
    }

    // Update query adjusted to match database schema
    $updateSql = "
        UPDATE product_details_tbl 
        SET Price = ?, Stock_Qty = ?, 
            Color = ?, Material = ?, Dimensions = ?, 
            ImagePath = ?
        WHERE Pdt_Det_Id = ?
    ";

    $stmt = $conn->prepare($updateSql);
    if ($stmt === false) {
        die("Update prepare failed: " . $conn->error);
    }

    $stmt->bind_param(
        "dissssi",
        $price,
        $quantity,
        $color,
        $material,
        $dimensions,
        $existingImage,
        $pdt_det_id
    );

    if ($stmt->execute()) {
        $_SESSION['success'] = "Changes Saved Successfully";
        echo "<script>
                setTimeout(function(){
                    alert('Changes Saved Successfully');
                    window.location.href = 'productVariants.php?pdt_det_id=$pdt_det_id';
                }, 100);
              </script>";
        exit;
    } else {
        echo "Error updating product details: " . $stmt->error;
    }
    $stmt->close();
}

function handleImageUpload($fieldName)
{
    if (isset($_FILES[$fieldName]) && $_FILES[$fieldName]['error'] === UPLOAD_ERR_OK) {
        $tempName = $_FILES[$fieldName]['tmp_name'];
        $imageName = basename($_FILES[$fieldName]['name']);
        $uploadDir = '../../assets/products/';
        $uploadPath = $uploadDir . $imageName;

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        if (move_uploaded_file($tempName, $uploadPath)) {
            return $uploadPath;
        }
    }
    return null;
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
            display: inline;
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
</head>

<body>
    <div class="form-container">
        <h2>EDIT PRODUCT</h2><br>

        <form method="POST" enctype="multipart/form-data">
            <div class="image-container">
                <div>
                    <img src="<?= htmlspecialchars($existingImage) ?>" alt="Product Image">
                    <p>Product Image</p>
                    <input type="file" name="image" accept="image/*">
                </div>
            </div>

            <h3>Product Details</h3>

            <div class="form-group">
                <label for="variant_id">Variant ID:</label>
                <input type="text" id="variant_id" name="variant_id"
                    value="<?= htmlspecialchars($product['Pdt_Det_Id']) ?>" readonly>
            </div>

            <div class="form-group">
                <label for="product_name">Product Name:</label>
                <input type="text" id="product_name" name="product_name"
                    value="<?= htmlspecialchars($product['Product_Name']) ?>" readonly>
            </div>

            <div class="form-group">
                <label for="price">Price:</label>
                <input type="text" id="price" name="price" value="<?= htmlspecialchars($product['Price']) ?>"
                    pattern="\d+(\.\d{1,2})?" title="Please enter a valid price.">
            </div>

            <div class="form-group">
                <label for="quantity">Quantity:</label>
                <input type="text" id="quantity" name="quantity" value="<?= htmlspecialchars($product['Stock_Qty']) ?>"
                    pattern="\d+" title="Please enter a valid quantity.">
            </div>

            <h3>Description</h3>

            <div class="form-group">
                <label for="color">Color:</label>
                <input type="text" id="color" name="color" value="<?= htmlspecialchars($product['Color']) ?>"
                    pattern="[A-Za-z\s]+" title="Only letters are allowed.">
            </div>

            <div class="form-group">
                <label for="material">Material:</label>
                <input type="text" id="material" name="material" value="<?= htmlspecialchars($product['Material']) ?>"
                    pattern="[A-Za-z\s]+" title="Only letters are allowed.">
            </div>

            <div class="form-group">
                <label for="dimensions">Dimensions:</label>
                <input type="text" id="dimensions" name="dimensions"
                    value="<?= htmlspecialchars($product['Dimensions']) ?>">
            </div>

            <button type="submit" class="modify-btn">Save Changes</button>
        </form>
    </div>

    <script>
        function validateForm() {
            var price = document.getElementById("price").value;
            var quantity = document.getElementById("quantity").value;

            if (isNaN(price) || price <= 0) {
                alert("Please enter a valid price (number only).");
                return false;
            }

            if (isNaN(quantity) || quantity < 0) {
                alert("Please enter a valid quantity (number only).");
                return false;
            }

            return true;
        }
    </script>
</body>

</html>