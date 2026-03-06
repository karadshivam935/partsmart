<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");
session_start();

include '../config/dbConnect.php';

if (
    !isset($_SESSION['forgot_password_email']) ||
    !isset($_SESSION['security_question_id']) ||
    !isset($_SESSION['forgot_password_time']) ||
    (time() - $_SESSION['forgot_password_time']) > 600 ||
    $_SESSION['forgot_password_step'] != 2
) {
    unset($_SESSION['forgot_password_email']);
    unset($_SESSION['security_question_id']);
    unset($_SESSION['forgot_password_time']);
    $_SESSION['forgot_password_step'] = 0;
    header("Location: forgot-password.php");
    exit();
}

$email = $_SESSION['forgot_password_email'];
$passwordError = $confirmPasswordError = '';
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    $isValid = true;

    if (strlen($newPassword) < 6) {
        $passwordError = "Password must be at least 6 characters long!";
        $isValid = false;
    }

    if ($newPassword === "123456") {
        $passwordError = "Older password cannot be used. Choose a different one!";
        $isValid = false;
    }

    if ($newPassword !== $confirmPassword) {
        $confirmPasswordError = "Passwords do not match. Please try again.";
        $isValid = false;
    }

    if ($isValid) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE user_detail_tbl SET PASSWORD = ? WHERE Email_Id = ?");
        $stmt->bind_param("ss", $hashedPassword, $email);
        $stmt->execute();

        if ($stmt->affected_rows === 1) {
            $successMessage = "Password changed successfully!";
            unset($_SESSION['forgot_password_email']);
            unset($_SESSION['security_question_id']);
            unset($_SESSION['forgot_password_time']);
            $_SESSION['forgot_password_step'] = 0; // Reset step
        } else {
            $passwordError = "Failed to update password. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
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

        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .password-form {
            max-width: 400px;
            background: #fff;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .password-form h2 {
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 1rem;
            text-align: left;
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

        .button-group {
            margin-top: 1rem;
        }

        .submit-btn {
            width: 100%;
            padding: .8rem;
            background-color: #373737;
            color: #fff;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
        }

        .submit-btn:hover {
            background-color: #2b2b2b;
        }

        .success-box {
            display: none;
            background-color: #4CAF50;
            color: white;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="password-form">
            <h2>Change Password</h2>
            <div id="successBox" class="success-box"><?php echo $successMessage; ?></div>
            <form id="passwordForm" method="POST" action="">
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" required>
                    <p id="password-error" style="color: red;"><?php echo $passwordError; ?></p>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                    <p id="confirm-password-error" style="color: red;"><?php echo $confirmPasswordError; ?></p>
                </div>
                <div class="button-group">
                    <button type="submit" class="submit-btn">Change Password</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Prevent back navigation
        history.pushState(null, null, location.href);
        window.onpopstate = function () {
            window.location.href = "forgot-password.php";
        };

        document.getElementById("passwordForm").addEventListener("submit", function (event) {
            let isValid = true;
            const newPassword = document.getElementById("new_password");
            const confirmPassword = document.getElementById("confirm_password");
            const passwordError = document.getElementById("password-error");
            const confirmPasswordError = document.getElementById("confirm-password-error");

            if (newPassword.value.length < 6) {
                passwordError.textContent = "Password must be at least 6 characters long!";
                passwordError.style.display = "block";
                isValid = false;
            } else {
                passwordError.style.display = "none";
            }

            if (newPassword.value === "123456") {
                passwordError.textContent = "Older password cannot be used. Choose a different one!";
                passwordError.style.display = "block";
                isValid = false;
            }

            if (newPassword.value !== confirmPassword.value) {
                confirmPasswordError.textContent = "Passwords do not match. Please try again.";
                confirmPasswordError.style.display = "block";
                isValid = false;
            } else {
                confirmPasswordError.style.display = "none";
            }

            if (!isValid) {
                event.preventDefault();
            }
        });

        const successBox = document.getElementById("successBox");
        if (successBox.textContent.trim() !== "") {
            successBox.style.display = "block";
            setTimeout(() => {
                window.location.href = "login.php";
            }, 900);
        }
    </script>
</body>

</html>