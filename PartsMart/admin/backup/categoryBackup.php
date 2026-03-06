<?php
session_start();  // Start the session

// Include database connection file
require_once 'dbConnect.php';

// Fetch categories from the database
$query = "SELECT Category_Id, Category_Name, Visibility_Mode FROM category_tbl";
$result = mysqli_query($conn, $query);

// Initialize categories array
$categories = [];
while ($row = mysqli_fetch_assoc($result)) {
    $categories[] = $row;
}

// Free the result set after fetching categories
mysqli_free_result($result);

// Fetch the total number of products for each category
foreach ($categories as $index => $category) {
    $categoryId = $category['Category_Id'];

    // Query to get the number of products in each category
    $productQuery = "SELECT COUNT(*) AS product_count FROM product_tbl WHERE Category_Id = ?";
    $stmt = $conn->prepare($productQuery); 
    $stmt->bind_param("i", $categoryId);
    $stmt->execute();
    $stmt->bind_result($totalProducts);
    $stmt->fetch();

    // Update the category array with the total number of products
    $categories[$index]['total_products'] = $totalProducts;

    // Close the prepared statement after execution
    $stmt->close();
}

// Handle form submission to update categories
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['Category_Id'], $_POST['name'], $_POST['status'])) {
        foreach ($_POST['Category_Id'] as $index => $categoryId) {
            $categoryName = $_POST['name'][$index];
            $categoryStatus = $_POST['status'][$index];

            // Update category details using prepared statement
            $query = "UPDATE category_tbl SET Category_Name = ?, Visibility_Mode = ? WHERE Category_Id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sii", $categoryName, $categoryStatus, $categoryId);
            $stmt->execute();
            $stmt->close();
        }

        // Set success message and redirect back to category.php
        $_SESSION['message'] = "Changes saved successfully!";
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
        display: flex;
        justify-content: space-between;
        padding-top: 10px;
    }

    .edit-btn,
    .save-btn,
    .add-category-btn {
        padding: 10px 15px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        transition: 0.3s;
    }

    .edit-btn {
        background-color: #007bff;
        color: white;
    }

    .save-btn {
        background-color: #28a745;
        color: white;
    }

    .save-btn:disabled {
        background-color: #ddd;
        cursor: not-allowed;
    }

    .editable {
        outline: none;
        /* Remove outline */
        border: none;
        /* Remove border */
        background: transparent;
        /* Make background transparent */
        padding: 5px;
        display: inline-block;
        /* Ensure it's properly aligned */
        min-width: 100px;}

        .add-category-btn {
            background-color: #ff9800;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        select,
        input {
            padding: 5px;
            border-radius: 5px;
        }
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
        <table>
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
                    <span class="editable"
                        data-index="<?= $index ?>"><?= htmlspecialchars($category['Category_Name']) ?></span>
                    <input type="hidden" name="name[]" value="<?= htmlspecialchars($category['Category_Name']) ?>">
                </td>
                <td><?= intval($category['total_products']) ?></td>
                <td>
                    <select name="status[]" data-index="<?= $index ?>" disabled>
                        <option value="1" <?= $category['Visibility_Mode'] == 1 ? 'selected' : '' ?>>Active</option>
                        <option value="0" <?= $category['Visibility_Mode'] == 0 ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </td>
                <input type="hidden" name="Category_Id[]" value="<?= $category['Category_Id'] ?>">
            </tr>
            <?php endforeach; ?>

        </table>

        <div class="button-container">
            <button type="button" class="edit-btn" id="edit-btn" onclick="enableEditing()">✎ EDIT CATEGORIES</button>
            <button type="submit" class="save-btn" id="save-changes" disabled>💾 SAVE CHANGES</button>
            <a href="addCategory.php" class="add-category-btn">+ ADD CATEGORY</a>
        </div>
    </form>

    <script>
    let isEditing = false;

    function enableEditing() {
        if (isEditing) return;
        isEditing = true;

        document.querySelectorAll('.editable').forEach(cell => {
            cell.contentEditable = true;
            cell.classList.add('editing');
        });

        document.querySelectorAll('select').forEach(select => {
            select.disabled = false;
        });

        document.getElementById('save-changes').disabled = false;
    }

    // Ensure correct values are submitted
    document.querySelectorAll('.editable').forEach(cell => {
        cell.addEventListener('input', function() {
            let index = this.dataset.index;
            document.getElementsByName("name[]")[index].value = this.innerText;
        });
    });

    document.querySelectorAll('select').forEach(select => {
        select.addEventListener('change', function() {
            let index = this.dataset.index;
            document.getElementsByName("status[]")[index].value = this.value;
        });
    });
    </script>

</body>

</html>