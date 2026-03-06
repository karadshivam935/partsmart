<?php
require_once '../config/dbConnect.php';
session_start();

// Ensure the user is logged in and is a delivery agent
if (!isset($_SESSION['isLoggedIn']) || $_SESSION['user_type'] !== 'Delivery Agent') {
    echo "<p style='color: red;'>Unauthorized Access!</p>";
    exit;
}

$email = $_SESSION['Email_id'];

// Handle OTP verification and order status update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['order_id']) && isset($_POST['otp'])) {
    $order_id = $_POST['order_id'];
    $otp = $_POST['otp'];

    if (isset($_SESSION['otp'][$order_id]) && $_SESSION['otp'][$order_id] == $otp) {
        // Update order status to 'Delivered', set Delivered_By and Delivery_Date
        $updateQuery = "UPDATE order_tbl 
                        SET Order_Status = 'Delivered', 
                            Delivered_By = ?,
                            Delivery_Date = NOW()
                        WHERE Order_Id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("si", $email, $order_id);

        if ($stmt->execute()) {
            unset($_SESSION['otp'][$order_id]); // Clear OTP after successful verification
            echo "<script>alert('Order marked as Delivered! Delivery recorded.'); window.location.href='index.php';</script>";
        } else {
            echo "<script>alert('Failed to update order status: " . addslashes($conn->error) . "');</script>";
        }
        $stmt->close();
    } else {
        echo "<script>alert('Invalid OTP. Please try again.');</script>";
    }
}

// Fetch orders that are 'Out for Delivery'
$query = "
    SELECT ot.Order_Id, ud.First_Name, ud.Last_Name, ud.Email_id, 
           ot.Delivery_Address, ud.Phone_Number, ot.Delivery_Pincode AS Pincode, ot.Order_Status
    FROM order_tbl ot
    JOIN user_detail_tbl ud ON ot.Email_Id = ud.Email_id
    WHERE ot.Order_Status = 'Out for Delivery'
";
$result = $conn->query($query);
$orders = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Out for Delivery Orders</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0px;
        }

        .container {
            width: 80%;
            margin: auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        .order-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .order-card {
            background: #fff;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-left: 5px solid #333;
        }

        .order-card p {
            margin: 5px 0;
        }

        .deliver-btn {
            background: #333;
            color: white;
            border: none;
            padding: 6px 12px;
            cursor: pointer;
            border-radius: 5px;
            width: auto;
            font-size: 14px;
            display: inline-block;
            margin-top: 10px;
        }

        .deliver-btn:hover {
            background: #555;
        }

        .otp-form {
            display: none;
            margin-top: 10px;
        }

        .otp-form input {
            padding: 8px;
            width: 70%;
            margin-right: 5px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        .otp-form button {
            padding: 8px 12px;
            border-radius: 5px;
            border: none;
            background: #333;
            color: white;
            cursor: pointer;
        }

        .otp-form button:hover {
            background: #555;
        }

        .status {
            font-weight: bold;
            color: green;
        }

        .no-orders {
            text-align: center;
            font-size: 18px;
            color: red;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>Out for Delivery Orders</h2>

        <?php if (!empty($orders)): ?>
            <div class="order-list">
                <?php foreach ($orders as $order): ?>
                    <div class="order-card">
                        <p><strong>Order ID:</strong> <?= htmlspecialchars($order['Order_Id']) ?></p>
                        <p><strong>Name:</strong> <?= htmlspecialchars($order['First_Name'] . ' ' . $order['Last_Name']) ?></p>
                        <p><strong>Phone Number:</strong> <?= htmlspecialchars($order['Phone_Number']) ?></p>
                        <p><strong>Address:</strong> <?= htmlspecialchars($order['Delivery_Address']) ?></p>
                        <p><strong>Pincode:</strong> <?= htmlspecialchars($order['Pincode']) ?></p>
                        <p class="status"><strong>Status:</strong> <?= htmlspecialchars($order['Order_Status']) ?></p>

                        <button class="deliver-btn"
                            onclick="sendOtp(<?= $order['Order_Id'] ?>, '<?= $order['Email_id'] ?>')">Send OTP</button>

                        <form method="POST" class="otp-form" id="otp-form-<?= $order['Order_Id'] ?>">
                            <input type="hidden" name="order_id" value="<?= $order['Order_Id'] ?>">
                            <input type="text" name="otp" placeholder="Enter OTP" required>
                            <button type="submit">Verify OTP</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="no-orders">No orders found.</p>
        <?php endif; ?>
    </div>

    <script>
        function sendOtp(orderId, email) {
            let button = document.querySelector(`button[onclick="sendOtp(${orderId}, '${email}')"]`);

            fetch('send-otp.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'order_id=' + orderId + '&email=' + encodeURIComponent(email)
            }).then(response => response.text()).then(data => {
                if (data.includes("success")) {
                    document.getElementById("otp-form-" + orderId).style.display = "block";
                    button.disabled = true;
                    button.style.background = "#999";
                    button.textContent = "OTP Sent";
                    alert("OTP has been sent successfully.");
                } else if (data.includes("OTP already sent")) {
                    alert(data);
                } else {
                    alert("Failed to send OTP. Please try again.");
                }
            });
        }
    </script>
</body>
</html>