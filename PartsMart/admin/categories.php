<?php
session_start();
require_once 'dbConnect.php';

// Fetch categories from the database
$query = "SELECT Category_Id, Category_Name, Visibility_Mode FROM category_tbl";
$result = mysqli_query($conn, $query);

$categories = [];
$maxId = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $categories[] = $row;
    if ($row['Category_Id'] > $maxId) {
        $maxId = $row['Category_Id'];
    }
}

mysqli_free_result($result);

// Fetch total products count for each category
foreach ($categories as $index => $category) {
    $categoryId = $category['Category_Id'];
    $productQuery = "SELECT COUNT(*) AS product_count FROM product_tbl WHERE Category_Id = ?";
    $stmt = $conn->prepare($productQuery);
    $stmt->bind_param("i", $categoryId);
    $stmt->execute();
    $stmt->bind_result($totalProducts);
    $stmt->fetch();
    $categories[$index]['total_products'] = $totalProducts ?? 0;
    $stmt->close();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['Category_Id'], $_POST['name'], $_POST['status'])) {
        foreach ($_POST['Category_Id'] as $index => $categoryId) {
            $categoryName = $_POST['name'][$index];
            $categoryStatus = isset($_POST['status'][$index]) && $_POST['status'][$index] !== "" 
                ? (int)$_POST['status'][$index] 
                : 1;

            if ($categoryId == "new") {
                $query = "INSERT INTO category_tbl (Category_Name, Visibility_Mode) VALUES (?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("si", $categoryName, $categoryStatus);
                $stmt->execute();
                $stmt->close();
            } else {
                // Update category
                $query = "UPDATE category_tbl SET Category_Name = ?, Visibility_Mode = ? WHERE Category_Id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("sii", $categoryName, $categoryStatus, $categoryId);
                $stmt->execute();
                $stmt->close();

                // Cascade status to products if category is made inactive
                if ($categoryStatus == 0) {
                    $productUpdateQuery = "UPDATE product_tbl SET Visibility_Mode = 0 WHERE Category_Id = ?";
                    $stmt = $conn->prepare($productUpdateQuery);
                    $stmt->bind_param("i", $categoryId);
                    $stmt->execute();
                    $stmt->close();
                }
                if ($categoryStatus == 1) {
                    $productUpdateQuery = "UPDATE product_tbl SET Visibility_Mode = 1 WHERE Category_Id = ?";
                    $stmt = $conn->prepare($productUpdateQuery);
                    $stmt->bind_param("i", $categoryId);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }

        $_SESSION['message'] = "Changes saved successfully! Product statuses updated where applicable.";
        header("Location: categories.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories</title>
    <style>
        /* Existing CSS unchanged */
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
        .save-btn, .add-category-btn {
            padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer; transition: 0.3s;
        }
        .save-btn { background-color: #28a745; color: white; }
        .save-btn:disabled { background-color: #ddd; cursor: not-allowed; }
        .add-category-btn { background-color: #007bff; color: white; }
        .editable { outline: none; border: none; background: transparent; padding: 5px; display: inline-block; min-width: 100px; }
        select, input { padding: 5px; border-radius: 5px; border: none; }
    </style>
</head>
<body>
    <h2>Category List</h2>

    <?php
    if (isset($_SESSION['message'])) {
        echo "<p style='color: green;'>{$_SESSION['message']}</p>";
        unset($_SESSION['message']);
    }
    ?>

    <form id="category-form" method="POST">
        <table id="category-table">
            <tr>
                <th>ID</th>
                <th>NAME</th>
                <th>TOTAL PRODUCTS</th>
                <th>STATUS</th>
            </tr>

            <?php foreach ($categories as $index => $category): ?>
                <tr>
                    <td><?= $category['Category_Id'] ?></td>
                    <td>
                        <span class="editable" data-index="<?= $index ?>"><?= htmlspecialchars($category['Category_Name']) ?></span>
                        <input type="hidden" name="name[]" value="<?= htmlspecialchars($category['Category_Name']) ?>">
                    </td>
                    <td><?= intval($category['total_products']) ?></td>
                    <td>
                        <select name="status[]" data-index="<?= $index ?>">
                            <option value="1" <?= $category['Visibility_Mode'] == 1 ? 'selected' : '' ?>>Active</option>
                            <option value="0" <?= $category['Visibility_Mode'] == 0 ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </td>
                    <input type="hidden" name="Category_Id[]" value="<?= $category['Category_Id'] ?>">
                </tr>
            <?php endforeach; ?>
        </table>

        <div class="button-container">
            <button type="submit" class="save-btn" id="save-changes">💾 SAVE CHANGES</button>
            <button type="button" class="add-category-btn" id="add-category" onclick="addNewCategory()">+ ADD CATEGORY</button>
        </div>
    </form>

    <script>
        // Existing JavaScript unchanged
        let nextCategoryId = <?= $maxId + 1 ?>;
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.editable').forEach(cell => {
                cell.contentEditable = true;
                cell.classList.add('editing');
            });
            document.querySelectorAll('select').forEach(select => {
                select.disabled = false;
            });
            document.getElementById('save-changes').disabled = false;
        });

        document.getElementById("category-form").addEventListener("submit", function (event) {
            let isValid = true;
            document.querySelectorAll('input[name="name[]"]').forEach(input => {
                if (!validateCategoryName(input)) {
                    isValid = false;
                }
            });

            if (!isValid) {
                event.preventDefault();
                alert("Please fix validation errors before submitting.");
            }
        });

        function addNewCategory() {
            let table = document.getElementById("category-table");
            let newRow = table.insertRow(-1);
        
            newRow.innerHTML = `
                <td>${nextCategoryId}</td>
                <td>
                    <input type="text" name="name[]" placeholder="Enter Category Name" oninput="validateCategoryName(this)">
                    <span class="error-message" style="color: red; font-size: 12px; display: none;">Invalid Name!</span>
                </td>
                <td>0</td>
                <td>
                    <select name="status[]">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </td>
                <input type="hidden" name="Category_Id[]" value="new">
            `;

            nextCategoryId++;
            checkFormValidity();
        }

        function validateCategoryName(input) {
            let regex = /^[A-Za-z][A-Za-z0-9\s]{2,}$/;
            let errorMessage = input.nextElementSibling;

            if (!regex.test(input.value.trim())) {
                input.style.border = "2px solid red";
                errorMessage.style.display = "inline";
                document.getElementById('save-changes').disabled = true;
                return false;
            } else {
                input.style.border = "2px solid green";
                errorMessage.style.display = "none";
                checkFormValidity();
                return true;
            }
        }

        function checkFormValidity() {
            let allValid = true;
            document.querySelectorAll('input[name="name[]"]').forEach(input => {
                if (!/^[A-Za-z][A-Za-z0-9\s]{2,}$/.test(input.value.trim())) {
                    allValid = false;
                }
            });
            document.getElementById('save-changes').disabled = !allValid;
        }

        document.querySelectorAll('.editable').forEach(cell => {
            cell.addEventListener('input', function () {
                let index = this.dataset.index;
                document.getElementsByName("name[]")[index].value = this.innerText;
            });
        });

        document.querySelectorAll('select[data-index]').forEach(select => {
            select.addEventListener('change', function () {
                let index = this.dataset.index;
                document.getElementsByName("status[]")[index].value = this.value;
            });
        });
    </script>
</body>
</html>