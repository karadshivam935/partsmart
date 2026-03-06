<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// Check if user is already logged in
if (isset($_SESSION['isLoggedIn']) && $_SESSION['isLoggedIn'] === true) {
    // Redirect based on user type
    switch ($_SESSION['user_type']) {
        case 'Customer':
            header("Location: index.php");
            break;
        case 'Delivery Agent':
            header("Location: ../delivery/index.php");
            break;
        case 'Admin':
            header("Location: ../admin/index.php");
            break;
        default:
            // If user_type is invalid, destroy session and redirect to login
            session_destroy();
            header("Location: login.php");
            break;
    }
    exit(); // Ensure no further code is executed
}
include '../config/dbConnect.php';

// Initialize error and success messages
$error = "";
$success = "";
$inactiveMessage = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Prepare SQL to prevent SQL injection
    $stmt = $conn->prepare("SELECT * FROM user_detail_tbl WHERE Email_Id = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        // Verify password
        if (password_verify($password, $user['PASSWORD'])) {
            // Check if the user is active
            if ($user['Profile_Status'] == 1) {
                // Set common session variables
                $_SESSION['isLoggedIn'] = true;
                $_SESSION['Email_id'] = $user['Email_Id'];
                $_SESSION['user_name'] = $user['First_Name'] . ' ' . $user['Last_Name'];
                $_SESSION['user_type'] = $user['User_Type'];
                $_SESSION['phone'] = $user['Phone_Number'];
                $_SESSION['address'] = $user['Address'];
                $_SESSION['pincode'] = $user['Pincode'];

                // Set success message
                $_SESSION['success'] = "Login successful! Redirecting...";

                // Store the redirect URL in a session variable
                switch ($user['User_Type']) {
                    case 'Customer':
                        $_SESSION['redirect_url'] = 'index.php';
                        break;
                    case 'Delivery Agent':
                        $_SESSION['redirect_url'] = '../delivery/index.php';
                        break;
                    case 'Admin':
                        $_SESSION['redirect_url'] = '../admin/index.php';
                        break;
                    default:
                        $error = "Invalid user type";
                        break;
                }
            } else {
                $inactiveMessage = "Your account is inactive. Please contact your admin.";
            }
        } else {
            $error = "Incorrect password";
        }
    } else {
        $error = "Invalid email or password";
    }
}

// Check for success message in session
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']); // Clear the message after displaying
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login Page</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&family=Poppins:wght@300;400;600&display=swap"
        rel="stylesheet" />
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
            display: none;
        }

        .login-container {
            max-width: 400px;
            margin: 3rem auto;
            background: #fff;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, .1);
            text-align: center;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: .5rem;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: .8rem;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 1rem;
        }

        .btn {
            width: 100%;
            padding: .8rem;
            background-color: #373737;
            color: #fff;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
        }

        .btn:hover {
            background-color: #2b2b2b;
        }

        .error-message {
            color: red;
            margin-bottom: 1rem;
        }

        .inactive-message {
            color: #ff9800;
            /* Orange color for warning */
            margin-top: 1rem;
            font-size: 0.9rem;
        }

        /* Success message box */
        #success-message {
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
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>

<body>
    <!-- Success message box -->
    <div id="success-message">
        Profile updated successfully!
    </div>

    <div class="login-container">
        <form method="POST" action="" id="login-form">
            <h2>Login</h2>

            <!-- Display error message -->
            <?php if (!empty($error)): ?>
                <p class="error-message"><?php echo $error; ?></p>
            <?php endif; ?>

            <div class="form-group">
                <label for="email">Email ID</label>
                <input type="email" id="email" name="email" placeholder="Enter your email" required
                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" />
                <p id="email-error" class="error-message" style="display: none;"></p>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required
                    value="<?php echo isset($_POST['password']) ? htmlspecialchars($_POST['password']) : ''; ?>" />
                <p id="password-error" class="error-message" style="display: none;"></p>
            </div>

            <button type="submit" class="btn">Login</button>

            <!-- Display inactive user message -->
            <?php if (!empty($inactiveMessage)): ?>
                <p class="inactive-message"><?php echo $inactiveMessage; ?></p>
            <?php endif; ?>

            <p class="forgot-password"><a href="forgot-password.php">Forgot Password?</a></p>
            <p class="signup-text">Haven't yet signed up? <a href="signup.php">Signup now</a></p>
        </form>
    </div>

    <script>
        const isLoggedIn = <?php echo isset($_SESSION['isLoggedIn']) && $_SESSION['isLoggedIn'] === true ? 'true' : 'false'; ?>;
        if (isLoggedIn) {
            const userType = "<?php echo $_SESSION['user_type'] ?? ''; ?>";
            let redirectUrl = '';
            switch (userType) {
                case 'Customer':
                    redirectUrl = 'index.php';
                    break;
                case 'Delivery Agent':
                    redirectUrl = '../delivery/index.php';
                    break;
                case 'Admin':
                    redirectUrl = '../admin/index.php';
                    break;
                default:
                    redirectUrl = 'login.php';
                    break;
            }
            // Avoid the page redirect here to let success message show for a brief moment
            setTimeout(() => {
                window.location.replace(redirectUrl); // Delay the redirect
            }, 900); // Set to 1.2 seconds (1200 ms)
        }

        // Display success message and redirect after 1.2 seconds
        const successMessage = document.getElementById("success-message");
        const success = "<?php echo $success; ?>";

        if (success) {
            // Update the success message text
            successMessage.textContent = success;

            // Show the success message
            successMessage.style.display = "block";

            // Redirect after 1.2 seconds
            setTimeout(() => {
                window.location.href = "<?php echo $_SESSION['redirect_url'] ?? 'index.php'; ?>";
            }, 900); // 1.2 seconds delay for success message
        }

        // Form validation
        document.getElementById("login-form").addEventListener("submit", function (e) {
            let isValid = true;
            const emailInput = document.getElementById("email");
            const passwordInput = document.getElementById("password");
            const emailError = document.getElementById("email-error");
            const passwordError = document.getElementById("password-error");

            // Email validation regex: At least 3 before '@', 4 after '@', no uppercase or special chars
            const emailRegex = /^[a-z0-9]+(?:\.[a-z0-9]+)*@[a-z0-9]{2,}\.[a-z]{2,}$/i;

            if (!emailRegex.test(emailInput.value)) {
                emailError.textContent = "Please Enter Valid Email!";
                emailError.style.display = "block";
                isValid = false;
            } else {
                emailError.style.display = "none";
            }

            // Password validation: Minimum 6 characters
            if (passwordInput.value.length < 6) {
                passwordError.textContent = "Password must be at least 6 characters long!";
                passwordError.style.display = "block";
                isValid = false;
            } else {
                passwordError.style.display = "none";
            }

            // Prevent form submission if validation fails
            if (!isValid) {
                e.preventDefault();
            }
        });
    </script>

</body>

</html>