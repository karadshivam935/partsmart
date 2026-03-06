<?php
session_start();

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// Check if user is logged in and is an Admin
if (!isset($_SESSION['isLoggedIn']) || $_SESSION['isLoggedIn'] !== true || $_SESSION['user_type'] !== 'Admin') {
    header("Location: ../client/login.php");
    exit();
}

// Include database connection
require '../config/dbConnect.php';

// Fetch order statistics
$orderStats = [
    'Processing' => 0,
    'Processed' => 0,
    'Out for Delivery' => 0,
    'Delivered' => 0
];

$sqlOrders = "SELECT Order_Status, COUNT(*) as count FROM order_tbl GROUP BY Order_Status";
$resultOrders = $conn->query($sqlOrders);
if ($resultOrders) {
    while ($row = $resultOrders->fetch_assoc()) {
        $orderStats[$row['Order_Status']] = $row['count'];
    }
}

// Fetch user statistics
$userStats = [
    'Active' => 0,
    'Deactivated' => 0,
    'Total' => 0
];

$sqlUsers = "SELECT Profile_Status, COUNT(*) as count FROM user_detail_tbl GROUP BY Profile_Status";
$resultUsers = $conn->query($sqlUsers);
if ($resultUsers) {
    while ($row = $resultUsers->fetch_assoc()) {
        if ($row['Profile_Status'] == 1) {
            $userStats['Active'] = $row['count'];
        } else {
            $userStats['Deactivated'] = $row['count'];
        }
    }
    $userStats['Total'] = $userStats['Active'] + $userStats['Deactivated'];
}

// Close database connection
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Dashboard</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&family=Poppins:wght@300;400;600&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Poppins", sans-serif;
        }

        body,
        html {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            overflow-x: hidden;
            font-family: Poppins, sans-serif;
            background-color: white;
            color: #373737;
        }

        body::-webkit-scrollbar {
            display: none
        }

        body {
            display: flex;
            height: 100vh;
            overflow: auto;
        }

        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 60px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #333;
            color: white;
            padding: 0 20px;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
            z-index: 1000;
        }

        .navbar .title {
            font-size: 1.2rem;
            font-weight: bold;
        }

        .navbar .search {
            flex-grow: 1;
            text-align: center;
        }

        .navbar .search input {
            width: 50%;
            padding: 5px 10px;
            font-size: 1rem;
            border-radius: 5px;
            border: none;
        }

        .navbar .profile {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .navbar .profile a {
            text-decoration: none;
            color: white;
        }

        .navbar .profile img {
            width: 30px;
            height: 30px;
            border-radius: 50%;
        }

        .sidebar {
            width: 250px;
            background-color: #333;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: flex-start;
            padding: 20px;
            gap: 15px;
            position: fixed;
            top: 60px;
            bottom: 0;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: white;
            padding: 12px 20px;
            font-size: 1rem;
            transition: background 0.3s ease;
            width: 100%;
            text-align: left;
        }

        .sidebar a:hover {
            background-color: #444;
        }

        .sidebar a i {
            font-size: 1.2rem;
        }

        .dropdown {
            width: 100%;
            position: relative;
        }

        .dropdown-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 12px 20px;
            font-size: 1rem;
            color: white;
            background-color: #333;
            cursor: pointer;
            transition: background 0.3s ease;
            width: 100%;
        }

        .dropdown-toggle:hover {
            background-color: #444;
        }

        .dropdown-toggle i {
            font-size: 1.2rem;
        }
        .dropdown-toggle span{
            display:flex;
            gap:20px;
        }

        .dropdown-menu {
            display: none;
            width: 100%;
            background-color: #444;
            flex-direction: column;
            position: absolute;
            top: 100%;
            left: 0;
            z-index: 1000;
        }

        .dropdown:hover .dropdown-menu {
            display: flex;
        }

        .dropdown-menu a {
            padding: 10px 40px;
            font-size: 0.9rem;
        }

        .dropdown-menu a:hover {
            background-color: #555;
        }

        .main-content {
            flex-grow: 1;
            margin-left: 260px;
            margin-top: 55px;
            padding: 20px;
            text-align: left;
            min-height: calc(100vh - 70px);
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .cards-container {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
        }

        .card {
            background-color: #f5f5f5;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.2);
            width: 200px;
            text-align: center;
        }

        iframe[name="content-frame"] {
            width: 100%;
            border: none;
            margin-top: 20px;
            display: none;
            /* Height is removed and will be set dynamically via JS */
        }

        /* Responsive Design */
        @media screen and (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                flex-direction: row;
                justify-content: space-between;
                padding: 10px;
                top: 0;
            }

            .main-content {
                margin-left: 0px;
            }

            .navbar .search input {
                width: 70%;
            }

            a {
                text-decoration: none;
                color: white;
            }

            .dropdown-toggle {
                padding: 8px 10px;
            }

            .dropdown-menu a {
                padding: 8px 20px;
            }
        }
    </style>
    <script>
        function showIframe(page) {
            const iframe = document.getElementById("content-frame");
            document.getElementById("default-content").style.display = "none";
            iframe.style.display = "block";
            iframe.src = page;
        }

        function resizeIframe() {
            const iframe = document.getElementById("content-frame");
            if (iframe.style.display === "block" && iframe.contentWindow.document.body) {
                const contentHeight = iframe.contentWindow.document.body.scrollHeight;
                iframe.style.height = `${contentHeight + 20}px`; // Add padding for safety
            }
        }

        window.onpageshow = function (event) {
            if (event.persisted) {
                window.location.reload();
            }
        };

        const isLoggedIn = <?php echo isset($_SESSION['isLoggedIn']) && $_SESSION['isLoggedIn'] === true ? 'true' : 'false'; ?>;
        const userType = "<?php echo $_SESSION['user_type'] ?? ''; ?>";
        if (!isLoggedIn || userType !== 'Admin') {
            window.location.href = '../login.php';
        }
    </script>
