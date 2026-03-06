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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Reports</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 800px;
            margin: auto;
            background: white;
            padding: 20px;
            box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.2);
            border-radius: 10px;
        }
        h2 {
            text-align: center;
        }
        .report-section {
            margin-bottom: 20px;
        }
        .report-section h3 {
            background: #333;
            color: white;
            padding: 10px;
            border-radius: 5px;
        }
        .report-list {
            list-style: none;
            padding: 0;
        }
        .report-list li {
            background: #ddd;
            margin: 5px 0;
            padding: 10px;
            border-radius: 5px;
            cursor: pointer;
            text-align: center;
        }
        .report-list li:hover {
            background: #bbb;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Generate Reports</h2>
        
        <div class="report-section">
            <h3>Sales Reports</h3>
            <ul class="report-list">
                <li onclick="generateReport('total_sales')">Total Sales Report</li>
                <li onclick="generateReport('sales_by_category')">Sales by Category</li>
            </ul>
        </div>
        
        <div class="report-section">
            <h3>Customer Reports</h3>
            <ul class="report-list">
                <li onclick="generateReport('clv')">Customer Lifetime Value (CLV) Report</li>
                <li onclick="generateReport('lost_customers')">Lost Customers Report</li>
            </ul>
        </div>
        
        <div class="report-section">
            <h3>Inventory Reports</h3>
            <ul class="report-list">
                <li onclick="generateReport('stock_levels')">Stock Levels Report</li>
                <li onclick="generateReport('low_stock')">Low Stock Alerts</li>
                <li onclick="generateReport('out_of_stock')">Out of Stock Report</li>
            </ul>
        </div>
        
        <div class="report-section">
            <h3>Order Management Reports</h3>
            <ul class="report-list">
                <li onclick="generateReport('pending_orders')">Pending Orders</li>
                <li onclick="generateReport('processed_orders')">Processed Orders</li>
                <li onclick="generateReport('out_for_delivery')">Out for Delivery Orders</li>
                <li onclick="generateReport('delivered_orders')">Delivered Orders</li>
            </ul>
        </div>
    </div>
    
    <script>
        function generateReport(reportType) {
            window.location.href = 'generate_report.php?type=' + reportType;
        }
    </script>
</body>
</html>
