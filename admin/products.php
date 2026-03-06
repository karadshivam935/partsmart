<?php
session_start();
require_once 'dbConnect.php';

// Fetch all categories (active and inactive) for display purposes
$categoryQuery = "SELECT Category_Id, Category_Name, Visibility_Mode FROM category_tbl";
$stmt = $conn->prepare($categoryQuery);
$stmt->execute();
$categoryResult = $stmt->get_result();
$categories = [];
while ($row = $categoryResult->fetch_assoc()) {
    $categories[$row['Category_Id']] = [
        'name' => $row['Category_Name'],
        'visibility' => $row['Visibility_Mode']
    ];
}
$stmt->close();

// Fetch only active categories for the dropdown
$activeCategoryQuery = "SELECT Category_Id, Category_Name FROM category_tbl WHERE Visibility_Mode = 1";
$stmt = $conn->prepare($activeCategoryQuery);
$stmt->execute();
$activeCategoryResult = $stmt->get_result();
$activeCategories = [];
while ($row = $activeCategoryResult->fetch_assoc()) {
    $activeCategories[$row['Category_Id']] = $row['Category_Name'];
}
$stmt->close();

// Fetch the latest Product_Id
$lastProductQuery = "SELECT Product_Id FROM product_tbl ORDER BY Product_Id DESC LIMIT 1";
$stmt = $conn->prepare($lastProductQuery);
$stmt->execute();
$lastProductResult = $stmt->get_result();
$lastProductRow = $lastProductResult->fetch_assoc();
$lastProductId = $lastProductRow ? intval($lastProductRow['Product_Id']) : 0;
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    foreach ($_POST['product_id'] as $index => $product_id) {
        $product_name = trim($_POST['product_name'][$index]);
        $category_id = isset($_POST['category'][$index]) ? intval($_POST['category'][$index]) : null;
        $status = intval($_POST['status'][$index]);
        $moq = intval($_POST['moq'][$index]);

        // Check if the category is inactive and prevent status change to Active unless reassigned
        $isCategoryInactive = isset($categories[$category_id]) && $categories[$category_id]['visibility'] == 0;
        if ($isCategoryInactive && $status == 1) {
            continue; // Skip status update to Active if category is inactive
        }

        if ($product_id === "new") {
            $newProductId = $lastProductId + 1;
            $lastProductId = $newProductId;

            $insertQuery = "INSERT INTO product_tbl (Product_Id, Product_Name, Category_Id, Visibility_Mode, MOQ) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insertQuery);
            $stmt->bind_param("isiii", $newProductId, $product_name, $category_id, $status, $moq);
            $stmt->execute();
            $stmt->close();
        } else {
            $updateQuery = "UPDATE product_tbl SET Product_Name = ?, Category_Id = ?, Visibility_Mode = ?, MOQ = ? WHERE Product_Id = ?";
            $stmt = $conn->prepare($updateQuery);
            $stmt->bind_param("siiii", $product_name, $category_id, $status, $moq, $product_id);
            $stmt->execute();
            $stmt->close();
        }
    }

    $_SESSION['message'] = "Changes Saved Successfully. Note: Products with inactive categories cannot be set to Active.";
    echo "<script>window.location.href = 'products.php';</script>";
    exit;
}

// Fetch product details
$sql = "
    SELECT 
        p.Product_Id, 
        p.Product_Name, 
        p.Category_Id,
        p.Visibility_Mode AS status,
        p.MOQ,
        (SELECT pd.Pdt_Det_Id FROM product_details_tbl pd WHERE pd.Product_Id = p.Product_Id LIMIT 1) AS Pdt_Det_Id
    FROM product_tbl p
    ORDER BY p.Product_Id ASC";

