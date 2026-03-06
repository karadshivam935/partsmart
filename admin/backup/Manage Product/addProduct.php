<?php
require_once 'dbConnect.php';
session_start();

// Get product_id from URL
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;

if ($product_id === 0) {
    die("Error: No product selected.");
}

// Get the highest existing Pdt_Det_Id and increment it for the new variant
$sql = "SELECT MAX(Pdt_Det_Id) AS max_id FROM product_details_tbl";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$new_variant_id = $row['max_id'] + 1;

// Get Product Name from product_tbl using Product_Id
$product_name_query = "SELECT Product_Name FROM product_tbl WHERE Product_Id = ?";
$stmt = $conn->prepare($product_name_query);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();
$product_name = $product['Product_Name'] ?? 'Unknown Product';
$stmt->close();

// Fetch existing images (if any)
$productDetailsQuery = "
    SELECT Img_Path1, Img_Path2, Img_Path3, Img_Path4 
    FROM product_details_tbl 
    WHERE Product_Id = ?
    LIMIT 1";
$stmt = $conn->prepare($productDetailsQuery);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$productDetails = $result->fetch_assoc();
$stmt->close();

// Default image if no existing image is found
$defaultImage = "images/default_image_1.jpg";
$imgPaths = [
    1 => !empty($productDetails['Img_Path1']) ? $productDetails['Img_Path1'] : $defaultImage,
    2 => !empty($productDetails['Img_Path2']) ? $productDetails['Img_Path2'] : $defaultImage,
    3 => !empty($productDetails['Img_Path3']) ? $productDetails['Img_Path3'] : $defaultImage,
    4 => !empty($productDetails['Img_Path4']) ? $productDetails['Img_Path4'] : $defaultImage,
];

// Insert new variant into database upon form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];
    $color = $_POST['color'];
    $material = $_POST['material'];
    $dimensions = $_POST['dimensions'];

    // Handle image uploads and update only if a new file is uploaded
    for ($i = 1; $i <= 4; $i++) {
        $newImagePath = handleImageUpload('image' . $i);
        if ($newImagePath) {
            $imgPaths[$i] = $newImagePath; // Replace old image if new one is uploaded
        }
    }

    // Insert new product variant into database
    $insertSql = "
        INSERT INTO product_details_tbl (Pdt_Det_Id, Product_Id, Price, Stock_Qty, Color, Material, Dimensions, Img_Path1, Img_Path2, Img_Path3, Img_Path4)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";

    $stmt = $conn->prepare($insertSql);
    $stmt->bind_param("iisisssssss", $new_variant_id, $product_id, $price, $quantity, $color, $material, $dimensions,
        $imgPaths[1], $imgPaths[2], $imgPaths[3], $imgPaths[4]);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Variant added successfully";
        echo "<script>
        setTimeout(function(){
            alert('Variant added successfully');
            window.location.href = 'productVariants.php?product_id=$product_id';
        }, 100);
      </script>";
        exit;
    } else {
        echo "Error adding variant: " . $stmt->error;
    }
    $stmt->close();
}

// Image upload handling
function handleImageUpload($fieldName) {
    if (isset($_FILES[$fieldName]) && $_FILES[$fieldName]['error'] === UPLOAD_ERR_OK) {
        $tempName = $_FILES[$fieldName]['tmp_name'];
        $imageName = basename($_FILES[$fieldName]['name']);
        $uploadDir = 'images/'; // Ensure this directory exists
        $uploadPath = $uploadDir . $imageName;

        if (move_uploaded_file($tempName, $uploadPath)) {
            return $uploadPath; // Return the path if upload is successful
        }
    }
    return null; // Return null if no new image was uploaded
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product Variant</title>
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

    label {
        font-weight: bold;
        font-size: 16px;
        color: #333;
        width: 25%;
        display: inline-block;
        margin-bottom: 10px;
    }

    input {
        width: 70%;
        padding: 12px;
        font-size: 16px;
        border-radius: 12px;
        border: 1px solid #ddd;
        background-color: #f9f9f9;
        margin-bottom: 10px;
    }

    .add-btn {
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

    .add-btn:hover {
        background-color: #0056b3;
    }
    </style>
</head>

<body>
    <h2>ADD PRODUCT VARIANT</h2><br>
    <form method="POST" enctype="multipart/form-data">
        <div class="image-container">
            <?php for ($i = 1; $i <= 4; $i++) { ?>
            <div>
                <img src="<?= htmlspecialchars($imgPaths[$i]) ?>" alt="Product Image">
                <p><?= "Image " . $i ?></p>
                <input type="file" name="image<?= $i ?>" accept="image/*">
            </div>
            <?php } ?>
        </div>
        <h3>Product Details</h3>
        <label>Variant ID:</label>
        <input type="text" value="<?= $new_variant_id ?>" readonly><br>
        <label>Product Name:</label>
        <input type="text" value="<?= htmlspecialchars($product_name) ?>" readonly><br>
        <label>Price:</label>
        <input type="text" name="price" required><br>
        <label>Quantity:</label>
        <input type="text" name="quantity" required><br>
        <h3>Description</h3>
        <label>Color:</label>
        <input type="text" name="color"><br>
        <label>Material:</label>
        <input type="text" name="material"><br>
        <label>Dimensions:</label>
        <input type="text" name="dimensions"><br>
        <button type="submit" class="add-btn">ADD VARIANT</button>
    </form>
</body>

</html>
