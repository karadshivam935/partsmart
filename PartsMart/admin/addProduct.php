<?php
require_once 'dbConnect.php'; // Adjusted to reach root-level dbConnect.php
session_start();

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

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

// Get Product Name from product_tbl using the received Product_Id
$product_name_query = "SELECT Product_Name FROM product_tbl WHERE Product_Id = ?";
$stmt = $conn->prepare($product_name_query);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();
$product_name = $product['Product_Name'] ?? 'Unknown Product';
$stmt->close();

// Insert new variant into database upon form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];
    $color = $_POST['color'];
    $material = $_POST['material'];
    $dimensions = $_POST['dimensions'];

    // Default image path if upload fails
    $defaultImage = "../assets/products/default_image.png";
    $imagePath = handleImageUpload('image') ?? $defaultImage;

    // Insert new product variant into database
    $insertSql = "
        INSERT INTO product_details_tbl (Pdt_Det_Id, Product_Id, Price, Stock_Qty, Color, Material, Dimensions, ImagePath)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ";

    $stmt = $conn->prepare($insertSql);
    $stmt->bind_param("iidissss", $new_variant_id, $product_id, $price, $quantity, $color, $material, $dimensions, $imagePath);

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

// Image upload handling with absolute path and error reporting
function handleImageUpload($fieldName)
{
    if (isset($_FILES[$fieldName]) && $_FILES[$fieldName]['error'] === UPLOAD_ERR_OK) {
        $tempName = $_FILES[$fieldName]['tmp_name'];
        $imageName = time() . '_' . basename($_FILES[$fieldName]['name']); // Unique filename

        // Absolute path to assets/products/ from project root
        $uploadDir = dirname(__DIR__, 1) . '/assets/products/';

        // Ensure the directory exists
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true)) {
                echo "Failed to create directory: $uploadDir<br>";
                return null;
            }
        }

        $uploadPath = $uploadDir . $imageName;

        if (move_uploaded_file($tempName, $uploadPath)) {
            // Return relative path as stored in DB
            return "../assets/products/" . $imageName;
        } else {
            echo "Failed to move uploaded file to: $uploadPath<br>";
            echo "Error Code: " . $_FILES[$fieldName]['error'] . "<br>";
            echo "Is directory writable? " . (is_writable($uploadDir) ? "Yes" : "No") . "<br>";
            return null;
        }
    } else {
        if (isset($_FILES[$fieldName])) {
            echo "Upload error: " . $_FILES[$fieldName]['error'] . "<br>";
        } else {
            echo "No file uploaded.<br>";
        }
        return null;
    }
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
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        body {
            background-color: #f4f7fa;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .container {
            width: 100%;
            max-width: 600px;
            background-color: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
        }
        h2 {
            text-align: center;
            color: #333;
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        h3 {
            color: #444;
            font-size: 20px;
            font-weight: 500;
            margin: 20px 0 10px;
        }
        .image-section {
            text-align: center;
            margin-bottom: 20px;
        }
        .image-section img {
            width: 100%;
            max-width: 200px;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
        }
        .form-group {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        label {
            font-weight: 500;
            font-size: 14px;
            color: #444;
            width: 30%;
            display: inline-block;
        }
        input,
        input[type="file"] {
            width: 70%;
            padding: 12px;
            font-size: 14px;
            border-radius: 6px;
            border: 1px solid #ddd;
            background-color: #fafafa;
            transition: border-color 0.3s ease;
        }
        input:focus {
            border-color: #007bff;
            outline: none;
        }
        input[readonly] {
            background-color: #e9ecef;
            cursor: not-allowed;
        }
        .add-btn {
            width: 100%;
            padding: 14px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-top: 20px;
        }
        .add-btn:hover {
            background-color: #0056b3;
        }
        @media (max-width: 768px) {
            .form-group {
                flex-direction: column;
                align-items: flex-start;
            }
            label,
            input,
            input[type="file"] {
                width: 100%;
            }
            label {
                margin-bottom: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Add Product Variant</h2>
        <form name="productForm" method="POST" enctype="multipart/form-data" onsubmit="return validateForm()">
            <div class="image-section">
                <img id="image-preview" src="../assets/products/default_image.png" alt="Default Image">
                <div class="form-group">
                    <label>Upload Image:</label>
                    <input type="file" name="image" id="image-upload" accept="image/*">
                </div>
            </div>

            <h3>Product Details</h3>
            <div class="form-group">
                <label>Variant ID:</label>
                <input type="text" value="<?= $new_variant_id ?>" readonly>
            </div>
            <div class="form-group">
                <label>Product Name:</label>
                <input type="text" value="<?= htmlspecialchars($product_name) ?>" readonly>
            </div>
            <div class="form-group">
                <label>Price:</label>
                <input type="text" name="price" required>
            </div>
            <div class="form-group">
                <label>Quantity:</label>
                <input type="text" name="quantity" required>
            </div>

            <h3>Description</h3>
            <div class="form-group">
                <label>Color:</label>
                <input type="text" name="color" required>
            </div>
            <div class="form-group">
                <label>Material:</label>
                <input type="text" name="material" required>
            </div>
            <div class="form-group">
                <label>Dimensions:</label>
                <input type="text" name="dimensions">
            </div>

            <button type="submit" class="add-btn">Add Variant</button>
        </form>
    </div>

    <script>
        // Image preview functionality
        document.getElementById('image-upload').addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('image-preview').src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });

        // Form validation
        function validateForm() {
            var price = document.forms["productForm"]["price"].value;
            if (isNaN(price) || price <= 0) {
                alert("Please enter a valid price.");
                return false;
            }

            var quantity = document.forms["productForm"]["quantity"].value;
            if (isNaN(quantity) || quantity < 0) {
                alert("Please enter a valid quantity.");
                return false;
            }

            var color = document.forms["productForm"]["color"].value;
            var colorPattern = /^[A-Za-z\s]+$/;
            if (!colorPattern.test(color)) {
                alert("Please enter a valid color (letters only).");
                return false;
            }

            var material = document.forms["productForm"]["material"].value;
            var materialPattern = /^[A-Za-z\s]+$/;
            if (!materialPattern.test(material)) {
                alert("Please enter a valid material (letters only).");
                return false;
            }

            return true;
        }
    </script>
</body>
</html>