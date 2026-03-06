<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if user is not logged in
if (!isset($_SESSION['isLoggedIn']) || $_SESSION['isLoggedIn'] !== true) {
    header("Location: login.php");
    exit;
}

// Include database connection
require_once '../config/dbConnect.php';

// Initialize message, form data, and errors array
$message = "";
$formData = [
    'current_password' => '',
    'new_password' => '',
    'confirm_password' => ''
];
$errors = []; // Array to store specific field errors

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Store form data to retain values
    $formData['current_password'] = $_POST['current_password'];
    $formData['new_password'] = $_POST['new_password'];
    $formData['confirm_password'] = $_POST['confirm_password'];

    $current_password = $_POST['current_password']; // User entered current password
    $new_password = $_POST['new_password']; // New password
    $confirm_password = $_POST['confirm_password']; // Confirm password

    // Fetch the current password hash from the database
    $email = $_SESSION['Email_id'];
    $sql = "SELECT Password FROM user_detail_tbl WHERE Email_Id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $stored_password_hash = $user['Password']; // Hashed password from the database

        // Server-side validation
        if (!password_verify($current_password, $stored_password_hash)) {
            $errors['current_password'] = "Incorrect current password.";
        } elseif ($new_password !== $confirm_password) {
            $errors['confirm_password'] = "New password and confirm password do not match.";
        } elseif (strlen($new_password) < 6 || strlen($new_password) > 16) {
            $errors['new_password'] = "Password must be between 6 and 16 characters.";
        } elseif (password_verify($new_password, $stored_password_hash)) {
            $errors['new_password'] = "New password cannot be the same as the current password.";
        } else {
            // Hash the new password
            $new_password_hash = password_hash($new_password, PASSWORD_BCRYPT);

            // Update the password in the database
            $update_sql = "UPDATE user_detail_tbl SET Password = ? WHERE Email_Id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ss", $new_password_hash, $email);

            if ($update_stmt->execute()) {
                $message = "Password changed successfully! Redirecting...";
                echo "<script>
                        setTimeout(function() {
                            window.location.href = 'logout.php';
                        }, 900);
                      </script>";
            } else {
                $message = "Failed to update password. Please try again.";
            }
        }
    } else {
        $message = "User not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
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

        .password-container {
            max-width: 400px;
            margin: 3rem auto;
            background: #fff;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        label {
            display: block;
            margin-bottom: .5rem;
            font-weight: 500;
        }

        input {
            width: 100%;
            padding: .8rem;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 1rem;
        }

        button {
            width: 100%;
            padding: .8rem;
            background-color: #373737;
            color: #fff;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
        }

        button:hover {
            background-color: #2b2b2b;
        }

        .error-message {
            color: #dc3545;
            /* Red color */
            font-size: 0.875em;
            margin-top: 5px;
            display: none;
        }

        .success-message {
            margin-top: 1rem;
            font-size: 1rem;
            color: green;
        }

        .general-message {
            margin-top: 1rem;
            font-size: 1rem;
        }
    </style>
</head>

<body>
    <div class="password-container">
        <h2>Change Password</h2>

        <form method="POST" id="change-password-form">
            <div class="form-group">
                <label for="current_password">Current Password:</label>
                <input type="password" name="current_password" id="current_password"
                    value="<?php echo htmlspecialchars($formData['current_password']); ?>" required>
                <div id="current_password_error" class="error-message">
                    <?php echo isset($errors['current_password']) ? $errors['current_password'] : ''; ?>
                </div>
            </div>

            <div class="form-group">
                <label for="new_password">New Password:</label>
                <input type="password" name="new_password" id="new_password"
                    value="<?php echo htmlspecialchars($formData['new_password']); ?>" required>
                <div id="new_password_error" class="error-message">
                    <?php echo isset($errors['new_password']) ? $errors['new_password'] : ''; ?>
                </div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm New Password:</label>
                <input type="password" name="confirm_password" id="confirm_password"
                    value="<?php echo htmlspecialchars($formData['confirm_password']); ?>" required>
                <div id="confirm_password_error" class="error-message">
                    <?php echo isset($errors['confirm_password']) ? $errors['confirm_password'] : ''; ?>
                </div>
            </div>

            <button type="submit">Change Password</button>
        </form>

        <!-- Display general message after submission -->
        <?php if (!empty($message)): ?>
            <p id="message"
                class="general-message <?php echo (strpos($message, 'successfully') !== false) ? 'success-message' : 'error-message'; ?>">
                <?php echo $message; ?>
            </p>
        <?php endif; ?>
    </div>

    <script>
        document.getElementById('change-password-form').addEventListener('submit', function (event) {
            let isValid = true;

            // Get form values
            const currentPassword = document.getElementById('current_password').value;
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            // Reset error messages (client-side only, server-side errors persist)
            const errorElements = document.querySelectorAll('.error-message');
            errorElements.forEach(el => {
                if (!el.textContent) { // Only reset if no server-side error
                    el.style.display = 'none';
                    el.textContent = '';
                }
            });

            // Client-side validation
            if (currentPassword === '') {
                isValid = false;
                const error = document.getElementById('current_password_error');
                error.textContent = 'Current password is required.';
                error.style.display = 'block';
            }

            if (newPassword.length < 6 || newPassword.length > 16) {
                isValid = false;
                const error = document.getElementById('new_password_error');
                error.textContent = 'Password must be between 6 and 16 characters.';
                error.style.display = 'block';
            }

            if (newPassword !== confirmPassword) {
                isValid = false;
                const error = document.getElementById('confirm_password_error');
                error.textContent = 'New password and confirm password do not match.';
                error.style.display = 'block';
            }

            // Prevent submission if client-side validation fails
            if (!isValid) {
                event.preventDefault();
            }

            // If server-side errors exist, ensure they are displayed
            <?php if (!empty($errors)): ?>
                document.querySelectorAll('.error-message').forEach(el => {
                    if (el.textContent) {
                        el.style.display = 'block';
                    }
                });
                event.preventDefault(); // Prevent submission if server-side errors exist
            <?php endif; ?>
        });

        // Disable back button after logout
        window.history.pushState(null, null, window.location.href);
        window.onpopstate = function () {
            window.history.pushState(null, null, window.location.href);
        };
    </script>
</body>

</html>