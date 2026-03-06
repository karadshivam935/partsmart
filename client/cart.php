<?php
session_start();

// Include the database connection file
require '../config/dbConnect.php';

// Check if the user is logged in
if (!isset($_SESSION['Email_id'])) {
    header('Location: login.php'); // Redirect to login if not logged in
    exit;
}

$email = $_SESSION['Email_id'];

// Fetch the user's cart ID
$sql = "SELECT Cart_Id FROM cart_tbl WHERE Email_Id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Create a new cart for the user if it doesn't exist
    $sql = "INSERT INTO cart_tbl (Email_Id, Creation_Date) VALUES (?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $cartId = $stmt->insert_id;
} else {
    // Use the existing cart
    $row = $result->fetch_assoc();
    $cartId = $row['Cart_Id'];
}

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $input = json_decode(file_get_contents('php://input'), true);
    $response = ['success' => false, 'message' => ''];

    if (isset($input['action'])) {
        switch ($input['action']) {
            case 'update':
                if (isset($input['pdt_det_id']) && isset($input['change'])) {
                    $pdtDetId = intval($input['pdt_det_id']);
                    $change = intval($input['change']);

                    // Fetch current quantity
                    $sql = "SELECT Qty FROM cart_detail_tbl WHERE Cart_Id = ? AND Pdt_Det_Id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ii", $cartId, $pdtDetId);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0) {
                        $row = $result->fetch_assoc();
                        $newQuantity = $row['Qty'] + $change;

                        if ($newQuantity > 0) {
                            // Update quantity in the database
                            $sql = "UPDATE cart_detail_tbl SET Qty = ? WHERE Cart_Id = ? AND Pdt_Det_Id = ?";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("iii", $newQuantity, $cartId, $pdtDetId);
                            $stmt->execute();

                            $response['success'] = true;
                            $response['message'] = 'Quantity updated';
                        } else {
                            $response['message'] = 'Quantity must be greater than 0';
                        }
                    }
                }
                break;

            case 'remove':
                if (isset($input['pdt_det_id'])) {
                    $pdtDetId = intval($input['pdt_det_id']);

                    // Remove item from the database
                    $sql = "DELETE FROM cart_detail_tbl WHERE Cart_Id = ? AND Pdt_Det_Id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ii", $cartId, $pdtDetId);
                    $stmt->execute();

                    $response['success'] = true;
                    $response['message'] = 'Item removed';
                }
                break;

            case 'add':
                if (isset($input['pdt_det_id']) && isset($input['quantity'])) {
                    $pdtDetId = intval($input['pdt_det_id']);
                    $quantity = intval($input['quantity']);

                    // Check if the product already exists in the cart
                    $sql = "SELECT Qty FROM cart_detail_tbl WHERE Cart_Id = ? AND Pdt_Det_Id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ii", $cartId, $pdtDetId);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0) {
                        // Update quantity if the product already exists
                        $row = $result->fetch_assoc();
                        $newQuantity = $row['Qty'] + $quantity;

                        $sql = "UPDATE cart_detail_tbl SET Qty = ? WHERE Cart_Id = ? AND Pdt_Det_Id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("iii", $newQuantity, $cartId, $pdtDetId);
                    } else {
                        // Add new product to the cart
                        $sql = "INSERT INTO cart_detail_tbl (Cart_Id, Pdt_Det_Id, Qty, Price) VALUES (?, ?, ?, (SELECT Price FROM product_details_tbl WHERE Pdt_Det_Id = ?))";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("iiii", $cartId, $pdtDetId, $quantity, $pdtDetId);
                    }

                    if ($stmt->execute()) {
                        $response['success'] = true;
                        $response['message'] = 'Item added to cart';
                    } else {
                        $response['message'] = 'Failed to add item to cart';
                    }
                }
                break;
        }
    }

    echo json_encode($response);
    exit;
}

// Fetch cart items from the database
$sql = "
    SELECT pd.Pdt_Det_Id, p.Product_Name, pd.Price, cd.Qty, pd.ImagePath
    FROM cart_detail_tbl cd
    JOIN product_details_tbl pd ON cd.Pdt_Det_Id = pd.Pdt_Det_Id
    JOIN product_tbl p ON pd.Product_Id = p.Product_Id
    WHERE cd.Cart_Id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $cartId);
$stmt->execute();
$result = $stmt->get_result();