$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();
$products = [];
$sr_no = 1;
while ($row = $result->fetch_assoc()) {
    $row['sr_no'] = $sr_no++;
    $row['category_name'] = isset($categories[$row['Category_Id']]) 
        ? $categories[$row['Category_Id']]['name'] . ($categories[$row['Category_Id']]['visibility'] == 0 ? ' (Inactive)' : '') 
        : 'Uncategorized';
    $products[] = $row;
}
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products</title>
    <style>
        body, html {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            overflow-x: hidden;
            font-family: Poppins, sans-serif;
        }
        body::-webkit-scrollbar { display: none; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #333; color: white; }
        .button-container { display: flex; justify-content: space-between; padding-top: 10px; }
        .edit-btn, .save-btn, .add-btn {
            width: 180px; padding: 10px 15px; border: none; border-radius: 5px; color: white; cursor: pointer;
        }
        .edit-btn { background-color: #007bff; }
        .edit-btn:hover { background-color: #0056b3; }
        .save-btn { background-color: #28a745; display: none; }
        .save-btn:hover { background-color: #218838; }
        .add-btn { background-color: #17a2b8; }
        .add-btn:hover { background-color: #138496; }
        select, input { padding: 5px; border-radius: 5px; }
        .editable-dropdown, .editable-input {
            border: none; background: transparent; padding: 5px; min-width: 150px; display: inline-block;
        }
        .editable-dropdown:focus, .editable-input:focus { outline: none; }
        a { color: #007bff; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .inactive-category { color: #888; }
    </style>
</head>

<body>
    <h2>Product List</h2>
    <?php if (isset($_SESSION['message'])): ?>
        <p style='color: green;'><?= htmlspecialchars($_SESSION['message']) ?></p>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <form id="product-form" method="POST">
        <table id="product-table">
            <tr>
                <th>SR. NO.</th>
                <th>Name</th>
                <th>Category</th>
                <th>Status</th>
                <th>MOQ</th>
                <th>Variants</th>
            </tr>
            <?php foreach ($products as $index => $product): ?>
                <tr>
                    <td><?= $product['sr_no'] ?></td>
                    <td><input type="text" name="product_name[]" class="editable-input"
                            value="<?= htmlspecialchars($product['Product_Name']) ?>" disabled></td>
                    <td>
                        <select name="category[]" class="editable-dropdown" disabled>
                            <?php foreach ($activeCategories as $id => $name): ?>
                                <option value="<?= $id ?>" <?= $product['Category_Id'] == $id ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($name) ?>
                                </option>
                            <?php endforeach; ?>
                            <?php if ($product['Category_Id'] === null || !isset($categories[$product['Category_Id']])): ?>
                                <option value="" selected>Uncategorized</option>
                            <?php elseif (!isset($activeCategories[$product['Category_Id']])): ?>
                                <option value="<?= $product['Category_Id'] ?>" selected class="inactive-category">
                                    <?= htmlspecialchars($product['category_name']) ?>
                                </option>
                            <?php endif; ?>
                        </select>
                    </td>
                    <td>
                        <?php $isCategoryInactive = isset($categories[$product['Category_Id']]) && $categories[$product['Category_Id']]['visibility'] == 0; ?>
                        <select name="status[]" class="editable-dropdown" disabled <?= $isCategoryInactive ? 'data-inactive-category="true"' : '' ?>>
                            <option value="1" <?= $product['status'] == 1 && !$isCategoryInactive ? 'selected' : '' ?>>Active</option>
                            <option value="0" <?= $product['status'] == 0 || $isCategoryInactive ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </td>
                    <td><input type="number" name="moq[]" class="editable-input" value="<?= $product['MOQ'] ?>" disabled min="1"></td>
                    <td>
                        <?php if (!empty($product['Pdt_Det_Id'])): ?>
                            <a href="productVariants.php?product_id=<?= $product['Product_Id'] ?>">SEE VARIANTS</a>
                        <?php else: ?>
                            <a href="addProduct.php?product_id=<?= $product['Product_Id'] ?>">ADD VARIANTS</a>
                        <?php endif; ?>
                    </td>
                    <input type="hidden" name="product_id[]" value="<?= $product['Product_Id'] ?>">
                </tr>
            <?php endforeach; ?>
        </table>

        <div class="button-container">
            <button type="button" class="edit-btn" onclick="enableEditing()">✎ EDIT PRODUCTS</button>
            <button type="submit" class="save-btn" id="saveButton">SAVE CHANGES</button>
            <button type="button" class="add-btn" onclick="addProduct()">+ ADD PRODUCT</button>
        </div>
    </form>

    <script>
        function enableEditing() {
            document.querySelectorAll(".editable-dropdown, .editable-input").forEach(field => {
                if (field.name === "status[]" && field.dataset.inactiveCategory === "true") {
                    field.disabled = true; // Keep status disabled for products with inactive categories
                } else {
                    field.removeAttribute("disabled");
                }
            });
            document.getElementById("saveButton").style.display = "inline-block";
        }

        function addProduct() {
            let table = document.getElementById("product-table");
            let rowCount = table.rows.length;
            let row = table.insertRow(rowCount);
            row.innerHTML = `
            <td>${rowCount}</td>
            <td><input type="text" name="product_name[]" class="editable-input" required></td>
            <td>
                <select name="category[]" class="editable-dropdown">
                    <?php foreach ($activeCategories as $id => $name): ?>
                    <option value="<?= $id ?>"><?= htmlspecialchars($name) ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td>
                <select name="status[]" class="editable-dropdown">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </td>
            <td><input type="number" name="moq[]" class="editable-input" required min="1"></td>
            <td>No variants available</td>
            <input type="hidden" name="product_id[]" value="new">
        `;
            enableEditing();
        }
    </script>
</body>
</html>