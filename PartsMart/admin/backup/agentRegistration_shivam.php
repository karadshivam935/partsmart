<?php
session_start();
include 'dbConnect.php'; // Ensure this file contains your database connection

// Fetch Security Questions from Database
$securityQuestions = [];
$query = "SELECT Question_id, Description FROM security_questions_tbl";
$result = $conn->query($query);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $securityQuestions[] = $row;
    }
}

// Initialize error array
$errors = [];
$formData = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // First Name Validation
    $formData['firstname'] = trim($_POST['firstname'] ?? '');
    if (!preg_match("/^[A-Za-z]{2,}$/", $formData['firstname'])) {
        $errors['firstname'] = "First Name must contain only alphabets and at least 2 characters.";
    }

    // Last Name Validation
    $formData['lastname'] = trim($_POST['lastname'] ?? '');
    if (!preg_match("/^[A-Za-z]{2,}$/", $formData['lastname'])) {
        $errors['lastname'] = "Last Name must contain only alphabets and at least 2 characters.";
    }

    // Gender Validation
    $formData['gender'] = $_POST['gender'] ?? '';
    if (empty($formData['gender'])) {
        $errors['gender'] = "Please select your gender.";
    }

    // Email Validation
    $formData['email'] = trim($_POST['email'] ?? '');
    if (!preg_match("/^[a-z0-9]+(?:\.[a-z0-9]+)*@[a-z0-9]{2,}\.[a-z]{2,}$/i", $formData['email'])) {
        $errors['email'] = "Please enter a valid email address.";
    }

    // Password Validation
    $formData['password'] = trim($_POST['password'] ?? '');
    if (strlen($formData['password']) < 6) {
        $errors['password'] = "Password must be at least 6 characters long.";
    }

    // Address Validation
    $formData['address'] = trim($_POST['address'] ?? '');
    if (strlen($formData['address']) < 5) {
        $errors['address'] = "Address must be at least 5 characters long.";
    }

    // Pincode Validation
    $formData['pincode'] = trim($_POST['pincode'] ?? '');
    if (!preg_match("/^\d{6}$/", $formData['pincode'])) {
        $errors['pincode'] = "Pincode must be exactly 6 digits.";
    }

    // Phone Validation
    $formData['phone'] = trim($_POST['phone'] ?? '');
    if (!preg_match("/^\d{10}$/", $formData['phone'])) {
        $errors['phone'] = "Phone number must be exactly 10 digits.";
    }

    // Security Answer Validation
    $formData['security-answer'] = trim($_POST['security-answer'] ?? '');
    if (empty($formData['security-answer'])) {
        $errors['security-answer'] = "Please enter an answer for the security question.";
    }

    // Status Validation
    $formData['status'] = $_POST['status'] ?? '';
    if (empty($formData['status'])) {
        $errors['status'] = "Please select a status.";
    }

    // If no errors, proceed with database operations
    if (empty($errors)) {
        $firstName = $formData['firstname'];
        $lastName = $formData['lastname'];
        $gender = $formData['gender'];
        $email = $formData['email'];
        $password = password_hash($formData['password'], PASSWORD_BCRYPT);
        $address = $formData['address'];
        $pincode = $formData['pincode'];
        $phone = $formData['phone'];
        $securityQuestionID = $_POST['security-question'];
        $securityAnswer = $formData['security-answer'];
        $userType = "Delivery Agent";
        $registrationDate = date("Y-m-d");
        $status = $formData['status'];

        // Check if email already exists
        $checkEmail = $conn->prepare("SELECT * FROM user_detail_tbl WHERE Email_Id = ?");
        $checkEmail->bind_param("s", $email);
        $checkEmail->execute();
        $result = $checkEmail->get_result();
        if ($result->num_rows > 0) {
            $errors['email'] = "Email already exists!";
        } else {
            // Insert data into database
            $sql = "INSERT INTO user_detail_tbl 
                    (First_Name, Last_Name, Gender, Email_Id, PASSWORD, Address, Pincode, Phone_Number, 
                    Question_Id, Security_Ans, User_Type, Reg_Date, Profile_Status) 
                    VALUES 
                    ('$firstName', '$lastName', '$gender', '$email', '$password', '$address', '$pincode', '$phone', 
                    '$securityQuestionID', '$securityAnswer', '$userType', '$registrationDate', '$status')";

            if ($conn->query($sql) === TRUE) {
                echo "<script>alert('Agent Registered successfully!'); window.location.href='agentRegistration_shivam.php';</script>";
            } else {
                $errors['database'] = "Signup Failed! Try Again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent Registration</title>
    <style>
        * {
            margin: 0;
            padding: 100;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: white;
            min-height: 100vh;
            overflow-x: hidden;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        body::-webkit-scrollbar {
            display: none;
        }

        .signup-container {
            width: 100%;
            max-width: 700px;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            font-size: 28px;
            font-weight: 600;
            color: #333;
            margin-bottom: 25px;
        }

        .error-message {
            color: #e63946;
            font-size: 13px;
            margin-top: 5px;
            font-weight: 400;
        }

        .form-group-row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            flex: 1;
            min-width: 280px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-size: 14px;
            font-weight: 500;
            color: #444;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            background-color: #fafafa;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: #4CAF50;
            outline: none;
        }

        .form-group-row-radio {
            margin-bottom: 20px;
        }

        .form-group-row-radio label {
            font-size: 14px;
            font-weight: 500;
            color: #444;
            margin-right: 20px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .form-group-row-radio input[type="radio"] {
            width: auto;
            margin: 0;
        }

        .btn {
            display: block;
            width: 100%;
            padding: 14px;
            background-color: #4CAF50;
            color: white;
            font-size: 16px;
            font-weight: 500;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn:hover {
            background-color: #45a049;
        }

        .form-group select {
            height: 44px;
            appearance: none;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="gray" viewBox="0 0 24 24"><path d="M7 10l5 5 5-5z"/></svg>') no-repeat right 12px center;
            background-size: 12px;
        }

        @media (max-width: 768px) {
            .signup-container {
                padding: 20px;
                max-width: 100%;
            }

            .form-group {
                min-width: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="signup-container">
        <h2>Agent Registration</h2>
        <form action="agentRegistration_shivam.php" method="POST">
            <!-- First Name & Last Name -->
            <div class="form-group-row">
                <div class="form-group">
                    <label for="firstname">First Name</label>
                    <input type="text" id="firstname" name="firstname"
                        value="<?php echo htmlspecialchars($formData['firstname'] ?? ''); ?>"
                        placeholder="Enter your first name" required>
                    <?php if (isset($errors['firstname'])): ?>
                        <div class="error-message"><?php echo $errors['firstname']; ?></div>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="lastname">Last Name</label>
                    <input type="text" id="lastname" name="lastname"
                        value="<?php echo htmlspecialchars($formData['lastname'] ?? ''); ?>"
                        placeholder="Enter your last name" required>
                    <?php if (isset($errors['lastname'])): ?>
                        <div class="error-message"><?php echo $errors['lastname']; ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Gender -->
            <div class="form-group-row-radio">
                <label>Gender:</label>
                <label for="male">
                    <input type="radio" id="male" name="gender" value="Male" <?php echo (isset($formData['gender']) && $formData['gender'] === 'Male') ? 'checked' : ''; ?> required> Male
                </label>
                <label for="female">
                    <input type="radio" id="female" name="gender" value="Female" <?php echo (isset($formData['gender']) && $formData['gender'] === 'Female') ? 'checked' : ''; ?>> Female
                </label>
                <label for="other">
                    <input type="radio" id="other" name="gender" value="Other" <?php echo (isset($formData['gender']) && $formData['gender'] === 'Other') ? 'checked' : ''; ?>> Other
                </label>
                <?php if (isset($errors['gender'])): ?>
                    <div class="error-message"><?php echo $errors['gender']; ?></div>
                <?php endif; ?>
            </div>

            <!-- Email -->
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email"
                    value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>" placeholder="Enter your email"
                    required>
                <?php if (isset($errors['email'])): ?>
                    <div class="error-message"><?php echo $errors['email']; ?></div>
                <?php endif; ?>
            </div>

            <!-- Password -->
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
                <?php if (isset($errors['password'])): ?>
                    <div class="error-message"><?php echo $errors['password']; ?></div>
                <?php endif; ?>
            </div>

            <!-- Address -->
            <div class="form-group">
                <label for="address">Address</label>
                <input type="text" id="address" name="address"
                    value="<?php echo htmlspecialchars($formData['address'] ?? ''); ?>" placeholder="Enter your address"
                    required>
                <?php if (isset($errors['address'])): ?>
                    <div class="error-message"><?php echo $errors['address']; ?></div>
                <?php endif; ?>
            </div>

            <!-- Pincode & Phone -->
            <div class="form-group-row">
                <div class="form-group">
                    <label for="pincode">Pincode</label>
                    <input type="text" id="pincode" name="pincode"
                        value="<?php echo htmlspecialchars($formData['pincode'] ?? ''); ?>" pattern="\d{6}"
                        placeholder="Enter your pincode" required>
                    <?php if (isset($errors['pincode'])): ?>
                        <div class="error-message"><?php echo $errors['pincode']; ?></div>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone"
                        value="<?php echo htmlspecialchars($formData['phone'] ?? ''); ?>" pattern="\d{10}"
                        placeholder="Enter your phone number" required>
                    <?php if (isset($errors['phone'])): ?>
                        <div class="error-message"><?php echo $errors['phone']; ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Security Question & Answer -->
            <div class="form-group-row">
                <div class="form-group">
                    <label for="security-question">Security Question</label>
                    <select id="security-question" name="security-question" required>
                        <?php foreach ($securityQuestions as $question): ?>
                            <option value="<?php echo $question['Question_id']; ?>">
                                <?php echo htmlspecialchars($question['Description']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="security-answer">Security Answer</label>
                    <input type="text" id="security-answer" name="security-answer"
                        value="<?php echo htmlspecialchars($formData['security-answer'] ?? ''); ?>"
                        placeholder="Enter your answer" required>
                    <?php if (isset($errors['security-answer'])): ?>
                        <div class="error-message"><?php echo $errors['security-answer']; ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Status -->
            <div class="form-group-row-radio">
                <label>Status:</label>
                <label for="active">
                    <input type="radio" id="active" name="status" value="1" <?php echo (isset($formData['status']) && $formData['status'] === '1') ? 'checked' : ''; ?> required> Active
                </label>
                <label for="inactive">
                    <input type="radio" id="inactive" name="status" value="0" <?php echo (isset($formData['status']) && $formData['status'] === '0') ? 'checked' : ''; ?>> Inactive
                </label>
                <?php if (isset($errors['status'])): ?>
                    <div class="error-message"><?php echo $errors['status']; ?></div>
                <?php endif; ?>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn">Register Agent</button>
        </form>
    </div>
</body>

</html>