$cartItems = [];
while ($row = $result->fetch_assoc()) {
    $cartItems[] = [
        'id' => $row['Pdt_Det_Id'],
        'name' => $row['Product_Name'],
        'price' => $row['Price'],
        'quantity' => $row['Qty'],
        'image' => $row['ImagePath']
    ];
}

// Calculate totals
function calculateTotals($cartItems)
{
    $subtotal = 0;
    foreach ($cartItems as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }

    $shipping = 150.00;
    $tax = $subtotal * 0.18;
    $total = $subtotal + $shipping + $tax;

    return [
        'subtotal' => $subtotal,
        'tax' => $tax,
        'total' => $total
    ];
}

$totals = calculateTotals($cartItems);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../styles/navbar.css" />
    <link rel="stylesheet" href="../styles/footer.css" />
    <style>
        body,
        html {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            overflow-x: hidden;
            font-family: Poppins, sans-serif;
        }

        body::-webkit-scrollbar {
            display: none
        }

        /* Container Styles */
        .container {
            max-width: 1200px;
            margin: 13vh auto 5vh;
            padding: 2rem;
        }

        /* Cart Title */
        .cart-title {
            font-size: 2.5rem;
            font-weight: 600;
            margin-bottom: 2rem;
            color: #2a2a2a;
            text-align: center;
        }

        /* Cart Layout */
        .cart-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }

        /* Cart Items Section */
        .cart-items {
            background-color: #ffffff;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }

        .cart-item {
            display: grid;
            grid-template-columns: auto 2fr 1fr 1fr auto;
            gap: 1.5rem;
            align-items: center;
            padding: 1.5rem 0;
            border-bottom: 1px solid #e0e0e0;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        /* Item Image */
        .item-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        /* Item Details */
        .item-details h3 {
            margin: 0 0 0.5rem 0;
            font-size: 1.2rem;
            color: #2a2a2a;
        }

        .item-price {
            font-weight: 600;
            color: #2a2a2a;
            font-size: 1.1rem;
        }

        /* Quantity Controls */
        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .quantity-btn {
            background-color: #f5f5f5;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .quantity-btn:hover {
            background-color: white;
            color: black;
            transform: scale(1.05);
        }

        .quantity-display {
            font-weight: 600;
            min-width: 40px;
            text-align: center;
            font-size: 1.1rem;
        }

        /* Remove Button */
        .remove-btn {
            background-color: #ff4444;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .remove-btn:hover {
            background-color: white;
            color: black;
            transform: scale(1.05);
        }

        /* Cart Summary Section */
        .cart-summary {
            background-color: #ffffff;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            height: fit-content;
            position: sticky;
            top: 2rem;
        }

        .cart-summary h2 {
            margin-bottom: 1.5rem;
            color: #2a2a2a;
            font-size: 1.8rem;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            padding: 0.5rem 0;
            font-size: 1.1rem;
        }

        .summary-total {
            border-top: 2px solid #e0e0e0;
            padding-top: 1rem;
            margin-top: 1rem;
            font-weight: 600;
            font-size: 1.3rem;
            color: #2a2a2a;
        }

        /* Checkout Button */
        .checkout-btn {
            background-color: #373737;
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 1.5rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .checkout-btn:hover {
            background-color: white;
            color: black;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        /* Empty Cart State */
        .empty-cart {
            text-align: center;
            padding: 3rem;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }

        .empty-cart p {
            font-size: 1.2rem;
            color: #666;
            margin-bottom: 1rem;
        }

        /* Toast Notification */
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 2rem;
            background-color: #333;
            color: white;
            border-radius: 8px;
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 1000;
        }

        .toast.show {
            opacity: 1;
        }

        /* Loading Spinner */
        .loading-spinner {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1000;
        }

        .loading-spinner::after {
            content: '';
            display: block;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 1rem;
                margin: 10vh auto 3vh;
            }

            .cart-title {
                font-size: 2rem;
                margin-bottom: 1.5rem;
            }

            .cart-container {
                grid-template-columns: 1fr;
            }

            .cart-item {
                grid-template-columns: auto 1fr;
                grid-template-rows: auto auto auto;
                gap: 1rem;
            }

            .item-image {
                grid-row: span 2;
                width: 80px;
                height: 80px;
            }

            .item-details {
                grid-column: 2;
            }

            .quantity-controls {
                grid-column: 2;
            }

            .item-price {
                grid-column: 1 / -1;
                text-align: right;
            }

            .remove-btn {
                grid-column: 1 / -1;
            }

            .cart-summary {
                margin-top: 2rem;
                position: static;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 0.5rem;
            }

            .cart-title {
                font-size: 1.8rem;
            }

            .cart-items,
            .cart-summary {
                padding: 1rem;
            }

            .item-image {
                width: 60px;
                height: 60px;
            }

            .item-details h3 {
                font-size: 1rem;
            }

            .quantity-btn {
                padding: 0.3rem 0.8rem;
            }
        }
    </style>
</head>

<body>
    <?php include('header.php'); ?>

    <div class="container">
        <h1 class="cart-title">Shopping Cart</h1>

        <?php if (empty($cartItems)): ?>
            <div class="empty-cart">
                <p>Your cart is empty</p>
                <a href="products.php" class="checkout-btn">Continue Shopping</a>
            </div>
        <?php else: ?>
            <div class="cart-container">
                <div class="cart-items">
                    <?php foreach ($cartItems as $item): ?>
                        <div class="cart-item" id="item-<?php echo $item['id']; ?>">
                            <img src="<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>" class="item-image">
                            <div class="item-details">
                                <h3><?php echo $item['name']; ?></h3>
                            </div>
                            <div class="item-price">₹<?php echo number_format($item['price'], 2); ?></div>
                            <div class="quantity-controls">
                                <button class="quantity-btn" onclick="updateQuantity(<?php echo $item['id']; ?>, -1)">-</button>
                                <span class="quantity-display"
                                    id="qty-<?php echo $item['id']; ?>"><?php echo $item['quantity']; ?></span>
                                <button class="quantity-btn" onclick="updateQuantity(<?php echo $item['id']; ?>, 1)">+</button>
                            </div>
                            <button class="remove-btn" onclick="removeItem(<?php echo $item['id']; ?>)">Remove</button>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="summary">
                    <div class="summary-item">
                        <span>Shipping Fee</span>
                        <span id="shipping">₹150.00</span>
                    </div>
                    <div class="summary-item">
                        <span>GST (18%)</span>
                        <span id="tax">₹<?php echo number_format($totals['tax'], 2); ?></span>
                    </div>
                    <div class="summary-item summary-total">
                        <span>Total</span>
                        <span id="total">₹<?php echo number_format($totals['total'], 2); ?></span>
                    </div>
                    <button class="checkout-btn" onclick="proceedToCheckout()">Proceed to Checkout</button>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="toast"></div>

    <!-- Loading Spinner -->
    <div id="loading-spinner" class="loading-spinner"></div>

    <?php include('footer.php'); ?>

    <script src="../scripts/navbar.js"></script>
    <script>
        function updateQuantity(id, change) {
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'update', pdt_det_id: id, change: change })
            })
                .then(response => response.json())
                .then(response => {
                    if (response.success) {
                        showToast(response.message);
                        // Reload the page after a short delay
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000); // 1 second delay
                    } else {
                        showToast(response.message);
                    }
                })
                .catch(() => showToast('An error occurred'));
        }

        function removeItem(id) {
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'remove', pdt_det_id: id })
            })
                .then(response => response.json())
                .then(response => {
                    if (response.success) {
                        showToast(response.message);
                        // Reload the page after a short delay
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000); // 1 second delay
                    } else {
                        showToast(response.message);
                    }
                })
                .catch(() => showToast('An error occurred'));
        }

        function addToCart(pdtDetId, quantity) {
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'add', pdt_det_id: pdtDetId, quantity: quantity })
            })
                .then(response => response.json())
                .then(response => {
                    if (response.success) {
                        showToast(response.message);
                        // Reload the page after a short delay
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000); // 1 second delay
                    } else {
                        showToast(response.message);
                    }
                })
                .catch(() => showToast('An error occurred'));
        }

        function showToast(message) {
            const toast = document.createElement('div');
            toast.classList.add('toast', 'show');
            toast.textContent = message;
            document.body.appendChild(toast);

            setTimeout(() => {
                toast.classList.remove('show');
                toast.remove();
            }, 900);
        }

        function proceedToCheckout() {
            window.location.href = 'checkout.php';
        }
    </script>
</body>

</html>