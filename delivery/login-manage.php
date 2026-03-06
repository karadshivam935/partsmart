<?php

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['isLoggedIn']) || $_SESSION['isLoggedIn'] !== true) {
    header("Location: login.php");
    exit;
}

// Database connection
require_once '../config/dbConnect.php';

// Function to fetch user data from database
function getUserData($conn, $email) {
    try {
        $sql = "SELECT Email_Id, First_Name, Last_Name, Gender, Address, Pincode, Phone_Number, User_Type
                FROM user_detail_tbl
                WHERE Email_Id = ?";

        if (!$stmt = $conn->prepare($sql)) {
            error_log("Prepare failed: " . $conn->error);
            return null;
        }

        $stmt->bind_param("s", $email);

        if (!$stmt->execute()) {
            error_log("Execute failed: " . $stmt->error);
            return null;
        }

        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            error_log("No user found with email: " . $email);
            return null;
        }

        return $result->fetch_assoc();
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        return null;
    }
}

// Handle AJAX request for profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    $response = ['success' => false, 'message' => '', 'errors' => []];
    
    try {
        $email = $_SESSION['Email_id'];
        
        // Validate input
        $errors = [];
        
        // Name validation
        if (!preg_match('/^[A-Za-z]+$/', $_POST['first_name'])) {
            $errors['first_name'] = 'First Name should only contain alphabets!';
        }
        
        if (!empty($_POST['last_name']) && !preg_match('/^[A-Za-z]+$/', $_POST['last_name'])) {
            $errors['last_name'] = 'Last Name should only contain alphabets!';
        }
        
        // Phone validation
        if (!preg_match('/^[6-9]\d{9}$/', $_POST['phone'])) {
            $errors['phone'] = 'Please enter a valid Indian phone number!';
        }
        
        // Gender validation
        if (!in_array($_POST['gender'], ['Male', 'Female', 'Other'])) {
            $errors['gender'] = 'Please select a valid gender!';
        }
        
        // Address validation
        if (empty(trim($_POST['address']))) {
            $errors['address'] = 'Address cannot be empty!';
        }
        
        // Pincode validation
        if (!preg_match('/^3800[0-9][0-9]$/', $_POST['pincode'])) {
            $errors['pincode'] = 'Please enter a valid Ahmedabad pincode!';
        }
        
        if (!empty($errors)) {
            $response['errors'] = $errors;
            echo json_encode($response);
            exit;
        }
        
        // Prepare the UPDATE query
        $sql = "UPDATE user_detail_tbl SET
                First_Name = ?,
                Last_Name = ?,
                Phone_Number = ?,
                Gender = ?,
                Address = ?,
                Pincode = ?
                WHERE Email_Id = ?";

        if (!$stmt = $conn->prepare($sql)) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        // Bind parameters
        $stmt->bind_param(
            "sssssss",
            $_POST['first_name'],
            $_POST['last_name'],
            $_POST['phone'],
            $_POST['gender'],
            $_POST['address'],
            $_POST['pincode'],
            $email
        );

        // Execute the UPDATE query
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        // Update session data
        $_SESSION['user_name'] = $_POST['first_name'] . ' ' . $_POST['last_name'];
        $_SESSION['phone'] = $_POST['phone'];
        $_SESSION['address'] = $_POST['address'];
        $_SESSION['pincode'] = $_POST['pincode'];

        $response['success'] = true;
        $response['message'] = 'Profile updated successfully!';
        
    } catch (Exception $e) {
        error_log("Error updating profile: " . $e->getMessage());
        $response['message'] = "Error updating profile: " . $e->getMessage();
    }
    
    echo json_encode($response);
    exit;
}

// Get user data using email from session
$userData = getUserData($conn, $_SESSION['Email_id']);

