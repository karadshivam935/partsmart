<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// Static OTP for testing
$staticOtp = '2604';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = $_POST['otp'];

    if ($otp === $staticOtp) {
        // If OTP is correct, redirect to change password page
        header("Location: newpassword.php");
        exit();
    } else {
        // If OTP is incorrect, show error message
        $error = "Incorrect OTP. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP</title>
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

        .verify-otp-container {
            max-width: 400px;
            margin: 3rem auto;
            background: #fff;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .verify-otp-container h2 {
            margin-bottom: 1rem;
            font-weight: 600;
        }

        form {
            text-align: left;
        }

        form label {
            display: block;
            margin-bottom: .5rem;
            font-weight: 500;
        }

        form input {
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

        p {
            font-size: 0.9rem;
            margin-top: 1rem;
        }
    </style>
</head>

<body>
    <div class="verify-otp-container">
        <h2>Enter OTP to Reset Your Password</h2>
        <form method="POST" action="verify-otp.php" id="otp-form">
            <label for="otp">Enter OTP:</label>
            <input type="text" name="otp" id="otp" required maxlength="4">
            <p id="otp-error" style="color: red; display: none;"></p>
            <button type="submit">Submit OTP</button>
        </form>
        <?php if (isset($error)): ?>
            <p style="color: red;"><?php echo $error; ?></p>
        <?php endif; ?>
    </div>

    <script>
        document.getElementById("otp-form").addEventListener("submit", function (e) {
            let isValid = true;
            const otpInput = document.getElementById("otp");
            const otpError = document.getElementById("otp-error");

            // OTP validation: Only 4 digits allowed
            const otpRegex = /^[0-9]{4}$/;

            if (!otpRegex.test(otpInput.value)) {
                otpError.textContent = "OTP must be exactly 4 digits!";
                otpError.style.display = "block";
                isValid = false;
            } else {
                otpError.style.display = "none";
            }

            // Prevent form submission if validation fails
            if (!isValid) {
                e.preventDefault();
            }
        });
    </script>

</body>

</html>