<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHP MAILER/Exception.php';
require 'PHP MAILER/PHPMailer.php';
require 'PHP MAILER/SMTP.php';

function generateOTP() {
    return rand(100000, 999999);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['order_id']) && isset($_POST['email'])) {
    session_start();
    $order_id = $_POST['order_id'];
    $email = $_POST['email'];

    // Check if an OTP was sent recently
    if (isset($_SESSION['otp_time'][$order_id])) {
        $timeElapsed = time() - $_SESSION['otp_time'][$order_id];

        if ($timeElapsed < 20) { // 20 seconds
            echo "OTP already sent. Try again after " . (20 - $timeElapsed) . " seconds.";
            exit;
        }
    }

    // Generate new OTP and update session
    $otp = generateOTP();
    $_SESSION['otp'][$order_id] = $otp;
    $_SESSION['otp_time'][$order_id] = time(); // Store current timestamp

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'karadshivam100@gmail.com';
        $mail->Password   = 'xgcnudwxynttxsva';  // Use App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('karadshivam100@gmail.com', 'PartsMart Delivery');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Your OTP for Order Delivery';
        $mail->Body    = "<p>Your OTP for order verification is: <b>$otp</b></p>";

        $mail->send();
        echo "success";
    } catch (Exception $e) {
        echo "Failed to send OTP.";
    }
} else {
    echo "Invalid request!";
}