// Format user data for the form (with session fallbacks)
$formData = [
    'first_name' => $userData['First_Name'] ?? explode(' ', $_SESSION['user_name'])[0] ?? '',
    'last_name' => $userData['Last_Name'] ?? explode(' ', $_SESSION['user_name'])[1] ?? '',
    'email' => $userData['Email_Id'] ?? $_SESSION['Email_id'] ?? '',
    'phone' => $userData['Phone_Number'] ?? $_SESSION['phone'] ?? '',
    'gender' => $userData['Gender'] ?? '',
    'address' => $userData['Address'] ?? $_SESSION['address'] ?? '',
    'pincode' => $userData['Pincode'] ?? $_SESSION['pincode'] ?? ''
];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Page</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&family=Poppins:wght@300;400;600&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
        integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
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
            background-color: #333;
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
            color: white;
        }

        .sidebar ul {
            list-style-type: none;
            padding: 0;
            margin: 0;
        }

        .sidebar li {
            margin: 10px 0;
        }

        .sidebar a {
            text-decoration: none;
            color: white;
            font-size: 16px;
            display: flex;
            align-items: center;
            padding: 12px 20px;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .sidebar a:hover {
            background-color: #444;
        }

        .sidebar a i {
            margin-right: 10px;
            font-size: 1.2rem;
        }

        /* Active link styling */
        .sidebar a.active {
            background-color: #555;
            font-weight: bold;
        }

        /* Main Content */
        .main-content {
            margin-left: 150px;
            padding: 20px;
            width: calc(100% - 230px);
            display: flex;
            flex-direction: column;
        }

        .profile-section {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        h2 {
            color: #373737;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            color: #373737;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        textarea {
            height: 100px;
            resize: vertical;
        }

        .password-link {
            display: inline-block;
            color: #373737;
            text-decoration: none;
            margin-top: 20px;
            font-weight: 500;
        }

        .password-link:hover {
            text-decoration: underline;
        }

        button {
            background-color: #333;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: #555;
        }

        .error-message {
            color: red;
            font-size: 12px;
            margin-top: 5px;
        }

        #success-message {
            display: none;
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            margin-top: 10px;
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1000;
            border-radius: 4px;
            animation: fadeInOut 3s ease-in-out;
        }

        #error-message {
            color: red;
            margin-bottom: 15px;
            display: none;
        }

        .error-border {
            border: 1px solid red !important;
        }

        @keyframes fadeInOut {
            0% { opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { opacity: 0; }
        }
    </style>
</head>

<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <nav class="sidebar">
            <h2>Delivery Agent</h2>
            <ul>
                <li><a href="?page=login-manage" class="active"><i class="fa-solid fa-circle-user"></i><span>Agent Profile</span></a></li>
                <li><a href="?page=order-status"><i class="fa-solid fa-cart-shopping"></i><span>Order Details</span></a></li>
                <li><a href="?page=order-history"><i class="fa-solid fa-cart-shopping"></i><span>Order History</span></a></li>
                <li><a href="?page=logout"><i class="fa-solid fa-arrow-right-from-bracket"></i><span>Log Out</span></a></li>
            </ul>
        </nav>

        <!-- Main Content Area -->
        <div class="main-content">
            <div class="profile-section">
                <h2>Personal Information</h2>
                <form id="profileForm" method="POST">
                    <input type="hidden" name="ajax" value="1">
                    
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name"
                            value="<?php echo htmlspecialchars($formData['first_name']); ?>" required>
                        <div id="first_name_error" class="error-message"></div>
                    </div>

                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name"
                            value="<?php echo htmlspecialchars($formData['last_name']); ?>">
                        <div id="last_name_error" class="error-message"></div>
                    </div>

                    <div class="form-group">
                        <label for="email">Email (Read-only)</label>
                        <input type="email" id="email" name="email"
                            value="<?php echo htmlspecialchars($_SESSION['Email_id']); ?>" readonly>
                        <div id="email_error" class="error-message"></div>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone"
                            value="<?php echo htmlspecialchars($formData['phone']); ?>" required>
                        <div id="phone_error" class="error-message"></div>
                    </div>

                    <div class="form-group">
                        <label for="gender">Gender</label>
                        <select id="gender" name="gender" required>
                            <option value="Male" <?php echo ($formData['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo ($formData['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo ($formData['gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                        <div id="gender_error" class="error-message"></div>
                    </div>

                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea id="address" name="address"><?php echo htmlspecialchars($formData['address']); ?></textarea>
                        <div id="address_error" class="error-message"></div>
                    </div>

                    <div class="form-group">
                        <label for="pincode">Pincode</label>
                        <input type="text" id="pincode" name="pincode"
                            value="<?php echo htmlspecialchars($formData['pincode']); ?>">
                        <div id="pincode_error" class="error-message"></div>
                    </div>

                    <div id="error-message">
                        No changes detected. Please update at least one field.
                    </div>

                    <button type="submit" class="submit-btn">Update Profile</button>
                </form>

                <div id="success-message">
                    Profile updated successfully!
                </div>
            </div>

            <div class="password-section">
                <a href="../client/change_password.php" class="password-link">Change Password</a>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const profileForm = document.getElementById('profileForm');
            const successMessage = document.getElementById('success-message');
            const errorMessage = document.getElementById('error-message');
            
            // Store initial form values
            const initialFormData = {
                first_name: document.getElementById('first_name').value,
                last_name: document.getElementById('last_name').value,
                phone: document.getElementById('phone').value,
                gender: document.getElementById('gender').value,
                address: document.getElementById('address').value,
                pincode: document.getElementById('pincode').value
            };

            profileForm.addEventListener('submit', function (e) {
                e.preventDefault();
                
                // Reset error messages and styles
                document.querySelectorAll('.error-message').forEach(el => el.textContent = '');
                document.querySelectorAll('input, select, textarea').forEach(el => 
                    el.classList.remove('error-border'));
                errorMessage.style.display = 'none';
                
                // Check if any field has changed
                let hasChanges = false;
                const currentFormData = {
                    first_name: document.getElementById('first_name').value,
                    last_name: document.getElementById('last_name').value,
                    phone: document.getElementById('phone').value,
                    gender: document.getElementById('gender').value,
                    address: document.getElementById('address').value,
                    pincode: document.getElementById('pincode').value
                };
                
                for (const key in currentFormData) {
                    if (currentFormData[key] !== initialFormData[key]) {
                        hasChanges = true;
                        break;
                    }
                }
                
                if (!hasChanges) {
                    errorMessage.style.display = 'block';
                    return;
                }
                
                // Submit form via AJAX
                const formData = new FormData(profileForm);
                
                fetch('login-manage.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message
                        successMessage.style.display = 'block';
                        setTimeout(() => {
                            successMessage.style.display = 'none';
                        }, 3000);
                        
                        // Update initial form data with new values
                        initialFormData.first_name = document.getElementById('first_name').value;
                        initialFormData.last_name = document.getElementById('last_name').value;
                        initialFormData.phone = document.getElementById('phone').value;
                        initialFormData.gender = document.getElementById('gender').value;
                        initialFormData.address = document.getElementById('address').value;
                        initialFormData.pincode = document.getElementById('pincode').value;
                    } else {
                        // Display validation errors
                        if (data.errors) {
                            for (const field in data.errors) {
                                const errorElement = document.getElementById(`${field}_error`);
                                const inputElement = document.getElementById(field);
                                
                                if (errorElement && inputElement) {
                                    errorElement.textContent = data.errors[field];
                                    inputElement.classList.add('error-border');
                                }
                            }
                        } else {
                            alert(data.message || 'Error updating profile');
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while updating the profile');
                });
            });
        });
    </script>
</body>
</html>