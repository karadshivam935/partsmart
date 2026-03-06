<?php
require_once 'dbConnect.php';

if (!isset($_GET['email'])) {
    die("Invalid request");
}
$email = $_GET['email'];

$query = "SELECT u.*, s.Description AS Security_Question FROM user_detail_tbl u
          LEFT JOIN security_questions_tbl s ON u.Question_Id = s.Question_Id
          WHERE u.Email_Id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    die("User not found");
}
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Details of <?= htmlspecialchars($user['First_Name'] . ' ' . $user['Last_Name']) ?></title>
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

        .form-container {
            max-width: 800px;
            margin: auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        h2 {
            text-align: left;
            color: #333;
        }
        label {
            font-weight: bold;
            font-size: 16px;
            display: inline-block;
            width: 25%;
            margin-bottom: 10px;
        }
        input {
            width: 70%;
            padding: 10px;
            font-size: 16px;
            border-radius: 8px;
            border: 1px solid #ddd;
            background-color: #f9f9f9;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Details of <?= htmlspecialchars($user['First_Name'] . ' ' . $user['Last_Name']) ?></h2>
        <label>First Name:</label> <input type="text" value="<?= htmlspecialchars($user['First_Name']) ?>" readonly><br>
        <label>Last Name:</label> <input type="text" value="<?= htmlspecialchars($user['Last_Name']) ?>" readonly><br>
        <label>Phone Number:</label> <input type="text" value="<?= htmlspecialchars($user['Phone_Number']) ?>" readonly><br>
        <label>Gender:</label> <input type="text" value="<?= htmlspecialchars($user['Gender']) ?>" readonly><br>
        <label>Address:</label> <input type="text" value="<?= htmlspecialchars($user['Address']) ?>" readonly><br>
        <label>Pincode:</label> <input type="text" value="<?= htmlspecialchars($user['Pincode']) ?>" readonly><br>
        <label>Security Question:</label> <input type="text" value="<?= htmlspecialchars($user['Security_Question']) ?>" readonly><br>
        <label>Security Answer:</label> <input type="text" value="<?= htmlspecialchars($user['Security_Ans']) ?>" readonly><br>
        <label>User Type:</label> <input type="text" value="<?= htmlspecialchars($user['User_Type']) ?>" readonly><br>
    </div>
</body>
</html>