<?php
// Database credentials
$servername = "localhost"; // or your host, e.g. '127.0.0.1'
$username = "root"; // your MySQL username
$password = ""; // your MySQL password
$dbname = "partsmart_db"; // your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>