</head>

<body>
    <div class="navbar">
        <div class="title">PartsMart</div>
        <div class="profile">
            <span>
                <a href="#" onclick="showIframe('profile.php')">Hi, Admin</a>
            </span>
        </div>
    </div>
    <div class="sidebar">
        <a href="#" onclick="showIframe('order_shivam.php')"><i class="fas fa-box"></i> Orders</a>
        <a href="#" onclick="showIframe('categories.php')"><i class="fas fa-th-list"></i> Categories</a>
        <a href="#" onclick="showIframe('products.php')"><i class="fas fa-cogs"></i> Products</a>
        <a href="#" onclick="showIframe('users.php')"><i class="fas fa-users"></i> Users</a>
        <a href="#" onclick="showIframe('agent.php')"><i class="fas fa-user-plus"></i> Agent
            Registration</a>
        <div class="dropdown">
            <div class="dropdown-toggle">
                <span><i class="fas fa-file-alt"></i> Reports</span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="dropdown-menu">
                <a href="#" onclick="showIframe('./reports/sales_reports.php')">Sales Report</a>
                <a href="#" onclick="showIframe('./reports/customer_reports.php')">Customer Report</a>
                <a href="#" onclick="showIframe('./reports/inventory_reports.php')">Inventory Report</a>
                <a href="#" onclick="showIframe('./reports/delivery_details_reports.php')">Delivery Details Report</a>
            </div>
        </div>
        <a href="../client/logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i><span>Log Out</span></a>
    </div>

    <div class="main-content">
        <div id="default-content">
            <div class="section-title">Orders</div>
            <div class="cards-container">
                <div class="card">Processing <br> <?php echo $orderStats['Processing']; ?></div>
                <div class="card">Processed <br> <?php echo $orderStats['Processed']; ?></div>
                <div class="card">Out For Delivery <br> <?php echo $orderStats['Out for Delivery']; ?></div>
                <div class="card">Delivered <br> <?php echo $orderStats['Delivered']; ?></div>
            </div>
            <div class="section-title">Users</div>
            <div class="cards-container">
                <div class="card">Active Users <br> <?php echo $userStats['Active']; ?></div>
                <div class="card">Deactivated Users <br> <?php echo $userStats['Deactivated']; ?></div>
                <div class="card">Total Users <br> <?php echo $userStats['Total']; ?></div>
            </div>
            <p>Welcome to the admin dashboard! Click on the sidebar links to manage orders, products, and more.</p>
        </div>
        <iframe name="content-frame" src="" loading="lazy" id="content-frame" onload="resizeIframe()"></iframe>
    </div>
</body>

</html>