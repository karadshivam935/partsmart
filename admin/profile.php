<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['isLoggedIn'])) {
    header("Location: login.php");
    exit;
}

// Database connection
require_once '../config/dbConnect.php';

// Function to fetch user data from database
function getUserData($conn, $email)
{
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

// Get user data using email from session
$userData = getUserData($conn, $_SESSION['Email_id']);

// Handle form submission for profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $email = $_SESSION['Email_id']; // Use email as identifier

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
            $email // Email as identifier
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

        // Refresh user data after update
        $userData = getUserData($conn, $email);

        if ($userData === null) {
            throw new Exception("Failed to retrieve updated user data");
        }

        // Redirect to profile page to reflect changes
        header("Location: profile.php");
        exit();
    } catch (Exception $e) {
        error_log("Error updating profile: " . $e->getMessage());
        die("Error updating profile: " . $e->getMessage());
    }
}

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
    <link rel="stylesheet" href="../styles/navbar.css">
    <link rel="stylesheet" href="../styles/footer.css" />
    <style>
        body,
        html {
            margin: 0;
            padding: 100;
            box-sizing: border-box;
            overflow-x: hidden;
            font-family: Poppins, sans-serif;
            background-color: white;
            color: #373737;
        }

        body::-webkit-scrollbar {
            display: none
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }



        .profile-section,
        .orders-section {
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

        .orders-table {
            width: 100%;
            border-collapse: collapse;
        }

        .orders-table th,
        .orders-table td {
            padding: 12px;
            text-align: right;
            border-bottom: 1px solid #ddd;
        }

        .review-btn {
            background-color: #373737;
            color: white;
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .reviewed {
            color: #666;
            font-style: italic;
        }

        .password-link {
            display: inline-block;
            color: #373737;
            text-decoration: none;
            margin-top: 20px;
            font-weight: 500;
        }

        body,
        html {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            overflow-x: hidden;
            font-family: Poppins, sans-serif
        }

        body::-webkit-scrollbar {
            display: none
        }

        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            display: flex;
            justify-content: space-around;
            align-items: center;
            height: 11vh;
            background-color: #373737;
            color: #fff;
            font-weight: 700;
            box-shadow: 0 4px 8px rgba(255, 255, 255, .5);
            z-index: 1000;
            border-bottom: 1px solid #000
        }

        .btn-grp button {
            background-color: transparent;
            border: none
        }

        .btn-grp button:hover {
            background-color: #fff
        }

        .btn-grp a {
            color: #000;
            text-decoration: none
        }

        .btn-grp button:hover a {
            color: #000
        }

        .navbar a {
            color: #fff;
            text-decoration: none;
            cursor: pointer
        }

        .options li {
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer
        }

        button {
            background-color: #fff;
            color: #000;
            padding: 10px 20px;
            font-size: 16px;
            font-weight: 700;
            border-radius: 5px;
            cursor: pointer;
            transition: all .1s ease
        }

        button:hover {
            background-color: #fff;
            color: #000
        }

        .search-container {
            display: flex;
            justify-content: center;
            align-items: center
        }

        .search-form {
            display: flex;
            align-items: center;
            background-color: transparent
        }

        .search-bar {
            background-color: #fff;
            border: 1px solid #ccc;
            border-radius: 25px 0 0 25px;
            padding: 10px;
            width: 300px;
            font-size: 16px;
            outline: 0
        }

        .search-bar:focus {
            border-color: #007bff
        }

        .search-button {
            background-color: #e0e0e0;
            color: #333;
            border: 1px solid #ccc;
            padding: 10px 15px;
            border-radius: 0 25px 25px 0;
            cursor: pointer;
            transition: all .3s ease
        }

        .search-button:hover {
            background-color: #fff;
            color: #000;
            border-color: #333
        }

        .search-button:focus {
            outline: 0
        }

        .hero {
            height: 60vh;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 100px;
            padding: 20px;
            width: 80%;
            margin: 13vh auto 0
        }

        .hero-content {
            width: 50%
        }

        .hero-content h1 {
            font-size: 3rem;
            margin-bottom: 20px
        }

        .hero-content p {
            font-size: 1.2rem;
            margin-bottom: 20px
        }

        .hero-content button {
            padding: 10px 20px;
            font-size: 1rem;
            cursor: pointer
        }

        .hero-image {
            width: 50%
        }

        .hero-image img {
            max-width: 100%;
            height: 200px;
            border-radius: 10px
        }

        .hero h5 {
            font-weight: 100;
            text-align: justify
        }

        .elegant-quote {
            text-align: center;
            font-family: Apercu, sans-serif;
            background: #fff;
            color: #181818;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .1);
            max-width: 80%;
            height: 17vh;
            margin: 0 auto;
            transform: scale(1);
            transition: transform .3s ease, box-shadow .3s ease
        }

        .elegant-quote p {
            font-size: 1.5rem;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            line-height: 1.4;
            padding: 20px
        }

        .elegant-quote:hover {
            transform: scale(1.01);
            box-shadow: 0 15px 50px rgba(0, 0, 0, .15)
        }

        .features {
            width: 80%;
            margin: 1% auto;
            gap: 50px;
            text-align: center;
            margin: 0 auto
        }

        .feature-items {
            display: flex;
            align-items: center;
            justify-content: space-evenly
        }

        .feature-item {
            width: 30%;
            background-color: #f1f1f1;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, .1);
            transition: transform .3s ease
        }

        .feature-item h3 {
            font-size: 1.5rem;
            margin-bottom: 10px
        }

        .feature-item p {
            font-size: 1rem;
            margin-bottom: 15px
        }

        .feature-item:hover {
            transform: translateY(-10px)
        }

        .feature-item img {
            max-width: 100%;
            height: 30px;
            object-fit: contain;
            margin-bottom: 10px
        }

        .testimonials {
            padding: 60px 20px;
            background-color: #f8f8f8;
            text-align: center
        }

        .testimonial-item {
            margin-bottom: 40px;
            display: inline-block;
            width: 300px;
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .1);
            transform: scale(1);
            transition: transform .3s ease
        }

        .testimonial-item:hover {
            transform: scale(1.05)
        }

        .testimonial-item p {
            font-size: 1.1rem;
            font-style: italic;
            margin-bottom: 15px
        }

        .testimonial-item h4 {
            font-size: 1.2rem;
            font-weight: 700;
            color: #333
        }

        .testimonial-item img {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            margin-bottom: 10px
        }

        .footer {
            background-color: #373737;
            color: #fff;
            padding: 40px 20px;
            text-align: center
        }

        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            align-items: center
        }

        .footer-container p {
            font-size: 1rem;
            margin-bottom: 20px
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 50px;
            margin-top: 20px
        }

        .footer-links ul {
            list-style: none;
            padding: 0
        }

        .footer-links ul li {
            margin-bottom: 10px
        }

        .footer-links ul li a {
            text-decoration: none;
            color: #fff;
            font-size: 1rem;
            transition: color .3s ease
        }

        .footer-links ul li a:hover {
            color: #ccc
        }

        @media (max-width:768px) {
            .footer-links {
                flex-direction: column;
                gap: 20px
            }
        }

        .cta {
            margin: 0 auto;
            width: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px
        }

        .featured-products {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            text-align: center;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, .1)
        }

        .section-title {
            font-size: 28px;
            margin-bottom: 20px;
            color: #333
        }

        .products-container {
            display: flex;
            justify-content: space-between;
            align-items: center
        }

        .product-card {
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            width: 280px;
            text-align: center;
            transition: transform .3s ease
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, .15)
        }

        .product-card img {
            max-width: 100%;
            border-radius: 8px;
            margin-bottom: 15px
        }

        .product-card h3 {
            font-size: 18px;
            color: #333;
            margin: 10px 0
        }

        .product-card .price {
            font-size: 16px;
            font-weight: 700;
            color: #007bff;
            margin: 10px 0
        }

        .product-card div {
            display: flex;
            align-items: center;
            justify-content: space-between
        }

        .buttons {
            display: flex;
            justify-content: space-between;
            gap: 10px
        }

        .dropdown {
            position: relative;
            display: inline-block
        }

        .dropdown-content {
            display: none;
            position: absolute;
            background-color: #fff;
            min-width: 160px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, .2);
            z-index: 1;
            top: 100%;
            left: 0
        }

        .dropdown-content a {
            color: #000;
            padding: 12px 16px;
            text-decoration: none;
            display: block
        }

        .dropdown-content a:hover {
            background-color: #ddd
        }

        .user-name {
            cursor: pointer;
            color: #ffffff;
            font-weight: 500;
        }

        @keyframes fadeout {
            from {
                opacity: 1
            }

            to {
                opacity: 0
            }
        }

        .password-link:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="profile-section">
            <h2>Personal Information</h2>
            <form id="profileForm" action="profile.php" method="POST">
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
                        <option value="Male" <?php echo ($formData['gender'] == 'Male') ? 'selected' : ''; ?>>Male
                        </option>
                        <option value="Female" <?php echo ($formData['gender'] == 'Female') ? 'selected' : ''; ?>>Female
                        </option>
                        <option value="Other" <?php echo ($formData['gender'] == 'Other') ? 'selected' : ''; ?>>Other
                        </option>
                    </select>
                    <div id="gender_error" class="error-message"></div>
                </div>

                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea id="address"
                        name="address"><?php echo htmlspecialchars($formData['address']); ?></textarea>
                    <div id="address_error" class="error-message"></div>
                </div>

                <div class="form-group">
                    <label for="pincode">Pincode</label>
                    <input type="text" id="pincode" name="pincode"
                        value="<?php echo htmlspecialchars($formData['pincode']); ?>">
                    <div id="pincode_error" class="error-message"></div>
                </div>

                <div id="error-message" style="color: red; display: none;">
                    No changes detected. Please update at least one field.
                </div>

                <button type="submit" class="submit-btn">Update Profile</button>
            </form>

            <div id="success-message"
                style="display: none; padding: 10px; background-color: green; color: white; margin-top: 10px; position: fixed; top: 20px; left: 50%; transform: translateX(-50%); z-index: 1000;">
                Profile updated successfully!
            </div>
        </div>

        <div class="password-section">
            <a href="change_password.php" class="password-link">Change Password</a>
        </div>
    </div>


    <script src="../scripts/navbar.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const profileForm = document.getElementById('profileForm');
            const successMessage = document.getElementById('success-message');
            const errorMessage = document.getElementById('error-message');

            profileForm.addEventListener('submit', function (e) {
                let isValid = true;
                let hasChanges = false;

                // Get current form values
                const firstName = document.getElementById('first_name').value;
                const lastName = document.getElementById('last_name').value;
                const email = document.getElementById('email').value;
                const phone = document.getElementById('phone').value;
                const gender = document.getElementById('gender').value;
                const address = document.getElementById('address').value;
                const pincode = document.getElementById('pincode').value;

                // Get the initial values from PHP (embedded in the page)
                const initialFirstName = '<?php echo htmlspecialchars($formData['first_name']); ?>';
                const initialLastName = '<?php echo htmlspecialchars($formData['last_name']); ?>';
                const initialEmail = '<?php echo htmlspecialchars($formData['email']); ?>';
                const initialPhone = '<?php echo htmlspecialchars($formData['phone']); ?>';
                const initialGender = '<?php echo htmlspecialchars($formData['gender']); ?>';
                const initialAddress = '<?php echo htmlspecialchars($formData['address']); ?>';
                const initialPincode = '<?php echo htmlspecialchars($formData['pincode']); ?>';

                // Check if any value has changed
                if (firstName !== initialFirstName ||
                    lastName !== initialLastName ||
                    email !== initialEmail ||
                    phone !== initialPhone ||
                    gender !== initialGender ||
                    address !== initialAddress ||
                    pincode !== initialPincode) {
                    hasChanges = true;
                }

                // If no changes, show error message and prevent form submission
                if (!hasChanges) {
                    errorMessage.style.display = 'block';
                    e.preventDefault();
                    return false; // Prevent form submission
                } else {
                    errorMessage.style.display = 'none';
                }

                // Reset error messages and styles
                const errorFields = document.querySelectorAll('.error-message');
                errorFields.forEach(function (errorField) {
                    errorField.textContent = '';
                });
                const inputFields = document.querySelectorAll('input, select, textarea');
                inputFields.forEach(function (field) {
                    field.style.border = '';
                });

                // Validate form fields if values have changed
                const nameRegex = /^[A-Za-z]+$/;
                if (!nameRegex.test(firstName)) {
                    isValid = false;
                    document.getElementById('first_name').style.border = '2px solid red';
                    const error = document.createElement('span');
                    error.classList.add('error-message');
                    error.style.color = 'red';
                    error.textContent = 'First Name should only contain alphabets!';
                    document.getElementById('first_name').after(error);
                }

                if (!nameRegex.test(lastName)) {
                    isValid = false;
                    document.getElementById('last_name').style.border = '2px solid red';
                    const error = document.createElement('span');
                    error.classList.add('error-message');
                    error.style.color = 'red';
                    error.textContent = 'Last Name should only contain alphabets!';
                    document.getElementById('last_name').after(error);
                }

                const emailRegex = /^[a-z0-9]+(?:\.[a-z0-9]+)*@[a-z0-9]{2,}\.[a-z]{2,}$/i;
                if (!emailRegex.test(email)) {
                    isValid = false;
                    document.getElementById('email').style.border = '2px solid red';
                    const error = document.createElement('span');
                    error.classList.add('error-message');
                    error.style.color = 'red';
                    error.textContent = 'Please Enter Valid Email!';
                    document.getElementById('email').after(error);
                }

                const phoneRegex = /^[6-9]\d{9}$/;
                if (!phoneRegex.test(phone)) {
                    isValid = false;
                    document.getElementById('phone').style.border = '2px solid red';
                    const error = document.createElement('span');
                    error.classList.add('error-message');
                    error.style.color = 'red';
                    error.textContent = 'Please enter a valid Indian phone number!';
                    document.getElementById('phone').after(error);
                }

                if (gender === "") {
                    isValid = false;
                    document.getElementById('gender').style.border = '2px solid red';
                    const error = document.createElement('span');
                    error.classList.add('error-message');
                    error.style.color = 'red';
                    error.textContent = 'Please select a gender!';
                    document.getElementById('gender').after(error);
                }

                if (address.trim() === "") {
                    isValid = false;
                    document.getElementById('address').style.border = '2px solid red';
                    const error = document.createElement('span');
                    error.classList.add('error-message');
                    error.style.color = 'red';
                    error.textContent = 'Address cannot be empty!';
                    document.getElementById('address').after(error);
                }

                const pincodeRegex = /^3800[0-9][0-9]$/;
                if (!pincodeRegex.test(pincode)) {
                    isValid = false;
                    document.getElementById('pincode').style.border = '2px solid red';
                    const error = document.createElement('span');
                    error.classList.add('error-message');
                    error.style.color = 'red';
                    error.textContent = 'Please enter a valid Ahmedabad pincode!';
                    document.getElementById('pincode').after(error);
                }

                if (isValid && hasChanges) {
                    // Show success message if validation passes
                    successMessage.style.display = 'block';
                    // Hide error message
                    errorMessage.style.display = 'none';

                    // Prevent default form submission
                    e.preventDefault();

                    // Wait for 1.5 seconds before submitting the form
                    setTimeout(function () {
                        profileForm.submit(); // Submit the form after delay
                    }, 1500); // 1.5 seconds delay
                }

                // Prevent form submission if validation fails
                if (!isValid) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>

</html>