<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database connection
require_once '../config/dbConnect.php';

// Handle form submission for agent registration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $email = $_POST['email'];
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'] ?? null; // Optional
        $phone = $_POST['phone'];
        $gender = $_POST['gender'];
        $address = $_POST['address'] ?? null; // Optional
        $pincode = $_POST['pincode'] ?? null; // Optional
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash the password
        $user_type = 'Delivery Agent'; // Match database enum value
        $reg_date = date('Y-m-d'); // Current date for Reg_Date
        $profile_status = 1; // Default to active (1)

        // Check if email already exists
        $check_sql = "SELECT Email_Id FROM user_detail_tbl WHERE Email_Id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            throw new Exception("Email already exists!");
        }

        // Prepare the INSERT query with all required fields
        $sql = "INSERT INTO user_detail_tbl (Email_Id, PASSWORD, First_Name, Last_Name, Phone_Number, Gender, Address, Pincode, User_Type, Reg_Date, Profile_Status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        if (!$stmt = $conn->prepare($sql)) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        // Bind parameters
        $stmt->bind_param(
            "ssssssssssi",
            $email,
            $password,
            $first_name,
            $last_name,
            $phone,
            $gender,
            $address,
            $pincode,
            $user_type,
            $reg_date,
            $profile_status
        );

        // Execute the INSERT query
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        // Redirect to the same page with a success message
        header("Location: agent.php?success=1");
        exit();
    } catch (Exception $e) {
        error_log("Error registering agent: " . $e->getMessage());
        $error_message = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent Registration</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="../styles/navbar.css">
    <link rel="stylesheet" href="../styles/footer.css" />
    <style>
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
            display: none;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .registration-section {
            margin-top: 0px;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 10px;
        }

        h2 {
            color: #373737;
            margin-bottom: 20px;
            font-size: 24px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            color: #373737;
            font-size: 14px;
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

        .submit-btn {
            background-color: #373737;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }

        .submit-btn:hover {
            opacity: 0.9;
        }

        .error-message {
            color: red;
            font-size: 12px;
            margin-top: 5px;
        }

        .success-message {
            display: none;
            padding: 10px;
            background-color: green;
            color: white;
            margin-top: 10px;
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1000;
            border-radius: 4px;
        }

        @media (max-width: 768px) {
            .container {
                max-width: 100%;
                padding: 10px;
            }

            h2 {
                font-size: 20px;
            }

            .submit-btn {
                width: 100%;
                padding: 8px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="registration-section">
            <h2>Agent Registration</h2>
            <form id="agentRegistrationForm" action="agent.php" method="POST">
                <div class="form-group">
                    <label for="first_name">First Name</label>
                    <input type="text" id="first_name" name="first_name" placeholder="Enter first name"
                        value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" required>
                    <div id="first_name_error" class="error-message"></div>
                </div>

                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" placeholder="Enter last name"
                        value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                    <div id="last_name_error" class="error-message"></div>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="agent@example.com"
                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                    <div id="email_error" class="error-message">
                        <?php echo isset($error_message) ? htmlspecialchars($error_message) : ''; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" placeholder="Enter phone number"
                        value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" required>
                    <div id="phone_error" class="error-message"></div>
                </div>

                <div class="form-group">
                    <label for="gender">Gender</label>
                    <select id="gender" name="gender" required>
                        <option value="" disabled <?php echo !isset($_POST['gender']) ? 'selected' : ''; ?>>Select
                            gender</option>
                        <option value="Male" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                        <option value="Other" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                    </select>
                    <div id="gender_error" class="error-message"></div>
                </div>

                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea id="address" name="address"
                        placeholder="Enter full address"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                    <div id="address_error" class="error-message"></div>
                </div>

                <div class="form-group">
                    <label for="pincode">Pincode</label>
                    <input type="text" id="pincode" name="pincode" placeholder="Enter pincode"
                        value="<?php echo htmlspecialchars($_POST['pincode'] ?? ''); ?>" required>
                    <div id="pincode_error" class="error-message"></div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter password (min 8 characters)"
                        required>
                    <div id="password_error" class="error-message"></div>
                </div>

                <button type="submit" class="submit-btn">Register Agent</button>
            </form>

            <div id="success-message" class="success-message">
                Agent registered successfully!
            </div>

            <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        const successMessage = document.getElementById('success-message');
                        successMessage.style.display = 'block';
                        setTimeout(() => {
                            successMessage.style.display = 'none';
                        }, 2000); // Hide after 2 seconds
                    });
                </script>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const agentForm = document.getElementById('agentRegistrationForm');
            const successMessage = document.getElementById('success-message');

            agentForm.addEventListener('submit', function (e) {
                let isValid = true;

                // Reset error messages and styles
                const errorFields = document.querySelectorAll('.error-message');
                errorFields.forEach(function (errorField) {
                    errorField.textContent = '';
                });
                const inputFields = document.querySelectorAll('input, select, textarea');
                inputFields.forEach(function (field) {
                    field.style.border = '';
                });

                // Validate form fields
                const nameRegex = /^[A-Za-z]+$/;
                const firstName = document.getElementById('first_name').value;
                if (!nameRegex.test(firstName)) {
                    isValid = false;
                    document.getElementById('first_name').style.border = '2px solid red';
                    document.getElementById('first_name_error').textContent = 'First Name should only contain alphabets!';
                }

                const lastName = document.getElementById('last_name').value;
                if (lastName && !nameRegex.test(lastName)) {
                    isValid = false;
                    document.getElementById('last_name').style.border = '2px solid red';
                    document.getElementById('last_name_error').textContent = 'Last Name should only contain alphabets!';
                }

                const emailRegex = /^[a-z0-9]+(?:\.[a-z0-9]+)*@[a-z0-9]{2,}\.[a-z]{2,}$/i;
                const email = document.getElementById('email').value;
                if (!emailRegex.test(email)) {
                    isValid = false;
                    document.getElementById('email').style.border = '2px solid red';
                    document.getElementById('email_error').textContent = 'Please enter a valid email!';
                }

                const phoneRegex = /^[6-9]\d{9}$/;
                const phone = document.getElementById('phone').value;
                if (!phoneRegex.test(phone)) {
                    isValid = false;
                    document.getElementById('phone').style.border = '2px solid red';
                    document.getElementById('phone_error').textContent = 'Please enter a valid Indian phone number!';
                }

                const gender = document.getElementById('gender').value;
                if (gender === "") {
                    isValid = false;
                    document.getElementById('gender').style.border = '2px solid red';
                    document.getElementById('gender_error').textContent = 'Please select a gender!';
                }

                const address = document.getElementById('address').value.trim();
                if (address === "") {
                    isValid = false;
                    document.getElementById('address').style.border = '2px solid red';
                    document.getElementById('address_error').textContent = 'Address cannot be empty!';
                }

                const pincodeRegex = /^3800[0-9][0-9]$/;
                const pincode = document.getElementById('pincode').value;
                if (!pincodeRegex.test(pincode)) {
                    isValid = false;
                    document.getElementById('pincode').style.border = '2px solid red';
                    document.getElementById('pincode_error').textContent = 'Please enter a valid Ahmedabad pincode!';
                }

                const password = document.getElementById('password').value;
                if (password.length < 8) {
                    isValid = false;
                    document.getElementById('password').style.border = '2px solid red';
                    document.getElementById('password_error').textContent = 'Password must be at least 8 characters long!';
                }

                if (!isValid) {
                    e.preventDefault();
                }
                // Note: Success message is handled by PHP redirect, so no need to show it here
            });
        });
    </script>
</body>

</html>