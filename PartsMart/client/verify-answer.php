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
  $_SESSION['forgot_password_step'] != 1
) {
  unset($_SESSION['forgot_password_email']);
  unset($_SESSION['security_question_id']);
  unset($_SESSION['forgot_password_time']);
  $_SESSION['forgot_password_step'] = 0;
  header("Location: forgot-password.php");
  exit();
}

$email = $_SESSION['forgot_password_email'];
$questionId = $_SESSION['security_question_id'];

// Fetch stored security answer
$stmt = $conn->prepare("SELECT Security_Ans FROM user_detail_tbl WHERE Email_Id = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$securityAnswer = $user['Security_Ans'];

// Fetch security question
$stmt = $conn->prepare("SELECT Description FROM security_questions_tbl WHERE Question_Id = ?");
$stmt->bind_param("i", $questionId);
$stmt->execute();
$result = $stmt->get_result();
$question = $result->fetch_assoc();
$securityQuestion = $question['Description'];

// Initialize form data and error
$formData = ['answer' => ''];
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $formData['answer'] = trim($_POST['answer']); // Retain entered answer
  $userAnswer = strtolower($formData['answer']);

  if (empty($userAnswer)) {
    $error = "Answer cannot be empty.";
  } elseif (strtolower($securityAnswer) !== $userAnswer) {
    $error = "Incorrect answer. Please try again.";
  } else {
    $_SESSION['forgot_password_step'] = 2; // Move to step 2
    header("Location: newpassword.php");
    exit();
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verify Security Question</title>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&family=Poppins:wght@300;400;600&display=swap"
    rel="stylesheet" />
  <style>
    body,
    html {
      margin: 0;
      padding: 0;
      display: flex;
      justify-content: center;
      align-items: center;
      box-sizing: border-box;
      overflow-x: hidden;
      font-family: Poppins, sans-serif;
    }

    body::-webkit-scrollbar {
      display: none;
    }

    .container {
      background: white;
      padding: 2rem;
      border-radius: 10px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      text-align: center;
      width: 400px;
    }

    .form-group {
      margin-top: 1rem;
      text-align: left;
    }

    .form-group label {
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
      margin-top: 1rem;
      background-color: #373737;
      color: white;
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
  <div class="container">
    <h2>Verify Security Question</h2>
    <form method="POST" action="" id="verify-answer-form">
      <p><strong>Security Question:</strong> <?php echo htmlspecialchars($securityQuestion); ?></p>
      <div class="form-group">
        <label for="answer">Enter Your Answer:</label>
        <input type="text" name="answer" id="answer" value="<?php echo htmlspecialchars($formData['answer']); ?>"
          required>
        <div id="answer-error" class="error-message">
          <?php echo !empty($error) ? $error : ''; ?>
        </div>
      </div>
      <button type="submit">Submit</button>
    </form>
  </div>

  <script>
    // Prevent back navigation
    history.pushState(null, null, location.href);
    window.onpopstate = function () {
      window.location.href = "forgot-password.php";
    };

    document.getElementById("verify-answer-form").addEventListener("submit", function (e) {
      let isValid = true;
      const answerInput = document.getElementById("answer");
      const answerError = document.getElementById("answer-error");

      // Reset error message if no server-side error exists
      if (!answerError.textContent) {
        answerError.style.display = "none";
        answerError.textContent = "";
      }

      // Client-side validation
      if (answerInput.value.trim() === "") {
        answerError.textContent = "Answer cannot be empty.";
        answerError.style.display = "block";
        isValid = false;
      }

      // Prevent submission if validation fails
      if (!isValid) {
        e.preventDefault();
      }

      // If server-side error exists, ensure it’s displayed
      <?php if (!empty($error)): ?>
        answerError.style.display = "block";
        e.preventDefault(); // Prevent submission if server-side error exists
      <?php endif; ?>
    });
  </script>
</body>

</html>