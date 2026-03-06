<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");
session_start();

include '../config/dbConnect.php';

// Initialize form data and error
$formData = ['email' => ''];
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['email'] = $_POST['email']; // Retain entered email
    $email = $_POST['email'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        $stmt = $conn->prepare("SELECT Email_Id, Question_Id FROM user_detail_tbl WHERE Email_Id = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $_SESSION['forgot_password_email'] = $user['Email_Id'];
            $_SESSION['security_question_id'] = $user['Question_Id'];
            $_SESSION['forgot_password_time'] = time();
            $_SESSION['forgot_password_step'] = 1; // Move to step 1
            header("Location: verify-answer.php");
            exit();
        } else {
            $error = "Email not found. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
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

        .forgot-password-container {
            max-width: 400px;
            margin: 3rem auto;
            background: #fff;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .forgot-password-container h2 {
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
    </style>
</head>

<body>
    <div class="forgot-password-container">
        <h2>Forgot Password</h2>
        <form method="POST" id="forgot-password-form">
            <div class="form-group">
                <label for="email">Enter your Email:</label>
                <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($formData['email']); ?>"
                    required>
                <div id="email-error" class="error-message">
                    <?php echo !empty($error) ? $error : ''; ?>
                </div>
            </div>
            <button type="submit">Proceed</button>
        </form>
    </div>

    <script>
        // Prevent back navigation
        history.pushState(null, null, location.href);
        window.onpopstate = function () {
            window.location.href = "forgot-password.php";
        };

        document.getElementById("forgot-password-form").addEventListener("submit", function (e) {
            let isValid = true;
            const emailInput = document.getElementById("email");
            const emailError = document.getElementById("email-error");

            // Reset error message if no server-side error exists
            if (!emailError.textContent) {
                emailError.style.display = "none";
                emailError.textContent = "";
            }

            // Client-side email validation
            const emailRegex = /^[a-z0-9]+(?:\.[a-z0-9]+)*@[a-z0-9]{2,}\.[a-z]{2,}$/i;
            if (!emailRegex.test(emailInput.value)) {
                emailError.textContent = "Please enter a valid email address.";
                emailError.style.display = "block";
                isValid = false;
            }

            // Prevent submission if validation fails
            if (!isValid) {
                e.preventDefault();
            }

            // If server-side error exists, ensure it’s displayed
            <?php if (!empty($error)): ?>
                emailError.style.display = "block";
                e.preventDefault(); // Prevent submission if server-side error exists
            <?php endif; ?>
        });
    </script>
</body>

</html>