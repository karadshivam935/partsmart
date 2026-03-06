<?php
// Set default page to 'order-status'
$page = isset($_GET['page']) ? $_GET['page'] : 'order-status';
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// Check if user is logged in and redirect only if they're not supposed to be here
if (isset($_SESSION['isLoggedIn']) && $_SESSION['isLoggedIn'] === true) {
    switch ($_SESSION['user_type']) {
        case 'Customer':
            header("Location: ../client/index.php");
            // Do nothing; they're already on the correct page (index.php)
            exit();
        case 'Delivery Agent':
            break;
        case 'Admin':
            header("Location: ../admin/index.php");
            exit();
        default:
            session_destroy();
            header("Location: login.php");
            exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Agent</title>
   <style>
    body,
html,
ul,
li,
a {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: Arial, sans-serif;
    display: flex;
    min-height: 100vh;
    background-color: #f9f9f9;
    overflow-x: hidden;
}

/* Dashboard Layout */
.dashboard {
    display: flex;
    width: 100%;
}

/* Sidebar */
.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    width: 230px;
    height: 100%;
    background-color: #333; /* Dark background like admin.php */
    color: white;
    padding: 20px;
    display: flex;
    flex-direction: column;
    box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
}

.sidebar h2 {
    margin-bottom: 20px;
    font-size: 24px;
    text-align: center;
    color: white; /* Ensure text is white */
}

.sidebar ul {
    list-style-type: none;
    padding: 0;
    margin: 0;
}

.sidebar li {
    margin: 10px 0; /* Reduced margin for compact look */
}

.sidebar a {
    text-decoration: none;
    color: white;
    font-size: 16px; /* Slightly smaller font size */
    display: flex;
    align-items: center;
    padding: 12px 20px; /* Padding like admin.php */
    border-radius: 5px; /* Rounded corners */
    transition: background-color 0.3s ease;
}

.sidebar a:hover {
    background-color: #444; /* Hover effect like admin.php */
}

.sidebar a i {
    margin-right: 10px; /* Spacing between icon and text */
    font-size: 1.2rem; /* Icon size like admin.php */
}

/* Active link styling */
.sidebar a.active {
    background-color: #555; /* Active link background */
    font-weight: bold; /* Highlight active link */
}

/* Main Content */
.main-content {
    margin-left: 250px; /* Ensure main content is not hidden behind sidebar */
    padding: 20px;
    width: calc(100% - 250px); /* Take up the remaining space */
    display: flex;
    flex-direction: column;
}

.main-content section {
    margin-bottom: 40px;
}

.main-content h1 {
    font-size: 24px;
    margin-bottom: 10px;
}

.main-content p {
    font-size: 16px;
    color: #555;
}

/* Log Out Button */
button {
    background-color: #dc3545;
    color: white;
    padding: 10px 15px;
    border: none;
    border-radius: 4px;
    font-size: 16px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

button:hover {
    background-color: #c82333;
}

iframe {
    width: 100%;
    height: 100vh;
    border: none;
}
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
        integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>

<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <nav class="sidebar">
            <h2>Delivery Agent</h2>
            <ul>
                <li><a href="?page=login-manage" id="profile-link"><i class="fa-solid fa-circle-user"></i><span>Agent
                            Profile</span></a></li>

                <li><a href="?page=order-status" id="orders-link"><i class="fa-solid fa-cart-shopping"></i><span>Order
                            Details</span></a></li>
                <li><a href="?page=order-history" id="orders-link"><i class="fa-solid fa-cart-shopping"></i><span>Order
                            History</span></a></li>
                <li><a href="?page=logout" id="logout-link"><i
                            class="fa-solid fa-arrow-right-from-bracket"></i><span>Log Out</span></a></li>
            </ul>
        </nav>

        <!-- Main Content Area -->
        <div class="main-content">
            <?php
            // PHP Logic to load content dynamically based on the page parameter
            switch ($page) {
                case 'login-manage':
                    include('login-manage.php');
                    break;
                case 'order-status':
                    include('OrderStatus.php');
                    break;
                case 'order-history':
                    include('orderdata.php');
                    break;
                case 'logout':
                    header("Location: ../client/logout.php"); // Redirect to login page
                    exit();
                default:
                    header("Location: OrderStatus.php");
            }
            ?>
        </div>
    </div>

    <script>
   // Prevent access from cache after logout
        window.onpageshow = function (event) {
            if (event.persisted) {
                window.location.reload();
            }
        };

        // Highlight the active link in the sidebar
        document.querySelectorAll('.sidebar a').forEach(link => {
            if (link.getAttribute('href') === `?page=<?php echo $page; ?>`) {
                link.classList.add('active');
            }
        });
       
    </script>
</body>

</html>