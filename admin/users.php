<?php
session_start();
require_once 'dbConnect.php';

// Fetch user details
$userQuery = "SELECT First_Name, Last_Name, Email_Id, Gender, Profile_Status FROM user_detail_tbl";
$result = $conn->query($userQuery);
$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

// Handle profile status update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    foreach ($_POST['email'] as $index => $email) {
        $profile_status = intval($_POST['profile_status'][$index]);
        
        $updateQuery = "UPDATE user_detail_tbl SET Profile_Status = ? WHERE Email_Id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("is", $profile_status, $email);
        $stmt->execute();
        $stmt->close();
    }
    $_SESSION['message'] = "Changes Saved Successfully";
    header("Location: users.php");
    exit;
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
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

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #333;
            color: white;
        }
        .button-container {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
        }
        .edit-btn, .save-btn {
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: 0.3s;
        }
        .edit-btn {
            background-color: #007bff;
            color: white;
        }
        .save-btn {
            background-color: #28a745;
            color: white;
        }
        .save-btn:disabled {
            background-color: #ddd;
            cursor: not-allowed;
        }
        select {
            padding: 5px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <h2>User List</h2>
    <?php if (isset($_SESSION['message'])): ?>
        <p style='color: green;'><?= $_SESSION['message'] ?></p>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>
    
    <form id="user-form" method="POST">
        <table>
            <tr>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Email</th>
                <th>Gender</th>
                <th>Profile Status</th>
                <th>Details</th>
            </tr>
            <?php foreach ($users as $index => $user): ?>
                <tr>
                    <td><?= htmlspecialchars($user['First_Name']) ?></td>
                    <td><?= htmlspecialchars($user['Last_Name']) ?></td>
                    <td><?= htmlspecialchars($user['Email_Id']) ?></td>
                    <td><?= htmlspecialchars($user['Gender']) ?></td>
                    <td>
                        <select name="profile_status[]" class="editable" data-index="<?= $index ?>" disabled>
                            <option value="1" <?= $user['Profile_Status'] == 1 ? 'selected' : '' ?>>Active</option>
                            <option value="0" <?= $user['Profile_Status'] == 0 ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </td>
                    <td>
                    <a href="userDetails.php?email=<?= urlencode($user['Email_Id']) ?>">View Details</a>

                    </td>
                    <input type="hidden" name="email[]" value="<?= $user['Email_Id'] ?>">
                </tr>
            <?php endforeach; ?>
        </table>
        
        <div class="button-container">
            <button type="button" class="edit-btn" id="edit-btn" onclick="enableEditing()">✎ EDIT STATUS</button>
            <button type="submit" class="save-btn" id="save-changes" disabled>💾 SAVE CHANGES</button>
        </div>
    </form>

    <script>
        let isEditing = false;
        function enableEditing() {
            if (isEditing) return;
            isEditing = true;
            document.querySelectorAll('.editable').forEach(element => {
                element.disabled = false;
            });
            document.getElementById('save-changes').disabled = false;
        }
    </script>
</body>
</html>