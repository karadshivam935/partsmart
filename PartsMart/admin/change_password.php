<?php
session_start();

// Redirect if user is not logged in
if (!isset($_SESSION['isLoggedIn']) || !isset($_SESSION['Email_id'])) {
    header("Location: login.php");
    exit;
}
// Include database connection
require_once '../config/dbConnect.php';

// Initialize message variable
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = trim($_POST['current_password']); // Remove whitespace
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    // Fetch the current password hash from the database
    $email = $_SESSION['Email_id'];
    $sql = "SELECT Password FROM user_detail_tbl WHERE Email_Id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $stored_password_hash = $user['Password'];

        // Debugging: Check values
        error_log("Entered: $current_password, Stored Hash: $stored_password_hash");

        // Verify current password
        if (!password_verify($current_password, $stored_password_hash)) {
            $message = "Incorrect current password.";
        } elseif ($new_password !== $confirm_password) {
            $message = "New password and confirm password do not match.";
        } elseif (strlen($new_password) < 6 || strlen($new_password) > 16) {
            $message = "Password must be between 6 and 16 characters.";
        } elseif (password_verify($new_password, $stored_password_hash)) {
            $message = "New password cannot be the same as the current password.";
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
                            window.top.location.href = '../client/logout.php';
                        }, 1500);
                      </script>";
            } else {
                $message = "Failed to update password. Please try again.";
            }
        }
    } else {
        $message = "User not found for email: $email";
    }

    $stmt->close();
    $conn->close();
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
            display: none
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
            margin-bottom: 1rem;
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
            margin-top: 1rem;
            font-size: 1rem;
            color: red;
        }

        .success-message {
            margin-top: 1rem;
            font-size: 1rem;
            color: green;
        }
    </style>
</head>

<body>
    <div class="password-container">
        <h2>Change Password</h2>

        <form method="POST" id="change-password-form">
            <label for="current_password">Current Password:</label>
            <input type="password" name="current_password" id="current_password" required>

            <label for="new_password">New Password:</label>
            <input type="password" name="new_password" id="new_password" required>

            <label for="confirm_password">Confirm New Password:</label>
            <input type="password" name="confirm_password" id="confirm_password" required>

            <button type="submit">Change Password</button>
        </form>

        <!-- Display the message after submission -->
        <?php if (isset($message)): ?>
            <p id="message"
                class="<?php echo (strpos($message, 'successfully') !== false) ? 'success-message' : 'error-message'; ?>">
                <?php echo $message; ?>
            </p>
        <?php endif; ?>
    </div>
</body>

</html>