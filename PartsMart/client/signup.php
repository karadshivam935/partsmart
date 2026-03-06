<?php
session_start();
include '../config/dbConnect.php';
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
$showSuccess = false; // Flag for showing success message

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
    $errors['email'] = "Please Enter Valid Email!";
  }

  // Password Validation
  $formData['password'] = trim($_POST['password'] ?? '');
  if (strlen($formData['password']) < 6) {
    $errors['password'] = "Password must be at least 6 characters long!";
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
  if (strlen($formData['security-answer']) < 2) {  // Updated validation
    $errors['security-answer'] = "Security answer must be at least 2 characters long.";
  }

  // If no errors, process the form
  if (empty($errors)) {
    $firstName = trim($_POST['firstname']);
    $lastName = trim($_POST['lastname']);
    $gender = $_POST['gender'];
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $address = trim($_POST['address']);
    $pincode = trim($_POST['pincode']);
    $phone = trim($_POST['phone']);
    $securityQuestionID = $_POST['security-question'];
    $securityAnswer = trim($_POST['security-answer']);
    $userType = "Customer";
    $registrationDate = date("Y-m-d");
    $status = "1";

    // Check if email already exists
    $checkEmail = $conn->prepare("SELECT * FROM user_detail_tbl WHERE Email_Id = ?");
    $checkEmail->bind_param("s", $email);
    $checkEmail->execute();
    $result = $checkEmail->get_result();

    if ($result->num_rows > 0) {
      echo "<script>alert('Email already exists!'); window.location.href='signup.php';</script>";
      exit();
    }

    // Use prepared statement for insertion
    $stmt = $conn->prepare("INSERT INTO user_detail_tbl 
                (First_Name, Last_Name, Gender, Email_Id, PASSWORD, Address, Pincode, Phone_Number, 
                Question_Id, Security_Ans, User_Type, Reg_Date, Profile_Status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param(
      "sssssssssssss",
      $firstName,
      $lastName,
      $gender,
      $email,
      $password,
      $address,
      $pincode,
      $phone,
      $securityQuestionID,
      $securityAnswer,
      $userType,
      $registrationDate,
      $status
    );

    if ($stmt->execute()) {
      $showSuccess = true; // Set flag to show success message
    } else {
      echo "<script>alert('Signup Failed! Try Again.'); window.location.href='signup.php';</script>";
      exit();
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Signup</title>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&family=Poppins:wght@300;400;600&display=swap"
    rel="stylesheet">
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

    .signup-container {
      max-width: 600px;
      margin: 0 auto;
      background: white;
      padding: 30px;
      border-radius: 8px;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .form-group {
      margin-bottom: 20px;
    }

    .form-group label {
      display: block;
      margin-bottom: 5px;
      font-weight: 500;
    }

    .form-group input,
    .form-group select {
      width: 100%;
      padding: 8px;
      border: 1px solid #ddd;
      border-radius: 4px;
      box-sizing: border-box;
    }

    .form-group-row {
      display: flex;
      gap: 20px;
      margin-bottom: 20px;
    }

    .form-group-row>div {
      flex: 1;
    }

    .form-group-row-radio {
      margin-bottom: 20px;
    }

    .form-group-row-radio label {
      margin-right: 20px;
    }

    .error-message {
      color: #dc3545;
      font-size: 0.875em;
      margin-top: 5px;
    }

    .success-message {
      color: #28a745;
      background-color: #d4edda;
      border: 1px solid #c3e6cb;
      padding: 10px;
      border-radius: 4px;
      margin-bottom: 20px;
      text-align: center;
    }

    .btn {
      background-color: #373737;
      color: white;
      padding: 10px 20px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      width: 100%;
      font-size: 16px;
    }

    .btn:hover {
      color: #373737;
      background-color: white;
    }

    .form-group textarea {
      width: 100%;
      padding: 8px;
      border: 1px solid #ddd;
      border-radius: 4px;
      box-sizing: border-box;
      min-height: 100px;
      resize: vertical;
      font-family: 'Roboto', sans-serif;
      font-size: 14px;
      line-height: 1.5;
    }

    #success-message {
      display:
        <?php echo $showSuccess ? 'block' : 'none'; ?>
      ;
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
  <div id="success-message">Signup successful! Redirecting...</div>

  <div class="signup-container">
    <h2>Signup</h2>


    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
      <!-- First Name and Last Name -->
      <div class="form-group-row">
        <div class="form-group">
          <label for="firstname">First Name</label>
          <input type="text" id="firstname" name="firstname"
            value="<?php echo htmlspecialchars($formData['firstname'] ?? ''); ?>" placeholder="Enter your first name"
            required>
          <?php if (isset($errors['firstname'])): ?>
            <div class="error-message"><?php echo $errors['firstname']; ?></div>
          <?php endif; ?>
        </div>
        <div class="form-group">
          <label for="lastname">Last Name</label>
          <input type="text" id="lastname" name="lastname"
            value="<?php echo htmlspecialchars($formData['lastname'] ?? ''); ?>" placeholder="Enter your last name"
            required>
          <?php if (isset($errors['lastname'])): ?>
            <div class="error-message"><?php echo $errors['lastname']; ?></div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Gender -->
      <div class="form-group-row-radio">
        <label>Gender:</label>
        <label for="male">
          <input type="radio" id="male" name="gender" value="Male" <?php echo (isset($formData['gender']) && $formData['gender'] === 'Male') ? 'checked' : ''; ?>> Male
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
        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>"
          placeholder="Enter your email" required>
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
        <textarea id="address" name="address" placeholder="Enter your address"
          required><?php echo htmlspecialchars($formData['address'] ?? ''); ?></textarea>
        <?php if (isset($errors['address'])): ?>
          <div class="error-message"><?php echo $errors['address']; ?></div>
        <?php endif; ?>
      </div>

      <!-- Pincode -->
      <div class="form-group">
        <label for="pincode">Pincode</label>
        <input type="text" id="pincode" name="pincode"
          value="<?php echo htmlspecialchars($formData['pincode'] ?? ''); ?>" placeholder="Enter your pincode" required>
        <?php if (isset($errors['pincode'])): ?>
          <div class="error-message"><?php echo $errors['pincode']; ?></div>
        <?php endif; ?>
      </div>

      <!-- Phone Number -->
      <div class="form-group">
        <label for="phone">Phone Number</label>
        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($formData['phone'] ?? ''); ?>"
          placeholder="Enter your phone number" required>
        <?php if (isset($errors['phone'])): ?>
          <div class="error-message"><?php echo $errors['phone']; ?></div>
        <?php endif; ?>
      </div>

      <!-- Security Question -->
      <div class="form-group">
        <label for="security-question">Security Question</label>
        <select id="security-question" name="security-question" required>
          <?php foreach ($securityQuestions as $question) { ?>
            <option value="<?= $question['Question_id'] ?>"><?= htmlspecialchars($question['Description']) ?></option>
          <?php } ?>
        </select>
      </div>

      <!-- Security Answer -->
      <div class="form-group">
        <label for="security-answer">Security Answer</label>
        <input type="text" id="security-answer" name="security-answer"
          value="<?php echo htmlspecialchars($formData['security-answer'] ?? ''); ?>" placeholder="Enter your answer"
          required>
        <?php if (isset($errors['security-answer'])): ?>
          <div class="error-message"><?php echo $errors['security-answer']; ?></div>
        <?php endif; ?>
      </div>

      <!-- Submit Button -->
      <button type="submit" class="btn">Sign Up</button>
    </form>
  </div>

  <?php if ($showSuccess): ?>
    <script>
      // Redirect after 3 seconds
      setTimeout(function () {
        window.location.href = 'index.php';
      }, 1500);
    </script>
  <?php endif; ?>
</body>

</html>